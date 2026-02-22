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
 * Специальные действия (НОВЫЙ БИЗНЕС-ПРОЦЕСС):
 * POST /api/bookings.php?action=confirm_session&id=1 - подтвердить съёмку (new → in_progress)
 * POST /api/bookings.php?action=complete_order&id=1 - выдать заказ с результатом (in_progress → completed/partially/not_completed)
 * POST /api/bookings.php?action=cancel&id=1 - отменить/клиент не пришёл (cancelled_by_photographer/cancelled_by_client/client_no_show)
 * POST /api/bookings.php?action=quick_payment&id=1 - быстрая оплата остатка
 * POST /api/bookings.php?action=payment&id=1 - добавить оплату
 *
 * УСТАРЕВШИЕ (для обратной совместимости):
 * POST /api/bookings.php?action=complete&id=1 - отметить "Съёмка состоялась" (→ использовать confirm_session)
 * POST /api/bookings.php?action=deliver&id=1 - провести заказ (→ использовать complete_order)
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

    // GET action для получения следующего ID
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_next_id') {
        $stmt = $db->query("SELECT MAX(id) as max_id FROM bookings");
        $result = $stmt->fetch();
        $nextId = ($result['max_id'] ?? 0) + 1;
        echo json_encode(['next_id' => $nextId]);
        exit;
    }

    // GET action для получения заказов доступных для возврата
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'refundable') {
        $stmt = $db->query("
            SELECT
                b.id,
                b.order_number,
                b.paid_amount,
                c.name as client_name
            FROM bookings b
            LEFT JOIN clients c ON b.client_id = c.id
            WHERE b.status IN ('new', 'in_progress')
            AND b.payment_status != 'unpaid'
            AND b.paid_amount > 0
            ORDER BY b.shooting_date DESC
        ");
        $result = $stmt->fetchAll();
        echo json_encode($result);
        exit;
    }

    // GET action для получения дохода по типам съёмок
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'income_by_type') {
        $month = $_GET['month'] ?? null;
        
        $whereClause = "b.payment_status = 'fully_paid' AND (b.status = 'delivered' OR b.status = 'completed')";
        
        if ($month) {
            $whereClause .= " AND DATE_FORMAT(b.shooting_date, '%Y-%m') = '" . $month . "'";
        }
        
        $stmt = $db->query("
            SELECT
                st.name as shooting_type_name,
                COUNT(b.id) as count,
                SUM(b.total_amount) as total
            FROM bookings b
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            WHERE $whereClause
            GROUP BY b.shooting_type_id
            ORDER BY total DESC
        ");
        $result = $stmt->fetchAll();
        echo json_encode($result);
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
    // ОТКЛЮЧЕНО: автоматическое обновление статусов
    // В новом бизнес-процессе статусы меняются только вручную фотографом

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
                st.name as shooting_type_name,
                p.name as promotion_name,
                p.discount_percent as promo_discount_percent
            FROM bookings b
            LEFT JOIN clients c ON b.client_id = c.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            LEFT JOIN promotions p ON b.promotion_id = p.id
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
                st.name as shooting_type_name,
                p.name as promotion_name,
                p.discount_percent as promo_discount_percent
            FROM bookings b
            LEFT JOIN clients c ON b.client_id = c.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            LEFT JOIN promotions p ON b.promotion_id = p.id
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
                st.name as shooting_type_name,
                p.name as promotion_name,
                p.discount_percent as promo_discount_percent
            FROM bookings b
            LEFT JOIN clients c ON b.client_id = c.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            LEFT JOIN promotions p ON b.promotion_id = p.id
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
            order_number, booking_date, shooting_date, processing_days, delivery_date,
            client_id, phone, shooting_type_id, quantity, promotion_id,
            base_price, discount, final_price, total_amount,
            paid_amount, payment_status, status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $booking_date = date('Y-m-d');
    $paid_amount = 0;
    $payment_status = 'unpaid';

    $stmt->execute([
        $data['order_number'] ?? '',
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
        'new',
        $data['notes'] ?? null
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
    $stmt = $db->prepare("SELECT status, is_locked FROM bookings WHERE id = ?");
    $stmt->execute([$data['id']]);
    $booking = $stmt->fetch();

    if ($booking['is_locked'] == 1) {
        http_response_code(403);
        echo json_encode(['error' => 'Нельзя редактировать заблокированную запись']);
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
               'base_price', 'discount', 'final_price', 'total_amount', 'notes'];

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
    $stmt = $db->prepare("SELECT status, is_locked FROM bookings WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $booking = $stmt->fetch();

    if ($booking['is_locked'] == 1) {
        http_response_code(403);
        echo json_encode(['error' => 'Нельзя удалить заблокированную запись. Используйте отмену.']);
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
        // ========================================
        // НОВЫЙ БИЗНЕС-ПРОЦЕСС
        // ========================================

        case 'confirm_session':
            // Подтвердить съёмку (new → in_progress)
            // Логика: проверить предоплату, если нет - вернуть ошибку
            $stmt = $db->prepare("SELECT status, paid_amount FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();

            if ($booking['status'] !== 'new') {
                http_response_code(403);
                echo json_encode(['error' => 'Можно подтвердить только новую съёмку']);
                exit;
            }

            // ВАЖНО: фронтенд сам проверит предоплату и откроет IncomeModal если нужно
            // Здесь просто меняем статус
            $stmt = $db->prepare("UPDATE bookings SET status = 'in_progress' WHERE id = ?");
            $stmt->execute([$id]);

            // Логируем в booking_history
            logBookingStatusChange($db, $id, 'new', 'in_progress', 'Подтверждение съёмки');

            echo json_encode(['success' => true, 'message' => 'Съёмка подтверждена. Статус: В работе']);
            break;

        case 'complete_order':
            // Выдать заказ (in_progress → completed/partially/not_completed)
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $data['result'] ?? null; // 'completed', 'completed_partially', 'not_completed'

            if (!in_array($result, ['completed', 'completed_partially', 'not_completed'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Неверный результат выполнения']);
                exit;
            }

            $stmt = $db->prepare("SELECT status, paid_amount, total_amount, client_id FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();

            if ($booking['status'] !== 'in_progress') {
                http_response_code(403);
                echo json_encode(['error' => 'Можно выдать только заказ со статусом "В работе"']);
                exit;
            }

            // Проверяем что оплата 100%
            if ($booking['paid_amount'] < $booking['total_amount']) {
                http_response_code(403);
                echo json_encode(['error' => 'Сначала необходимо собрать 100% оплату']);
                exit;
            }

            // Обновляем статус и блокируем редактирование
            $stmt = $db->prepare("
                UPDATE bookings
                SET status = ?, is_locked = 1, processed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$result, $id]);

            // Логируем
            logBookingStatusChange($db, $id, 'in_progress', $result, 'Выдача заказа');

            // Если клиент принял ЧАСТИЧНО или НЕ ПРИНЯЛ - создаём автовозврат
            if ($result === 'completed_partially') {
                // Частичный возврат 50%
                $refund_amount = $booking['total_amount'] * 0.5;
                createRefund($db, $id, $booking['client_id'], $refund_amount, 'partial');
                echo json_encode([
                    'success' => true,
                    'message' => 'Заказ завершён частично. Автовозврат 50% создан.',
                    'refund_amount' => $refund_amount
                ]);
            } elseif ($result === 'not_completed') {
                // Полный возврат 100%
                $refund_amount = $booking['total_amount'];
                createRefund($db, $id, $booking['client_id'], $refund_amount, 'full');
                echo json_encode([
                    'success' => true,
                    'message' => 'Клиент не принял заказ. Автовозврат 100% создан.',
                    'refund_amount' => $refund_amount
                ]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Заказ успешно выполнен!']);
            }
            break;

        // ========================================
        // УСТАРЕВШИЕ (для обратной совместимости)
        // ========================================

        case 'complete':
            // Старая логика "Съёмка состоялась" → редирект на confirm_session
            handleSpecialAction($db, 'confirm_session', $id);
            break;

        case 'deliver':
            // Старая логика "Провести заказ" → редирект на complete_order с результатом 'completed'
            $_POST_BACKUP = file_get_contents('php://input');
            file_put_contents('php://input', json_encode(['result' => 'completed']));
            handleSpecialAction($db, 'complete_order', $id);
            file_put_contents('php://input', $_POST_BACKUP);
            break;

        case 'quick_payment':
            // Быстрая оплата остатка
            $stmt = $db->prepare("SELECT client_id, total_amount, paid_amount FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();

            $remaining = $booking['total_amount'] - $booking['paid_amount'];

            if ($remaining <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Заказ уже полностью оплачен']);
                exit;
            }

            addPayment($db, $id, $booking['client_id'], $remaining, $booking['total_amount']);

            echo json_encode([
                'success' => true,
                'message' => 'Остаток оплачен',
                'paid' => $booking['total_amount'],
                'total' => $booking['total_amount']
            ]);
            break;

        case 'cancel':
            // Отменить съёмку / Клиент не пришёл
            $data = json_decode(file_get_contents('php://input'), true);
            $cancelledBy = $data['cancelled_by'] ?? 'client'; // 'client', 'photographer', 'no_show'

            // Определяем статус
            if ($cancelledBy === 'no_show') {
                $status = 'client_no_show';
                $message = 'Клиент не пришёл на съёмку';
            } elseif ($cancelledBy === 'photographer') {
                $status = 'cancelled_by_photographer';
                $message = 'Съёмка отменена фотографом';
            } else {
                $status = 'cancelled_by_client';
                $message = 'Съёмка отменена клиентом';
            }

            // Получаем данные заказа для возврата
            $stmt = $db->prepare("SELECT status, client_id, paid_amount FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();

            $old_status = $booking['status'];

            // Обновляем статус и блокируем
            $stmt = $db->prepare("UPDATE bookings SET status = ?, is_locked = 1 WHERE id = ?");
            $stmt->execute([$status, $id]);

            // Логируем
            logBookingStatusChange($db, $id, $old_status, $status, $message);

            // Если была оплата - создаём автовозврат ПРЕДОПЛАТЫ
            if ($booking['paid_amount'] > 0) {
                createRefund($db, $id, $booking['client_id'], $booking['paid_amount'], 'prepayment');
                echo json_encode([
                    'success' => true,
                    'message' => $message . '. Автовозврат предоплаты создан.',
                    'refund_amount' => $booking['paid_amount']
                ]);
            } else {
                echo json_encode(['success' => true, 'message' => $message]);
            }
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

/**
 * Создать возврат средств (автоматически при отмене/непринятии заказа)
 *
 * @param PDO $db
 * @param int $booking_id
 * @param int $client_id
 * @param float $amount
 * @param string $refund_type 'prepayment' | 'partial' | 'full'
 */
function createRefund($db, $booking_id, $client_id, $amount, $refund_type) {
    // Получаем ID категории "Возвраты" (должна быть ID=2 согласно миграции)
    $stmt = $db->query("SELECT id FROM expense_categories WHERE name = 'Возвраты' LIMIT 1");
    $category = $stmt->fetch();

    if (!$category) {
        // Если категория не найдена - создаём её
        $stmt = $db->prepare("INSERT INTO expense_categories (name) VALUES ('Возвраты')");
        $stmt->execute();
        $category_id = $db->lastInsertId();
    } else {
        $category_id = $category['id'];
    }

    // Создаём возврат
    $stmt = $db->prepare("
        INSERT INTO expenses (date, amount, category, description, booking_id, refund_type)
        VALUES (CURDATE(), ?, ?, ?, ?, ?)
    ");

    $description = "Возврат средств по заказу (автоматический)";

    $stmt->execute([
        $amount,
        $category_id,
        $description,
        $booking_id,
        $refund_type
    ]);
}

/**
 * Логировать изменение статуса заказа
 *
 * @param PDO $db
 * @param int $booking_id
 * @param string $old_status
 * @param string $new_status
 * @param string $reason
 */
function logBookingStatusChange($db, $booking_id, $old_status, $new_status, $reason = null) {
    $stmt = $db->prepare("
        INSERT INTO booking_history (booking_id, old_status, new_status, reason)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$booking_id, $old_status, $new_status, $reason]);
}
?>
