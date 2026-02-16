<?php
/**
 * API для работы с типами съёмок
 *
 * GET /api/shooting-types.php - получить все типы
 * GET /api/shooting-types.php?id=1 - получить один тип
 * POST /api/shooting-types.php - создать новый тип
 * PUT /api/shooting-types.php - обновить тип
 * DELETE /api/shooting-types.php?id=1 - удалить тип
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
            // Проверка связей с заказами
            if (isset($_GET['check_relations']) && isset($_GET['id'])) {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE shooting_type_id = ?");
                $stmt->execute([$_GET['id']]);
                $result = $stmt->fetch();
                echo json_encode($result['count'] > 0);
                break;
            }

            if (isset($_GET['id'])) {
                // Получить один тип съёмки
                $stmt = $db->prepare("SELECT * FROM shooting_types WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $result = $stmt->fetch();

                if ($result) {
                    echo json_encode($result);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Тип съёмки не найден']);
                }
            } else {
                // Получить все типы съёмок
                $stmt = $db->query("SELECT * FROM shooting_types WHERE is_active = 1 ORDER BY name");
                $result = $stmt->fetchAll();
                echo json_encode($result);
            }
            break;

        case 'POST':
            // Создать новый тип съёмки
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['name']) || !isset($data['base_price'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указаны обязательные поля: name, base_price']);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO shooting_types (name, base_price, duration_minutes, description, is_active)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['name'],
                $data['base_price'],
                $data['duration_minutes'] ?? 30,
                $data['description'] ?? null,
                $data['is_active'] ?? 1
            ]);

            echo json_encode([
                'success' => true,
                'id' => $db->lastInsertId(),
                'message' => 'Тип съёмки создан'
            ]);
            break;

        case 'PUT':
            // Обновить тип съёмки
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID']);
                exit;
            }

            $stmt = $db->prepare("
                UPDATE shooting_types
                SET name = ?, base_price = ?, duration_minutes = ?, description = ?, is_active = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $data['name'],
                $data['base_price'],
                $data['duration_minutes'] ?? 30,
                $data['description'] ?? null,
                $data['is_active'] ?? 1,
                $data['id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Тип съёмки обновлён'
            ]);
            break;

        case 'DELETE':
            // Удалить тип съёмки
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID']);
                exit;
            }

            // Проверяем, используется ли тип в записях
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE shooting_type_id = ?");
            $stmt->execute([$_GET['id']]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                http_response_code(409);
                echo json_encode([
                    'error' => 'Невозможно удалить тип съёмки',
                    'message' => "Тип используется в {$result['count']} записях. Деактивируйте вместо удаления."
                ]);
                exit;
            }

            // Удаляем
            $stmt = $db->prepare("DELETE FROM shooting_types WHERE id = ?");
            $stmt->execute([$_GET['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Тип съёмки удалён'
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
