<?php
// api/home_blocks.php
header('Content-Type: application/json; charset=utf-8');

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
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once 'database.php';
$pdo = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// GET ?id=1 — получить блок
if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id < 1 || $id > 4) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id должен быть от 1 до 4']);
        exit();
    }
    $stmt = $pdo->prepare("SELECT content FROM home_blocks WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'content' => $row['content'] ?? '']);
}

// POST { id: 1, content: "..." } — сохранить блок (только admin)
elseif ($method === 'POST') {
    require_once 'session.php';
    initSession();
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    $content = trim($input['content'] ?? '');

    if ($id < 1 || $id > 4) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id должен быть от 1 до 4']);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE home_blocks SET content = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$content, $id]);

    echo json_encode(['success' => true, 'message' => 'Сохранено']);
}

else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
}
