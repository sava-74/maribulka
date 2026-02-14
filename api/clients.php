<?php
/**
 * API для работы с клиентами
 *
 * GET /api/clients.php - получить всех клиентов
 * GET /api/clients.php?id=1 - получить одного клиента
 * GET /api/clients.php?search=Иванов - поиск клиента по имени/телефону
 * POST /api/clients.php - создать нового клиента
 * PUT /api/clients.php - обновить клиента
 * DELETE /api/clients.php?id=1 - удалить клиента
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
            if (isset($_GET['check_relations'])) {
                // Проверить связи клиента с документами
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE client_id = ?");
                $stmt->execute([$_GET['id']]);
                $result = $stmt->fetch();

                echo json_encode($result['count'] > 0);
                exit;
            } elseif (isset($_GET['id'])) {
                // Получить одного клиента
                $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $result = $stmt->fetch();

                if ($result) {
                    echo json_encode($result);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Клиент не найден']);
                }
            } elseif (isset($_GET['search'])) {
                // Поиск клиента по имени или телефону
                $search = '%' . $_GET['search'] . '%';
                $stmt = $db->prepare("
                    SELECT * FROM clients
                    WHERE name LIKE ? OR phone LIKE ?
                    ORDER BY name
                    LIMIT 10
                ");
                $stmt->execute([$search, $search]);
                $result = $stmt->fetchAll();
                echo json_encode($result);
            } else {
                // Получить всех клиентов
                $stmt = $db->query("SELECT * FROM clients ORDER BY name");
                $result = $stmt->fetchAll();
                echo json_encode($result);
            }
            break;

        case 'POST':
            // Создать нового клиента
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['name']) || !isset($data['phone'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указаны обязательные поля: name, phone']);
                exit;
            }

            // Проверяем, не существует ли клиент с таким телефоном
            $stmt = $db->prepare("SELECT id FROM clients WHERE phone = ?");
            $stmt->execute([$data['phone']]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Клиент с таким телефоном уже существует']);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO clients (name, phone, notes)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $data['name'],
                $data['phone'],
                $data['notes'] ?? null
            ]);

            echo json_encode([
                'success' => true,
                'id' => $db->lastInsertId(),
                'message' => 'Клиент создан'
            ]);
            break;

        case 'PUT':
            // Обновить клиента
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID']);
                exit;
            }

            // Проверяем, не занят ли телефон другим клиентом
            $stmt = $db->prepare("SELECT id FROM clients WHERE phone = ? AND id != ?");
            $stmt->execute([$data['phone'], $data['id']]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Телефон уже используется другим клиентом']);
                exit;
            }

            $stmt = $db->prepare("
                UPDATE clients
                SET name = ?, phone = ?, notes = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $data['name'],
                $data['phone'],
                $data['notes'] ?? null,
                $data['id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Клиент обновлён'
            ]);
            break;

        case 'DELETE':
            // Удалить клиента (проверка связей происходит на фронтенде через check_relations)
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID']);
                exit;
            }

            // Удаляем
            $stmt = $db->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->execute([$_GET['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Клиент удалён'
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
