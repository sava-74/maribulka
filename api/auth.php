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
// CHECK - проверка текущей сессии
// ============================================
if ($action === 'check') {
    if (isset($_SESSION['user'])) {
        echo json_encode([
            'success' => true,
            'isAuthenticated' => true,
            'user' => [
                'id' => $_SESSION['user']['id'],
                'login' => $_SESSION['user']['login'],
                'role' => $_SESSION['user']['role']
            ]
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
                'role' => $user['role']
            ];

            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'login' => $user['login'],
                    'role' => $user['role']
                ]
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
// Неизвестный action
// ============================================
http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'Неизвестный action. Доступные: check, login, logout'
]);
