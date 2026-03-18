<?php
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
initSession();

$action = $_GET['action'] ?? '';

// ============================================
// Вспомогательная функция: формирует массив пользователя для ответа
// Читает специализации из записи users и персональные права из user_permissions
// ============================================
function buildUserResponse(array $user, PDO $pdo): array {
    $specializations = [
        'photographer' => (bool)($user['is_photographer'] ?? false),
        'hairdresser'  => (bool)($user['is_hairdresser'] ?? false),
        'admin_role'   => (bool)($user['is_admin_role'] ?? false),
    ];

    $stmt = $pdo->prepare(
        "SELECT section, action, allowed FROM user_permissions WHERE user_id = ?"
    );
    $stmt->execute([$user['id']]);
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $perms = array_map(fn($p) => [
        'section' => $p['section'],
        'action'  => $p['action'],
        'allowed' => (bool)$p['allowed'],
    ], $permissions);

    return [
        'id'              => $user['id'],
        'login'           => $user['login'],
        'name'            => $user['full_name'] ?? $user['name'] ?? '',
        'role'            => $user['role'] ?? 'prouser',
        'specializations' => $specializations,
        'permissions'     => $perms,
    ];
}

// ============================================
// CHECK - проверка текущей сессии
// ============================================
if ($action === 'check') {
    if (isset($_SESSION['user'])) {
        $rememberMe = $_SESSION['remember_me'] ?? false;
        $lastPing = $_SESSION['last_ping'] ?? time();
        // Если не "запомнить" и последний пинг > 90 сек назад — сессия протухла
        if (!$rememberMe && (time() - $lastPing > 90)) {
            $_SESSION = [];
            session_destroy();
            echo json_encode(['success' => true, 'isAuthenticated' => false]);
            exit;
        }
        require_once 'database.php';
        $pdo = Database::getInstance()->getConnection();
        echo json_encode([
            'success' => true,
            'isAuthenticated' => true,
            'rememberMe' => $rememberMe,
            'user' => buildUserResponse($_SESSION['user_full'] ?? $_SESSION['user'], $pdo)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'isAuthenticated' => false
        ]);
    }
    exit;
}

// ============================================
// LOGIN - вход в систему
// ============================================
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $login = $input['login'] ?? '';
    $password = $input['password'] ?? '';

    // Подключение к MySQL
    require_once 'database.php';
    $pdo = Database::getInstance()->getConnection();

    try {
        // Проверяем пользователя
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ? AND password = ?");
        $stmt->execute([$login, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Создаём сессию
            $_SESSION['user'] = [
                'id' => $user['id'],
                'login' => $user['login'],
                'role' => $user['role'],
                'name' => $user['name'] ?? ''
            ];
            $_SESSION['user_full'] = $user; // полная запись из БД для buildUserResponse
            $_SESSION['remember_me'] = (bool)($input['rememberMe'] ?? false);
            $_SESSION['last_ping'] = time();

            echo json_encode([
                'success' => true,
                'user' => buildUserResponse($user, $pdo)
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Неверный логин или пароль']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// LOGOUT - выход из системы
// ============================================
if ($action === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Уничтожаем сессию
    $_SESSION = [];
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Вы успешно вышли из системы'
    ]);
    exit;
}

// ============================================
// PING - heartbeat для сессий без "запомнить"
// ============================================
if ($action === 'ping') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    if (isset($_SESSION['user'])) {
        $_SESSION['last_ping'] = time();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No session']);
    }
    exit;
}

// ============================================
// Неизвестный action
// ============================================
http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'Неизвестный action. Доступные: check, login, logout'
]);
