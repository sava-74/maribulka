<?php
/**
 * API для работы с акциями (скидками)
 *
 * GET /api/promotions.php - получить все акции
 * GET /api/promotions.php?id=1 - получить одну акцию
 * POST /api/promotions.php - создать новую акцию
 * PUT /api/promotions.php - обновить акцию
 * DELETE /api/promotions.php?id=1 - удалить акцию
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
            if (isset($_GET['id'])) {
                // Получить одну акцию
                $stmt = $db->prepare("SELECT * FROM promotions WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $result = $stmt->fetch();

                if ($result) {
                    echo json_encode($result);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Акция не найдена']);
                }
            } else {
                // Получить все акции
                $stmt = $db->query("SELECT * FROM promotions WHERE is_active = 1 ORDER BY name");
                $result = $stmt->fetchAll();
                echo json_encode($result);
            }
            break;

        case 'POST':
            // Создать новую акцию
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['name']) || !isset($data['discount_percent'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указаны обязательные поля: name, discount_percent']);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO promotions (name, discount_percent, start_date, end_date, is_active)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['name'],
                $data['discount_percent'],
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['is_active'] ?? 1
            ]);

            echo json_encode([
                'success' => true,
                'id' => $db->lastInsertId(),
                'message' => 'Акция создана'
            ]);
            break;

        case 'PUT':
            // Обновить акцию
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID']);
                exit;
            }

            $stmt = $db->prepare("
                UPDATE promotions
                SET name = ?, discount_percent = ?, start_date = ?, end_date = ?, is_active = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $data['name'],
                $data['discount_percent'],
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['is_active'] ?? 1,
                $data['id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Акция обновлена'
            ]);
            break;

        case 'DELETE':
            // Удалить акцию
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID']);
                exit;
            }

            // Проверяем, используется ли акция в записях
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE promotion_id = ?");
            $stmt->execute([$_GET['id']]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                http_response_code(409);
                echo json_encode([
                    'error' => 'Невозможно удалить акцию',
                    'message' => "Акция используется в {$result['count']} записях. Деактивируйте вместо удаления."
                ]);
                exit;
            }

            // Удаляем
            $stmt = $db->prepare("DELETE FROM promotions WHERE id = ?");
            $stmt->execute([$_GET['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Акция удалена'
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
