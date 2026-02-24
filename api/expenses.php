<?php
/**
 * API для работы с расходами
 *
 * GET /api/expenses.php - получить все расходы (последние 100)
 * GET /api/expenses.php?month=2026-02 - расходы за месяц
 * GET /api/expenses.php?balance=true - общий баланс кассы (приход - расход)
 * POST /api/expenses.php - создать расход
 * PUT /api/expenses.php - обновить расход
 * DELETE /api/expenses.php?id=1 - удалить расход
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
    switch ($method) {
        case 'GET':
            // Получить общий баланс кассы (приход - расход)
            if (isset($_GET['balance'])) {
                $stmtIncome = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM income");
                $stmtExpenses = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses");

                $totalIncome = $stmtIncome->fetch()['total'];
                $totalExpenses = $stmtExpenses->fetch()['total'];
                $balance = $totalIncome - $totalExpenses;

                echo json_encode([
                    'totalIncome' => floatval($totalIncome),
                    'totalExpenses' => floatval($totalExpenses),
                    'balance' => floatval($balance)
                ]);
                break;
            }

            if (isset($_GET['start']) && isset($_GET['end'])) {
                // Расходы за период (НОВОЕ!)
                $stmt = $db->prepare("
                    SELECT
                        e.*,
                        ec.name as category_name,
                        c.name as client_name,
                        st.name as shooting_type_name
                    FROM expenses e
                    LEFT JOIN expense_categories ec ON e.category = ec.id
                    LEFT JOIN bookings b ON e.booking_id = b.id
                    LEFT JOIN clients c ON b.client_id = c.id
                    LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
                    WHERE e.date BETWEEN ? AND ?
                    ORDER BY e.date DESC
                ");
                $stmt->execute([$_GET['start'], $_GET['end']]);
                $result = $stmt->fetchAll();
                echo json_encode($result);
            } elseif (isset($_GET['month'])) {
                // Расходы за месяц (обратная совместимость)
                $stmt = $db->prepare("
                    SELECT
                        e.*,
                        ec.name as category_name,
                        c.name as client_name,
                        st.name as shooting_type_name
                    FROM expenses e
                    LEFT JOIN expense_categories ec ON e.category = ec.id
                    LEFT JOIN bookings b ON e.booking_id = b.id
                    LEFT JOIN clients c ON b.client_id = c.id
                    LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
                    WHERE DATE_FORMAT(e.date, '%Y-%m') = ?
                    ORDER BY e.date DESC
                ");
                $stmt->execute([$_GET['month']]);
                $result = $stmt->fetchAll();
                echo json_encode($result);
            } else {
                // Все расходы (последние 100)
                $stmt = $db->query("
                    SELECT
                        e.*,
                        ec.name as category_name,
                        c.name as client_name,
                        st.name as shooting_type_name
                    FROM expenses e
                    LEFT JOIN expense_categories ec ON e.category = ec.id
                    LEFT JOIN bookings b ON e.booking_id = b.id
                    LEFT JOIN clients c ON b.client_id = c.id
                    LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
                    ORDER BY e.date DESC, e.created_at DESC
                    LIMIT 100
                ");
                $result = $stmt->fetchAll();
                echo json_encode($result);
            }
            break;

        case 'POST':
            // Создать расход
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['date']) || !isset($data['amount']) || !isset($data['category']) || !isset($data['description'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указаны обязательные поля: date, amount, category, description']);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO expenses (date, amount, category, description, booking_id)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['date'],
                $data['amount'],
                $data['category'],
                $data['description'],
                $data['booking_id'] ?? null
            ]);

            echo json_encode([
                'success' => true,
                'id' => $db->lastInsertId(),
                'message' => 'Расход создан'
            ]);
            break;

        case 'PUT':
            // Обновить расход
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID']);
                exit;
            }

            $stmt = $db->prepare("
                UPDATE expenses
                SET date = ?, amount = ?, category = ?, description = ?, booking_id = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $data['date'],
                $data['amount'],
                $data['category'],
                $data['description'],
                $data['booking_id'] ?? null,
                $data['id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Расход обновлён'
            ]);
            break;

        case 'DELETE':
            // Удалить расход
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$_GET['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Расход удалён'
            ]);
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
?>
