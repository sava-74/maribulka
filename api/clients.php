<?php
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
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once 'session.php';
require_once 'database.php';
initSession();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentRole = (int)($_SESSION['user']['role'] ?? 0);
if (!in_array($currentRole, [1, 2, 3, 4])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$pdo = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? 'list';

$canFull = in_array($currentRole, [1, 2, 3]);
$canView = in_array($currentRole, [1, 2, 3, 4]);

// GET ?check_relations=1&id=X
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_relations'])) {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID обязателен']); exit; }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE client_id = ?");
    $stmt->execute([$id]);
    $count = (int)$stmt->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM income WHERE client_id = ?");
        $stmt->execute([$id]);
        $count = (int)$stmt->fetchColumn();
    }
    echo json_encode($count > 0);
    exit;
}

// GET ?action=list
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $stmt = $pdo->query("SELECT * FROM clients ORDER BY name");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// GET ?action=get&id=X
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID обязателен']); exit; }
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Клиент не найден']); exit; }
    echo json_encode(['success' => true, 'data' => $row]);
    exit;
}

// POST ?action=create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    if (!$canFull) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Доступ запрещён']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty(trim($data['name'] ?? ''))) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Укажите имя клиента']); exit; }
    if (empty(trim($data['phone'] ?? ''))) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Укажите телефон']); exit; }
    // Уникальность телефона
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE phone = ?");
    $stmt->execute([trim($data['phone'])]);
    if ($stmt->fetch()) { http_response_code(409); echo json_encode(['success' => false, 'message' => 'Клиент с таким телефоном уже существует']); exit; }
    try {
        $stmt = $pdo->prepare("INSERT INTO clients (name, phone, notes) VALUES (?, ?, ?)");
        $stmt->execute([trim($data['name']), trim($data['phone']), $data['notes'] ?? null]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка БД: ' . $e->getMessage()]);
    }
    exit;
}

// POST ?action=update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    if (!$canFull) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Доступ запрещён']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID обязателен']); exit; }
    if (empty(trim($data['name'] ?? ''))) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Укажите имя клиента']); exit; }
    if (empty(trim($data['phone'] ?? ''))) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Укажите телефон']); exit; }
    // Уникальность телефона (исключая текущего)
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE phone = ? AND id != ?");
    $stmt->execute([trim($data['phone']), $id]);
    if ($stmt->fetch()) { http_response_code(409); echo json_encode(['success' => false, 'message' => 'Телефон уже используется другим клиентом']); exit; }
    try {
        $stmt = $pdo->prepare("UPDATE clients SET name = ?, phone = ?, notes = ? WHERE id = ?");
        $stmt->execute([trim($data['name']), trim($data['phone']), $data['notes'] ?? null, $id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка БД: ' . $e->getMessage()]);
    }
    exit;
}

// DELETE ?action=delete&id=X
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'delete') {
    if (!$canFull) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Доступ запрещён']); exit; }
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID обязателен']); exit; }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE client_id = ?");
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Клиент используется в записях на съёмку']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM income WHERE client_id = ?");
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Клиент используется в платежах']);
        exit;
    }
    try {
        $pdo->prepare("DELETE FROM clients WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка БД: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Неизвестный action']);
