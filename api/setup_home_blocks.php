<?php
// api/setup_home_blocks.php
// Запускается один раз: GET /api/setup_home_blocks.php
require_once 'session.php';
initSession();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Только для администратора']);
    exit();
}

require_once 'database.php';
$pdo = Database::getInstance()->getConnection();

$sql = file_get_contents(__DIR__ . '/migrations/004_home_blocks.sql');
$pdo->exec($sql);

echo json_encode(['success' => true, 'message' => 'Таблица home_blocks создана']);
