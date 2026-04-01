<?php
// api/salary-types.php
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

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
// Проверка прав: admin (1), superuser (2) - FULL, superuser1 (3), auser (4) - VIEW
if (!in_array($currentRole, [1, 2, 3, 4])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$pdo = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? 'list';

// Проверка прав для действий
$canFull = in_array($currentRole, [1, 2]); // admin, superuser
$canView = in_array($currentRole, [1, 2, 3, 4]); // все + superuser1, auser

// GET ?action=list - получить все типы зарплат
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    if (!$canView) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
        exit;
    }
    
    $stmt = $pdo->query("SELECT * FROM salary_type ORDER BY id");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

// GET ?action=get&id=X - получить один тип зарплаты
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    if (!$canView) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
        exit;
    }
    
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID обязателен']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM salary_type WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Тип зарплаты не найден']);
        exit;
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

// GET ?action=check_relations&id=X - проверить связи перед удалением
if (isset($_GET['check_relations']) && isset($_GET['id'])) {
    if (!$canView) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
        exit;
    }
    
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID обязателен']);
        exit;
    }
    
    // Проверка: используется ли тип зарплаты в пользователях
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE id_salary_type = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Возвращаем true если есть связи, false если нет
    echo json_encode($result['count'] > 0);
    exit;
}

// POST ?action=create - создать тип зарплаты
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    if (!$canFull) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Проверка обязательного поля title
    if (!isset($data['title']) || empty(trim($data['title']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Поле title обязательно']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO salary_type (
                title,
                monthly_salary, salary_value,
                percentage_of_the_order, the_percentage_value,
                interest_dividends, value_dividend_percentages,
                fixed_order, fixed_value_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            trim($data['title']),
            (int)($data['monthly_salary'] ?? 0),
            (int)($data['salary_value'] ?? 0),
            (int)($data['percentage_of_the_order'] ?? 0),
            (int)($data['the_percentage_value'] ?? 0),
            (int)($data['interest_dividends'] ?? 0),
            (int)($data['value_dividend_percentages'] ?? 0),
            (int)($data['fixed_order'] ?? 0),
            (int)($data['fixed_value_order'] ?? 0)
        ]);
        
        echo json_encode([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'message' => 'Тип зарплаты создан'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка БД: ' . $e->getMessage()]);
    }
    exit;
}

// PUT ?action=update - обновить тип зарплаты
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $action === 'update') {
    if (!$canFull) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !(int)$data['id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID обязателен']);
        exit;
    }
    
    if (!isset($data['title']) || empty(trim($data['title']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Поле title обязательно']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE salary_type SET
                title = ?,
                monthly_salary = ?, salary_value = ?,
                percentage_of_the_order = ?, the_percentage_value = ?,
                interest_dividends = ?, value_dividend_percentages = ?,
                fixed_order = ?, fixed_value_order = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            trim($data['title']),
            (int)($data['monthly_salary'] ?? 0),
            (int)($data['salary_value'] ?? 0),
            (int)($data['percentage_of_the_order'] ?? 0),
            (int)($data['the_percentage_value'] ?? 0),
            (int)($data['interest_dividends'] ?? 0),
            (int)($data['value_dividend_percentages'] ?? 0),
            (int)($data['fixed_order'] ?? 0),
            (int)($data['fixed_value_order'] ?? 0),
            (int)$data['id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Тип зарплаты обновлён'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка БД: ' . $e->getMessage()]);
    }
    exit;
}

// DELETE ?action=delete&id=X - удалить тип зарплаты
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'delete') {
    if (!$canFull) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
        exit;
    }
    
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID обязателен']);
        exit;
    }
    
    // Проверка связей перед удалением
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE id_salary_type = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Невозможно удалить тип зарплаты',
            'message' => "Тип зарплаты используется у {$result['count']} сотрудников. Сначала измените им тип зарплаты."
        ]);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM salary_type WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Тип зарплаты удалён'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка БД: ' . $e->getMessage()]);
    }
    exit;
}

// Если action не распознан
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Неизвестный action']);
