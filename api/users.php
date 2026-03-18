<?php
// api/users.php
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

// Auth check — only admin and superuser can access users API
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$currentRole = $_SESSION['user']['role'] ?? '';
if (!in_array($currentRole, ['admin', 'superuser'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$pdo = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? 'list';

// GET ?action=list — список активных пользователей (fired_at IS NULL)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $stmt = $pdo->query(
        "SELECT id, full_name, login, role,
                is_photographer, is_hairdresser, is_admin_role,
                salary_type, hired_at, fired_at, notes, created_at
         FROM users
         ORDER BY fired_at IS NOT NULL, full_name"
    );
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// POST ?action=create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);

    // SuperUser cannot create admin or superuser accounts
    if ($currentRole === 'superuser' && in_array($input['role'] ?? '', ['admin', 'superuser'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав для создания пользователя данной роли']);
        exit;
    }

    // Validate required fields
    if (empty($input['login']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Логин и пароль обязательны']);
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, login, password, role,
            is_photographer, is_hairdresser, is_admin_role,
            salary_type, hired_at, notes, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->execute([
        $input['full_name'] ?? null,
        $input['login'],
        $input['password'],
        $input['role'] ?? 'prouser',
        (int)($input['is_photographer'] ?? 0),
        (int)($input['is_hairdresser'] ?? 0),
        (int)($input['is_admin_role'] ?? 0),
        $input['salary_type'] ?? null,
        $input['hired_at'] ?? null,
        $input['notes'] ?? null,
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// POST ?action=update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID обязателен']);
        exit;
    }

    if (empty($input['login'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Логин обязателен']);
        exit;
    }

    // SuperUser cannot edit admin or superuser accounts
    if ($currentRole === 'superuser') {
        $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $check->execute([$id]);
        $target = $check->fetch(PDO::FETCH_ASSOC);
        if ($target && in_array($target['role'], ['admin', 'superuser'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Недостаточно прав для редактирования']);
            exit;
        }
    }

    $fields = "full_name=?, login=?, role=?,
               is_photographer=?, is_hairdresser=?, is_admin_role=?,
               salary_type=?, hired_at=?, notes=?";
    $params = [
        $input['full_name'] ?? null,
        $input['login'] ?? null,
        $input['role'] ?? 'prouser',
        (int)($input['is_photographer'] ?? 0),
        (int)($input['is_hairdresser'] ?? 0),
        (int)($input['is_admin_role'] ?? 0),
        $input['salary_type'] ?? null,
        $input['hired_at'] ?? null,
        $input['notes'] ?? null,
    ];
    // Update password only if provided
    if (!empty($input['password'])) {
        $fields .= ", password=?";
        $params[] = $input['password'];
    }
    $params[] = $id;
    $pdo->prepare("UPDATE users SET $fields WHERE id = ?")->execute($params);
    echo json_encode(['success' => true]);
    exit;
}

// POST ?action=fire — irreversible, sets fired_at to today
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'fire') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID обязателен']);
        exit;
    }
    $pdo->prepare("UPDATE users SET fired_at = CURDATE() WHERE id = ? AND fired_at IS NULL")
        ->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);
