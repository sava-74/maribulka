<?php
/**
 * API для работы с приходом (платежами)
 *
 * GET /api/income.php - получить все платежи
 * GET /api/income.php?month=2026-02 - платежи за месяц
 * GET /api/income.php?booking_id=1 - платежи по записи
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once 'database.php';

$db = Database::getInstance()->getConnection();

// POST - создание платежа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $bookingId = $input['booking_id'] ?? null;
        $amount = $input['amount'] ?? null;
        $category = $input['category'] ?? 'balance';
        $date = $input['date'] ?? date('Y-m-d');

        if (!$bookingId || !$amount) {
            http_response_code(400);
            echo json_encode(['error' => 'Не указаны обязательные поля']);
            exit;
        }

        // Получаем client_id из заказа
        $stmt = $db->prepare("SELECT client_id FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            http_response_code(404);
            echo json_encode(['error' => 'Заказ не найден']);
            exit;
        }

        // Создаём запись о платеже
        $stmt = $db->prepare("
            INSERT INTO income (booking_id, client_id, amount, category, date, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$bookingId, $booking['client_id'], $amount, $category, $date]);

        // Обновляем статус оплаты заказа
        updatePaymentStatus($db, $bookingId);

        $lastId = $db->lastInsertId();
        echo json_encode(['success' => true, 'id' => (int)$lastId]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка БД: ' . $e->getMessage()]);
        exit;
    }
}

// DELETE - удаление платежа
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID не указан']);
        exit;
    }

    // Проверяем статус заказа и получаем booking_id
    $stmt = $db->prepare("
        SELECT i.booking_id, b.status
        FROM income i
        LEFT JOIN bookings b ON i.booking_id = b.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result && in_array($result['status'], ['completed', 'delivered'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Нельзя удалить платёж по проведенному заказу']);
        exit;
    }

    // Удаляем платеж
    $stmt = $db->prepare("DELETE FROM income WHERE id = ?");
    $stmt->execute([$id]);

    // Обновляем статус оплаты заказа
    if ($result && $result['booking_id']) {
        updatePaymentStatus($db, $result['booking_id']);
    }

    echo json_encode(['success' => true]);
    exit;
}

function updatePaymentStatus($db, $bookingId) {
    // Пересчитываем paid_amount
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM income WHERE booking_id = ?");
    $stmt->execute([$bookingId]);
    $result = $stmt->fetch();
    $totalPaid = $result['total_paid'];

    // Получаем total_amount заказа
    $stmt = $db->prepare("SELECT total_amount FROM bookings WHERE id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    $totalAmount = $booking['total_amount'];

    // Определяем новый статус оплаты
    if ($totalPaid == 0) {
        $paymentStatus = 'unpaid';
    } elseif ($totalPaid >= $totalAmount) {
        $paymentStatus = 'fully_paid';
    } else {
        $paymentStatus = 'partially_paid';
    }

    // Обновляем заказ
    $stmt = $db->prepare("UPDATE bookings SET paid_amount = ?, payment_status = ? WHERE id = ?");
    $stmt->execute([$totalPaid, $paymentStatus, $bookingId]);
}

try {
    if (isset($_GET['booking_id'])) {
        // Платежи по конкретной записи
        $stmt = $db->prepare("
            SELECT
                i.*,
                c.name as client_name,
                c.phone,
                b.shooting_date,
                st.name as shooting_type_name
            FROM income i
            LEFT JOIN clients c ON i.client_id = c.id
            LEFT JOIN bookings b ON i.booking_id = b.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            WHERE i.booking_id = ?
            ORDER BY i.date DESC
        ");
        $stmt->execute([$_GET['booking_id']]);
        $result = $stmt->fetchAll();
        echo json_encode($result);
    } elseif (isset($_GET['start']) && isset($_GET['end'])) {
        // Платежи за период (НОВОЕ!)
        $stmt = $db->prepare("
            SELECT
                i.*,
                c.name as client_name,
                c.phone,
                st.name as shooting_type_name,
                b.quantity,
                b.order_number,
                b.status as booking_status,
                b.payment_status,
                b.total_amount,
                b.paid_amount,
                b.booking_date,
                b.shooting_date,
                b.delivery_date,
                b.processed_at,
                b.id as booking_id,
                b.created_at as booking_created_at,
                p.discount_percent as promo_discount_percent
            FROM income i
            LEFT JOIN clients c ON i.client_id = c.id
            LEFT JOIN bookings b ON i.booking_id = b.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            LEFT JOIN promotions p ON b.promotion_id = p.id
            WHERE i.date BETWEEN ? AND ?
            ORDER BY i.date DESC
        ");
        $stmt->execute([$_GET['start'], $_GET['end']]);
        $result = $stmt->fetchAll();
        echo json_encode($result);
    } elseif (isset($_GET['month'])) {
        // Платежи за месяц (обратная совместимость)
        $stmt = $db->prepare("
            SELECT
                i.*,
                c.name as client_name,
                c.phone,
                st.name as shooting_type_name,
                b.quantity,
                b.order_number,
                b.status as booking_status,
                b.payment_status,
                b.total_amount,
                b.paid_amount,
                b.booking_date,
                b.shooting_date,
                b.delivery_date,
                b.processed_at,
                b.id as booking_id,
                b.created_at as booking_created_at,
                p.discount_percent as promo_discount_percent
            FROM income i
            LEFT JOIN clients c ON i.client_id = c.id
            LEFT JOIN bookings b ON i.booking_id = b.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            LEFT JOIN promotions p ON b.promotion_id = p.id
            WHERE DATE_FORMAT(i.date, '%Y-%m') = ?
            ORDER BY i.date DESC
        ");
        $stmt->execute([$_GET['month']]);
        $result = $stmt->fetchAll();
        echo json_encode($result);
    } else {
        // Все платежи (последние 100)
        $stmt = $db->query("
            SELECT
                i.*,
                c.name as client_name,
                c.phone,
                st.name as shooting_type_name
            FROM income i
            LEFT JOIN clients c ON i.client_id = c.id
            LEFT JOIN bookings b ON i.booking_id = b.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            ORDER BY i.date DESC, i.created_at DESC
            LIMIT 100
        ");
        $result = $stmt->fetchAll();
        echo json_encode($result);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
