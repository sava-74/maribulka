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

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Получаем JSON из тела запроса
$input = json_decode(file_get_contents('php://input'), true);
$login = $input['login'] ?? '';
$password = $input['password'] ?? '';

// Подключение к MySQL
// TODO: Переместить в .env файл для безопасности
$host = 'localhost';
$dbname = 'sava7424_mari';
$dbuser = 'sava7424_mari';
$dbpass = 'Zxc456Siti';  // ⚠️ Замени на реальный пароль на сервере!

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Проверяем пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ? AND password = ?");
    $stmt->execute([$login, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Создаём сессию
        session_start();
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
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
