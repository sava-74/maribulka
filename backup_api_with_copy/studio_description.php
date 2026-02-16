<?php
header('Content-Type: application/json; charset=utf-8');

// Определяем Origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'http://localhost:5173',
    'https://xn--80aac1alfd7a3a5g.xn--p1ai',
    'http://xn--80aac1alfd7a3a5g.xn--p1ai'
];

if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'database.php';

$pdo = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// GET - получить описание студии
if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT content, updated_at FROM studio_description ORDER BY id DESC LIMIT 1");
        $description = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($description) {
            echo json_encode([
                'success' => true,
                'data' => $description
            ]);
        } else {
            // Если нет записи - вернуть дефолтное описание
            echo json_encode([
                'success' => true,
                'data' => [
                    'content' => '<p>Добро пожаловать в нашу фотостудию!</p>',
                    'updated_at' => null
                ]
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при получении описания: ' . $e->getMessage()
        ]);
    }
}

// POST - обновить описание студии (только для админа)
elseif ($method === 'POST') {
    // Проверка авторизации
    require_once 'session.php';
    initSession();

    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Доступ запрещён. Требуются права администратора.'
        ]);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['content'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Отсутствует поле content'
        ]);
        exit();
    }

    $content = trim($input['content']);

    if (empty($content)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Описание не может быть пустым'
        ]);
        exit();
    }

    try {
        // Проверяем есть ли уже запись
        $stmt = $pdo->query("SELECT id FROM studio_description LIMIT 1");
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            // Обновляем существующую запись
            $stmt = $pdo->prepare("UPDATE studio_description SET content = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$content, $exists['id']]);
        } else {
            // Создаём новую запись
            $stmt = $pdo->prepare("INSERT INTO studio_description (content) VALUES (?)");
            $stmt->execute([$content]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Описание успешно обновлено'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при обновлении описания: ' . $e->getMessage()
        ]);
    }
}

else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не поддерживается'
    ]);
}
