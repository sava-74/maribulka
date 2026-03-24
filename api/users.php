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

// Auth check — only admin, superuser, superuser1 can access users API
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$currentRole = $_SESSION['user']['role'] ?? '';
if (!in_array($currentRole, ['admin', 'superuser', 'superuser1'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$pdo = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? 'list';

// GET ?action=professions — список профессий
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'professions') {
    $stmt = $pdo->query("SELECT id, title FROM profession WHERE active = 1 ORDER BY id");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// GET ?action=list — список пользователей
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $stmt = $pdo->query(
        "SELECT u.id, u.full_name, u.login, u.role, u.id_profession,
                u.is_photographer, u.is_hairdresser, u.is_admin_role,
                u.salary_type, u.hired_at, u.fired_at, u.notes, u.created_at,
                u.region, u.city, u.street, u.house_building, u.flat,
                u.phone_user, u.email_user, u.date_of_birth,
                p.title AS profession_title
         FROM users u
         LEFT JOIN profession p ON u.id_profession = p.id
         ORDER BY u.fired_at IS NOT NULL, u.full_name"
    );
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// POST ?action=create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);

    // admin не может создавать admin, superuser, superuser1
    if ($currentRole === 'admin' && in_array($input['role'] ?? '', ['admin', 'superuser', 'superuser1'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав для создания пользователя данной роли']);
        exit;
    }
    // superuser не может создавать admin
    if ($currentRole === 'superuser' && ($input['role'] ?? '') === 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав для создания пользователя данной роли']);
        exit;
    }
    // superuser1 не может создавать admin, superuser
    if ($currentRole === 'superuser1' && in_array($input['role'] ?? '', ['admin', 'superuser', 'superuser1'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав для создания пользователя данной роли']);
        exit;
    }

    // Нельзя создать superuser если уже есть 1
    $countSU = $pdo->query("SELECT COUNT(*) FROM users WHERE role='superuser' AND fired_at IS NULL")->fetchColumn();
    if (($input['role'] ?? '') === 'superuser' && $countSU >= 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Пользователь с ролью Руководитель уже существует']);
        exit;
    }
    // Нельзя создать superuser1 если уже есть 1
    $countSU1 = $pdo->query("SELECT COUNT(*) FROM users WHERE role='superuser1' AND fired_at IS NULL")->fetchColumn();
    if (($input['role'] ?? '') === 'superuser1' && $countSU1 >= 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Пользователь с ролью Руководитель 2 уже существует']);
        exit;
    }

    // Validate required fields
    if (empty($input['login']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Логин и пароль обязательны']);
        exit;
    }

    // Check login uniqueness (exclude fired users)
    $dup = $pdo->prepare("SELECT id FROM users WHERE login = ? AND fired_at IS NULL");
    $dup->execute([$input['login']]);
    if ($dup->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует']);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, login, password, role, id_profession,
                is_photographer, is_hairdresser, is_admin_role,
                salary_type, hired_at, notes, created_at,
                region, city, street, house_building, flat,
                phone_user, email_user, date_of_birth)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $input['full_name'] ?? null,
            $input['login'],
            $input['password'],
            $input['role'] ?? 'prouser',
            !empty($input['id_profession']) ? (int)$input['id_profession'] : null,
            (int)($input['is_photographer'] ?? 0),
            (int)($input['is_hairdresser'] ?? 0),
            (int)($input['is_admin_role'] ?? 0),
            $input['salary_type'] ?? null,
            $input['hired_at'] ?? null,
            $input['notes'] ?? null,
            $input['region'] ?? null,
            $input['city'] ?? null,
            $input['street'] ?? null,
            $input['house_building'] ?? null,
            !empty($input['flat']) ? (int)$input['flat'] : null,
            $input['phone_user'] ?? null,
            $input['email_user'] ?? null,
            $input['date_of_birth'] ?? null,
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при создании пользователя']);
        }
    }
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

    // Check login uniqueness (exclude fired users and current user)
    $dup = $pdo->prepare("SELECT id FROM users WHERE login = ? AND fired_at IS NULL AND id != ?");
    $dup->execute([$input['login'], $id]);
    if ($dup->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует']);
        exit;
    }

    // Проверяем роль редактируемого пользователя
    $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $check->execute([$id]);
    $target = $check->fetch(PDO::FETCH_ASSOC);
    $targetRole = $target['role'] ?? '';

    // admin не может редактировать admin, superuser, superuser1
    if ($currentRole === 'admin' && in_array($targetRole, ['admin', 'superuser', 'superuser1'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }
    // superuser не может назначить роль admin
    if ($currentRole === 'superuser' && ($input['role'] ?? '') === 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Нельзя назначить роль admin']);
        exit;
    }
    // superuser1 не может редактировать admin и superuser
    if ($currentRole === 'superuser1' && in_array($targetRole, ['admin', 'superuser'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }

    $fields = "full_name=?, login=?, role=?, id_profession=?,
               is_photographer=?, is_hairdresser=?, is_admin_role=?,
               salary_type=?, hired_at=?, notes=?,
               region=?, city=?, street=?, house_building=?, flat=?,
               phone_user=?, email_user=?, date_of_birth=?";
    $params = [
        $input['full_name'] ?? null,
        $input['login'] ?? null,
        $input['role'] ?? 'prouser',
        !empty($input['id_profession']) ? (int)$input['id_profession'] : null,
        (int)($input['is_photographer'] ?? 0),
        (int)($input['is_hairdresser'] ?? 0),
        (int)($input['is_admin_role'] ?? 0),
        $input['salary_type'] ?? null,
        $input['hired_at'] ?? null,
        $input['notes'] ?? null,
        $input['region'] ?? null,
        $input['city'] ?? null,
        $input['street'] ?? null,
        $input['house_building'] ?? null,
        !empty($input['flat']) ? (int)$input['flat'] : null,
        $input['phone_user'] ?? null,
        $input['email_user'] ?? null,
        $input['date_of_birth'] ?? null,
    ];
    // Update password only if provided
    if (!empty($input['password'])) {
        $fields .= ", password=?";
        $params[] = $input['password'];
    }
    $params[] = $id;
    try {
        $pdo->prepare("UPDATE users SET $fields WHERE id = ?")->execute($params);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении пользователя']);
        }
    }
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

    // admin вообще не может уволить никого
    if ($currentRole === 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }

    $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $check->execute([$id]);
    $target = $check->fetch(PDO::FETCH_ASSOC);
    $targetRole = $target['role'] ?? '';

    // никто не может уволить admin или superuser
    if (in_array($targetRole, ['admin', 'superuser'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Нельзя уволить этого пользователя']);
        exit;
    }
    // superuser1 не может уволить superuser1
    if ($currentRole === 'superuser1' && $targetRole === 'superuser1') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Нельзя уволить этого пользователя']);
        exit;
    }

    $pdo->prepare("UPDATE users SET fired_at = CURDATE(), login = NULL, password = NULL WHERE id = ? AND fired_at IS NULL")
        ->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);
