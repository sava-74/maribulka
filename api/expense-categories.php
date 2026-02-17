<?php
/**
 * API для работы с категориями расходов
 *
 * GET /api/expense-categories.php - получить все категории
 * GET /api/expense-categories.php?id=1 - получить одну категорию
 * POST /api/expense-categories.php - создать новую категорию
 * PUT /api/expense-categories.php - обновить категорию
 * DELETE /api/expense-categories.php?id=1 - удалить категорию
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
            // Проверка связей с расходами
            if (isset($_GET['check_relations']) && isset($_GET['id'])) {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM expenses WHERE category = ?");
                $stmt->execute([$_GET['id']]);
                $result = $stmt->fetch();
                echo json_encode($result['count'] > 0);
                break;
            }

            if (isset($_GET['id'])) {
                // Получить одну категорию
                $stmt = $db->prepare("SELECT * FROM expense_categories WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $result = $stmt->fetch();

                if ($result) {
                    echo json_encode($result);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Категория не найдена']);
                }
            } else {
                // Получить все категории
                $stmt = $db->query("SELECT * FROM expense_categories WHERE is_active = 1 ORDER BY name");
                $result = $stmt->fetchAll();
                echo json_encode($result);
            }
            break;

        case 'POST':
            // Создать новую категорию
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указано обязательное поле: name']);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO expense_categories (name, is_active)
                VALUES (?, ?)
            ");

            $stmt->execute([
                $data['name'],
                $data['is_active'] ?? 1
            ]);

            echo json_encode([
                'success' => true,
                'id' => $db->lastInsertId(),
                'message' => 'Категория создана'
            ]);
            break;

        case 'PUT':
            // Обновить категорию
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID']);
                exit;
            }

            $stmt = $db->prepare("
                UPDATE expense_categories
                SET name = ?, is_active = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $data['name'],
                $data['is_active'] ?? 1,
                $data['id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Категория обновлена'
            ]);
            break;

        case 'DELETE':
            // Удалить категорию
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID']);
                exit;
            }

            // Проверяем, используется ли категория в расходах
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM expenses WHERE category = ?");
            $stmt->execute([$_GET['id']]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                http_response_code(409);
                echo json_encode([
                    'error' => 'Невозможно удалить категорию',
                    'message' => "Категория используется в {$result['count']} расходах. Деактивируйте вместо удаления."
                ]);
                exit;
            }

            // Удаляем
            $stmt = $db->prepare("DELETE FROM expense_categories WHERE id = ?");
            $stmt->execute([$_GET['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Категория удалена'
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
