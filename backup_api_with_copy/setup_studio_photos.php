<?php
require_once 'database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS studio_photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        photo_url VARCHAR(255) NOT NULL,
        position INT NOT NULL UNIQUE COMMENT 'Позиция фото (0-3)',
        created_at DATETIME NOT NULL,
        INDEX idx_position (position)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $pdo->exec($sql);
    echo "✅ Таблица studio_photos успешно создана!\n";
} catch (PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
