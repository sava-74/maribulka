<?php
require_once 'database.php';
header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT MAX(id) as max_id FROM bookings");
$result = $stmt->fetch();
$nextId = ($result['max_id'] ?? 0) + 1;

echo json_encode([
    'next_id' => $nextId,
    'debug' => [
        'max_id' => $result['max_id'],
        'GET' => $_GET,
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD']
    ]
]);
