<?php
// api/upload-image.php
// CKEditor SimpleUploadAdapter ожидает: POST multipart с полем "upload"
// Возвращает: { "url": "..." } при успехе или { "error": { "message": "..." } }
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

// Только для администратора
require_once 'session.php';
initSession();
if (!isset($_SESSION['user']) || (int)($_SESSION['user']['role'] ?? 0) !== 1) {
    http_response_code(403);
    echo json_encode(['error' => ['message' => 'Доступ запрещён']]);
    exit();
}

if (!isset($_FILES['upload'])) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'Файл не передан']]);
    exit();
}

$file = $_FILES['upload'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['error' => ['message' => 'Недопустимый тип файла']]);
    exit();
}
if ($file['size'] > $maxSize) {
    echo json_encode(['error' => ['message' => 'Файл слишком большой (максимум 5MB)']]);
    exit();
}

// Папка: /media/editor/YYYY-MM/
$subDir = date('Y-m');
$uploadDir = '/home/s/sava7424/maribulka.rf/media/editor/' . $subDir . '/';
$localUploadDir = __DIR__ . '/../media/editor/' . $subDir . '/'; // для локальной разработки

// Используем правильный путь в зависимости от окружения
$targetDir = is_dir('/home/s/sava7424') ? $uploadDir : $localUploadDir;

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('img_', true) . '.' . strtolower($ext);
$targetPath = $targetDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['error' => ['message' => 'Ошибка сохранения файла']]);
    exit();
}

$url = '/media/editor/' . $subDir . '/' . $filename;
echo json_encode(['url' => $url]);
