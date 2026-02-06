<?php
/**
 * API для работы с записями на съёмку
 *
 * GET /api/bookings.php - получить все записи
 * GET /api/bookings.php?id=1 - получить одну запись
 * GET /api/bookings.php?date=2026-02-15 - записи на дату
 * GET /api/bookings.php?month=2026-02 - записи за месяц
 * POST /api/bookings.php - создать новую запись
 * PUT /api/bookings.php - обновить запись
 * DELETE /api/bookings.php?id=1 - удалить запись
 *
 * Специальные действия:
 * POST /api/bookings.php?action=complete&id=1 - отметить "Съёмка состоялась"
 * POST /api/bookings.php?action=deliver&id=1 - отметить "Проект сдан"
 * POST /api/bookings.php?action=cancel&id=1 - отменить съёмку
 * POST /api/bookings.php?action=payment&id=1 - добавить оплату
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once 'database.php';

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Специальные действия
    if ($method === 'POST' && isset($_GET['action'])) {
        handleSpecialAction($db, $_GET['action'], $_GET['id'] ?? null);
        exit;
    }

    switch ($method) {
        case 'GET':
            handleGet($db);
            break;

        case 'POST':
            handlePost($db);
            break;

        case 'PUT':
            handlePut($db);
            break;

        case 'DELETE':
            handleDelete($db);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Метод не поддерживается']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}

// ============================================
// ФУНКЦИИ
// ============================================

function handleGet($db) {
    if (isset($_GET['id'])) {
        // Получить одну запись с полной информацией
        $stmt = $db->prepare("
            SELECT
                b.*,
                c.name as client_name,
                st.name as shooting_type_name,
                st.base_price as shooting_type_price,
                p.name as promotion_name,
                p.discount_percent
            FROM bookings b
            LEFT JOIN clients c ON b.client_id = c.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            LEFT JOIN promotions p ON b.promotion_id = p.id
            WHERE b.id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $result = $stmt->fetch();

        if ($result) {
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Запись не найдена']);
        }
    } elseif (isset($_GET['date'])) {
        // Получить записи на конкретную дату
        $stmt = $db->prepare("
            SELECT
                b.*,
                c.name as client_name,
                st.name as shooting_type_name
            FROM bookings b
            LEFT JOIN clients c ON b.client_id = c.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            WHERE b.shooting_date = ?
            ORDER BY b.shooting_date, b.created_at
        ");
        $stmt->execute([$_GET['date']]);
        $result = $stmt->fetchAll();
        echo json_encode($result);
    } elseif (isset($_GET['month'])) {
        // Получить записи за месяц
        $stmt = $db->prepare("
            SELECT
                b.*,
                c.name as client_name,
                st.name as shooting_type_name
            FROM bookings b
            LEFT JOIN clients c ON b.client_id = c.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            WHERE DATE_FORMAT(b.shooting_date, '%Y-%m') = ?
            ORDER BY b.shooting_date, b.created_at
        ");
        $stmt->execute([$_GET['month']]);
        $result = $stmt->fetchAll();
        echo json_encode($result);
    } else {
        // Получить все записи
        $stmt = $db->query("
            SELECT
                b.*,
                c.name as client_name,
                st.name as shooting_type_name
            FROM bookings b
            LEFT JOIN clients c ON b.client_id = c.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            WHERE b.status != 'cancelled'
            ORDER BY b.shooting_date DESC, b.created_at DESC
            LIMIT 100
        ");
        $result = $stmt->fetchAll();
        echo json_encode($result);
    }
}

function handlePost($db) {
    $data = json_decode(file_get_contents('php://input'), true);

    // Валидация
    $required = ['shooting_date', 'client_id', 'phone', 'shooting_type_id', 'quantity'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Не указано обязательное поле: $field"]);
            exit;
        }
    }

    // Получаем данные для расчётов
    $stmt = $db->prepare("SELECT base_price FROM shooting_types WHERE id = ?");
    $stmt->execute([$data['shooting_type_id']]);
    $shooting_type = $stmt->fetch();

    $base_price = $shooting_type['base_price'];
    $discount = 0;
    $discount_percent = 0;

    // Если есть акция, рассчитываем скидку
    if (!empty($data['promotion_id'])) {
        $stmt = $db->prepare("SELECT discount_percent FROM promotions WHERE id = ?");
        $stmt->execute([$data['promotion_id']]);
        $promotion = $stmt->fetch();
        $discount_percent = $promotion['discount_percent'];
        $discount = ($base_price * $discount_percent) / 100;
    }

    $final_price = $base_price - $discount;
    $total_amount = $final_price * $data['quantity'];

    // Рассчитываем дату выдачи
    $processing_days = $data['processing_days'] ?? 7;
    $delivery_date = date('Y-m-d', strtotime($data['shooting_date'] . " + $processing_days days"));

    // Создаём запись
    $stmt = $db->prepare("
        INSERT INTO bookings (
            booking_date, shooting_date, processing_days, delivery_date,
            client_id, phone, shooting_type_id, quantity, promotion_id,
            base_price, discount, final_price, total_amount,
            paid_amount, payment_status, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $booking_date = date('Y-m-d');
    $paid_amount = 0;
    $payment_status = 'unpaid';

    $stmt->execute([
        $booking_date,
        $data['shooting_date'],
        $processing_days,
        $delivery_date,
        $data['client_id'],
        $data['phone'],
        $data['shooting_type_id'],
        $data['quantity'],
        $data['promotion_id'] ?? null,
        $base_price,
        $discount,
        $final_price,
        $total_amount,
        $paid_amount,
        $payment_status,
        'new'
    ]);

    $booking_id = $db->lastInsertId();

    // Если указана оплата сразу, добавляем её
    if (!empty($data['payment_amount']) && $data['payment_amount'] > 0) {
        $payment_amount = min($data['payment_amount'], $total_amount);
        addPayment($db, $booking_id, $data['client_id'], $payment_amount, $total_amount);
    }

    // Обновляем счётчик съёмок у клиента
    $stmt = $db->prepare("UPDATE clients SET total_bookings = total_bookings + 1 WHERE id = ?");
    $stmt->execute([$data['client_id']]);

    echo json_encode([
        'success' => true,
        'id' => $booking_id,
        'message' => 'Запись создана'
    ]);
}

function handlePut($db) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Не указан ID']);
        exit;
    }

    // Проверяем, можно ли редактировать
    $stmt = $db->prepare("SELECT status FROM bookings WHERE id = ?");
    $stmt->execute([$data['id']]);
    $booking = $stmt->fetch();

    if ($booking['status'] === 'completed' || $booking['status'] === 'delivered') {
        http_response_code(403);
        echo json_encode(['error' => 'Нельзя редактировать завершённую запись']);
        exit;
    }

    // Пересчитываем цены если изменились параметры
    if (isset($data['shooting_type_id']) || isset($data['promotion_id']) || isset($data['quantity'])) {
        // Получаем базовую цену
        $stmt = $db->prepare("SELECT base_price FROM shooting_types WHERE id = ?");
        $stmt->execute([$data['shooting_type_id']]);
        $shooting_type = $stmt->fetch();
        $base_price = $shooting_type['base_price'];

        // Рассчитываем скидку
        $discount = 0;
        if (!empty($data['promotion_id'])) {
            $stmt = $db->prepare("SELECT discount_percent FROM promotions WHERE id = ?");
            $stmt->execute([$data['promotion_id']]);
            $promotion = $stmt->fetch();
            $discount_percent = $promotion['discount_percent'];
            $discount = ($base_price * $discount_percent) / 100;
        }

        $final_price = $base_price - $discount;
        $total_amount = $final_price * $data['quantity'];

        $data['base_price'] = $base_price;
        $data['discount'] = $discount;
        $data['final_price'] = $final_price;
        $data['total_amount'] = $total_amount;
    }

    // Пересчитываем дату выдачи
    if (isset($data['shooting_date']) || isset($data['processing_days'])) {
        $processing_days = $data['processing_days'] ?? 7;
        $shooting_date = $data['shooting_date'];
        $data['delivery_date'] = date('Y-m-d', strtotime("$shooting_date + $processing_days days"));
    }

    // Обновляем запись
    $fields = ['shooting_date', 'processing_days', 'delivery_date', 'phone',
               'shooting_type_id', 'quantity', 'promotion_id',
               'base_price', 'discount', 'final_price', 'total_amount'];

    $updates = [];
    $values = [];

    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $values[] = $data[$field];
        }
    }

    $values[] = $data['id'];

    $stmt = $db->prepare("UPDATE bookings SET " . implode(', ', $updates) . " WHERE id = ?");
    $stmt->execute($values);

    echo json_encode([
        'success' => true,
        'message' => 'Запись обновлена'
    ]);
}

function handleDelete($db) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Не указан ID']);
        exit;
    }

    // Проверяем, можно ли удалить
    $stmt = $db->prepare("SELECT status FROM bookings WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $booking = $stmt->fetch();

    if ($booking['status'] === 'completed' || $booking['status'] === 'delivered') {
        http_response_code(403);
        echo json_encode(['error' => 'Нельзя удалить завершённую запись. Используйте отмену.']);
        exit;
    }

    // Удаляем
    $stmt = $db->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Запись удалена'
    ]);
}

function handleSpecialAction($db, $action, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Не указан ID']);
        exit;
    }

    switch ($action) {
        case 'complete':
            // Отметить "Съёмка состоялась"
            $stmt = $db->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Съёмка отмечена как состоявшаяся']);
            break;

        case 'deliver':
            // Отметить "Проект сдан"
            $stmt = $db->prepare("SELECT status FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();

            if ($booking['status'] !== 'completed') {
                http_response_code(403);
                echo json_encode(['error' => 'Сначала отметьте "Съёмка состоялась"']);
                exit;
            }

            $stmt = $db->prepare("UPDATE bookings SET status = 'delivered' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Проект отмечен как сданный']);
            break;

        case 'cancel':
            // Отменить съёмку
            $data = json_decode(file_get_contents('php://input'), true);
            $reason = $data['reason'] ?? 'Не указана';

            $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled', cancel_reason = ? WHERE id = ?");
            $stmt->execute([$reason, $id]);
            echo json_encode(['success' => true, 'message' => 'Съёмка отменена']);
            break;

        case 'payment':
            // Добавить оплату
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['amount'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указана сумма оплаты']);
                exit;
            }

            // Получаем данные записи
            $stmt = $db->prepare("SELECT client_id, total_amount, paid_amount FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();

            $remaining = $booking['total_amount'] - $booking['paid_amount'];
            $payment_amount = min($data['amount'], $remaining);

            if ($payment_amount <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Запись уже полностью оплачена']);
                exit;
            }

            addPayment($db, $id, $booking['client_id'], $payment_amount, $booking['total_amount']);

            echo json_encode([
                'success' => true,
                'message' => 'Оплата добавлена',
                'paid' => $booking['paid_amount'] + $payment_amount,
                'total' => $booking['total_amount']
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Неизвестное действие']);
            break;
    }
}

function addPayment($db, $booking_id, $client_id, $amount, $total_amount) {
    // Получаем текущую оплаченную сумму
    $stmt = $db->prepare("SELECT paid_amount FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    $current_paid = $booking['paid_amount'];

    $new_paid = $current_paid + $amount;

    // Определяем категорию платежа
    $category = 'balance'; // По умолчанию - доплата

    if ($current_paid == 0) {
        // Это первый платёж
        if ($amount >= $total_amount) {
            $category = 'full_payment'; // Полная оплата
        } else {
            $category = 'advance'; // Аванс
        }
    } elseif ($new_paid >= $total_amount) {
        $category = 'balance'; // Финальная доплата
    }

    // Определяем статус оплаты
    if ($new_paid >= $total_amount) {
        $payment_status = 'fully_paid';
    } elseif ($new_paid > 0) {
        $payment_status = 'partially_paid';
    } else {
        $payment_status = 'unpaid';
    }

    // Обновляем запись
    $stmt = $db->prepare("
        UPDATE bookings
        SET paid_amount = ?, payment_status = ?
        WHERE id = ?
    ");
    $stmt->execute([$new_paid, $payment_status, $booking_id]);

    // Добавляем запись в таблицу прихода
    $stmt = $db->prepare("
        INSERT INTO income (date, booking_id, client_id, amount, category)
        VALUES (CURDATE(), ?, ?, ?, ?)
    ");
    $stmt->execute([$booking_id, $client_id, $amount, $category]);
}
?>
