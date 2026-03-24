<?php
// api/permissions.php — управление индивидуальными правами (только для admin)
header('Content-Type: application/json; charset=utf-8');

// Определяем Origin для CORS
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

// Обработка preflight запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once 'session.php';
require_once 'database.php';
initSession();

// Only admin can manage permissions
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Только admin может управлять правами']);
    exit;
}

$pdo = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';

// GET ?action=get&user_id=5 — get all permission overrides for a user
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $userId = (int)($_GET['user_id'] ?? 0);
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id обязателен']);
        exit;
    }
    $stmt = $pdo->prepare(
        "SELECT section, action, allowed FROM user_permissions WHERE user_id = ? ORDER BY section, action"
    );
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = array_map(fn($r) => [
        'section' => $r['section'],
        'action'  => $r['action'],
        'allowed' => (bool)$r['allowed'],
    ], $rows);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// POST ?action=set — upsert a single permission override
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'set') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['user_id']) || empty($input['section']) || empty($input['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id, section и action обязательны']);
        exit;
    }
    $checkRole = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $checkRole->execute([(int)$input['user_id']]);
    $targetUser = $checkRole->fetch(PDO::FETCH_ASSOC);
    if (in_array($targetUser['role'] ?? '', ['admin', 'superuser'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Права этого пользователя не изменяемы']);
        exit;
    }
    $stmt = $pdo->prepare(
        "INSERT INTO user_permissions (user_id, section, action, allowed, granted_by)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE allowed = VALUES(allowed), granted_by = VALUES(granted_by)"
    );
    $stmt->execute([
        (int)$input['user_id'],
        $input['section'],
        $input['action'],
        (int)(bool)$input['allowed'],
        $_SESSION['user']['id'],
    ]);
    echo json_encode(['success' => true]);
    exit;
}

// POST ?action=delete — remove a single permission override (revert to role default)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['user_id']) || empty($input['section']) || empty($input['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id, section и action обязательны']);
        exit;
    }
    $checkRole = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $checkRole->execute([(int)$input['user_id']]);
    $targetUser = $checkRole->fetch(PDO::FETCH_ASSOC);
    if (in_array($targetUser['role'] ?? '', ['admin', 'superuser'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Права этого пользователя не изменяемы']);
        exit;
    }
    $pdo->prepare(
        "DELETE FROM user_permissions WHERE user_id = ? AND section = ? AND action = ?"
    )->execute([(int)$input['user_id'], $input['section'], $input['action']]);
    echo json_encode(['success' => true]);
    exit;
}

// POST ?action=reset — remove ALL permission overrides for a user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reset') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = (int)($input['user_id'] ?? 0);
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id обязателен']);
        exit;
    }
    $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?")
        ->execute([$userId]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);
