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

$canFull = in_array($currentRole, [1, 2]);
$canView = in_array($currentRole, [1, 2, 3, 4]);

// GET ?action=list
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $stmt = $pdo->query("SELECT * FROM promotions ORDER BY name");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// GET ?action=get&id=X
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID обязателен']); exit; }
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Акция не найдена']); exit; }
    echo json_encode(['success' => true, 'data' => $row]);
    exit;
}

// GET ?check_relations=1&id=X
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_relations'])) {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID обязателен']); exit; }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE promotion_id = ?");
    $stmt->execute([$id]);
    echo json_encode((int)$stmt->fetchColumn() > 0);
    exit;
}

// POST ?action=create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    if (!$canFull) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Доступ запрещён']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty(trim($data['name'] ?? ''))) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Укажите название акции']); exit; }
    if (!isset($data['discount_percent']) || (float)$data['discount_percent'] <= 0 || (float)$data['discount_percent'] > 100) {
        http_response_code(400); echo json_encode(['success' => false, 'message' => 'Процент скидки должен быть от 0 до 100']); exit;
    }
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;
    // Проверка пересечений (только для срочных)
    if ($start_date && $end_date) {
        $stmt = $pdo->prepare("SELECT id, name FROM promotions WHERE is_active = 1 AND start_date IS NOT NULL AND end_date IS NOT NULL AND (? <= end_date AND ? >= start_date)");
        $stmt->execute([$start_date, $end_date]);
        $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($conflict) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => "Период пересекается с акцией «{$conflict['name']}»"]);
            exit;
        }
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO promotions (name, discount_percent, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([trim($data['name']), (float)$data['discount_percent'], $start_date ?: null, $end_date ?: null, (int)($data['is_active'] ?? 1)]);
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
    if (empty(trim($data['name'] ?? ''))) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Укажите название акции']); exit; }
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;
    if ($start_date && $end_date) {
        $stmt = $pdo->prepare("SELECT id, name FROM promotions WHERE is_active = 1 AND id != ? AND start_date IS NOT NULL AND end_date IS NOT NULL AND (? <= end_date AND ? >= start_date)");
        $stmt->execute([$id, $start_date, $end_date]);
        $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($conflict) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => "Период пересекается с акцией «{$conflict['name']}»"]);
            exit;
        }
    }
    try {
        $stmt = $pdo->prepare("UPDATE promotions SET name = ?, discount_percent = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?");
        $stmt->execute([trim($data['name']), (float)($data['discount_percent'] ?? 0), $start_date ?: null, $end_date ?: null, (int)($data['is_active'] ?? 1), $id]);
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
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE promotion_id = ?");
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Акция используется в записях на съёмку']);
        exit;
    }
    try {
        $pdo->prepare("DELETE FROM promotions WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка БД: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Неизвестный action']);
