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
if (!in_array($currentRole, [1, 2, 3])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$pdo = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? 'list';

// GET ?action=professions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'professions') {
    $stmt = $pdo->query("SELECT id, title FROM profession WHERE active = 1 ORDER BY id");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// GET ?action=salary_types
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'salary_types') {
    $stmt = $pdo->query("SELECT id, title FROM salary_type ORDER BY id");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// GET ?action=permissions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'permissions') {
    $stmt = $pdo->query("SELECT id, name FROM user_permissions WHERE active = 1 ORDER BY id");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// GET ?action=get&id=X
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID обязателен']);
        exit;
    }
    $stmt = $pdo->prepare(
        "SELECT u.id, u.full_name, u.login, u.role, u.id_profession,
                u.is_photographer, u.is_hairdresser, u.is_admin_role,
                u.id_salary_type, u.hired_at, u.fired_at, u.notes, u.created_at,
                u.region, u.city, u.street, u.house_building, u.flat,
                u.phone_user, u.email_user, u.date_of_birth,
                p.title AS profession_title,
                st.title AS salary_type_title,
                ur.name AS permission_name
         FROM users u
         LEFT JOIN profession p ON u.id_profession = p.id
         LEFT JOIN salary_type st ON u.id_salary_type = st.id
         LEFT JOIN user_permissions ur ON u.role = ur.id
         WHERE u.id = ?"
    );
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $user]);
    exit;
}

// GET ?action=list
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $stmt = $pdo->query(
        "SELECT u.id, u.full_name, u.login, u.role, u.id_profession,
                u.is_photographer, u.is_hairdresser, u.is_admin_role,
                u.id_salary_type, u.hired_at, u.fired_at, u.notes, u.created_at,
                u.region, u.city, u.street, u.house_building, u.flat,
                u.phone_user, u.email_user, u.date_of_birth,
                p.title AS profession_title,
                st.title AS salary_type_title,
                ur.name AS permission_name
         FROM users u
         LEFT JOIN profession p ON u.id_profession = p.id
         LEFT JOIN salary_type st ON u.id_salary_type = st.id
         LEFT JOIN user_permissions ur ON u.role = ur.id
         ORDER BY u.fired_at IS NOT NULL, u.full_name"
    );
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// POST ?action=create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);

    $inputRole = (int)($input['role'] ?? 0);
    if (empty($inputRole)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Укажите роль пользователя']);
        exit;
    }

    // admin(1) не может создавать 1,2,3
    if ($currentRole === 1 && in_array($inputRole, [1, 2, 3])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }
    // superuser(2) не может создавать admin(1)
    if ($currentRole === 2 && $inputRole === 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }
    // superuser1(3) не может создавать 1,2,3
    if ($currentRole === 3 && in_array($inputRole, [1, 2, 3])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }

    // superuser(2) — только 1 активный
    $countSU = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 2 AND fired_at IS NULL")->fetchColumn();
    if ($inputRole === 2 && $countSU >= 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Директор уже существует']);
        exit;
    }
    // superuser1(3) — только 1 активный
    $countSU1 = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 3 AND fired_at IS NULL")->fetchColumn();
    if ($inputRole === 3 && $countSU1 >= 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Руководитель уже существует']);
        exit;
    }

    if (empty($input['login']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Логин и пароль обязательны']);
        exit;
    }

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
                id_salary_type, hired_at, notes, created_at,
                region, city, street, house_building, flat,
                phone_user, email_user, date_of_birth)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $input['full_name'] ?? null,
            $input['login'],
            password_hash($input['password'], PASSWORD_BCRYPT),
            $inputRole ?: 5,
            !empty($input['id_profession']) ? (int)$input['id_profession'] : null,
            (int)($input['is_photographer'] ?? 0),
            (int)($input['is_hairdresser'] ?? 0),
            (int)($input['is_admin_role'] ?? 0),
            !empty($input['id_salary_type']) ? (int)$input['id_salary_type'] : null,
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

    $dup = $pdo->prepare("SELECT id FROM users WHERE login = ? AND fired_at IS NULL AND id != ?");
    $dup->execute([$input['login'], $id]);
    if ($dup->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует']);
        exit;
    }

    $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $check->execute([$id]);
    $target = $check->fetch(PDO::FETCH_ASSOC);
    $targetRole = (int)($target['role'] ?? 0);

    // admin(1) не может редактировать 1,2,3
    if ($currentRole === 1 && in_array($targetRole, [1, 2, 3])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }
    // superuser1(3) не может редактировать 1,2
    if ($currentRole === 3 && in_array($targetRole, [1, 2])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }

    // Для вечных (1,2) роль не меняется
    $roleToSave = in_array($targetRole, [1, 2]) ? $targetRole : ((int)($input['role'] ?? $targetRole) ?: $targetRole);

    $fields = "full_name=?, login=?, role=?, id_profession=?,
               is_photographer=?, is_hairdresser=?, is_admin_role=?,
               id_salary_type=?, hired_at=?, notes=?,
               region=?, city=?, street=?, house_building=?, flat=?,
               phone_user=?, email_user=?, date_of_birth=?";
    $params = [
        $input['full_name'] ?? null,
        $input['login'] ?? null,
        $roleToSave,
        !empty($input['id_profession']) ? (int)$input['id_profession'] : null,
        (int)($input['is_photographer'] ?? 0),
        (int)($input['is_hairdresser'] ?? 0),
        (int)($input['is_admin_role'] ?? 0),
        !empty($input['id_salary_type']) ? (int)$input['id_salary_type'] : null,
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
    if (!empty($input['password'])) {
        $fields .= ", password=?";
        $params[] = password_hash($input['password'], PASSWORD_BCRYPT);
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

// POST ?action=fire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'fire') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID обязателен']);
        exit;
    }

    // admin(1) не может уволить никого
    if ($currentRole === 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }

    $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $check->execute([$id]);
    $target = $check->fetch(PDO::FETCH_ASSOC);
    $targetRole = (int)($target['role'] ?? 0);

    // нельзя уволить admin(1) или superuser(2)
    if (in_array($targetRole, [1, 2])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Нельзя уволить этого пользователя']);
        exit;
    }
    // superuser1(3) не может уволить superuser1(3)
    if ($currentRole === 3 && $targetRole === 3) {
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
