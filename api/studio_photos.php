<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// GET - получить все фото
if ($method === 'GET') {
    try {
        $stmt = $pdo->query("
            SELECT id, photo_url, position, created_at
            FROM studio_photos
            ORDER BY position ASC
        ");
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'photos' => $photos
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения фото: ' . $e->getMessage()
        ]);
    }
}

// POST - загрузить новое фото
elseif ($method === 'POST') {
    try {
        // Проверка наличия файла
        if (!isset($_FILES['photo'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Файл не загружен'
            ]);
            exit();
        }

        $file = $_FILES['photo'];
        $position = isset($_POST['position']) ? (int)$_POST['position'] : 0;

        // Валидация файла
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Неверный формат файла. Разрешены: JPG, PNG, WEBP'
            ]);
            exit();
        }

        // Проверка размера (макс 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Размер файла превышает 5 МБ'
            ]);
            exit();
        }

        // Создание директории для фото если не существует
        // Путь к медиа вне dist (чтобы не терялись при деплое)
        $uploadDir = dirname(__DIR__, 3) . '/media/home/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Генерация уникального имени файла
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'studio_' . time() . '_' . uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        // Перемещение файла
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка загрузки файла'
            ]);
            exit();
        }

        // Оптимизация и сжатие изображения
        $imageOptimized = false;
        $mime = mime_content_type($filePath);

        // Создаём изображение из загруженного файла
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = @imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($filePath);
                break;
            case 'image/webp':
                $sourceImage = @imagecreatefromwebp($filePath);
                break;
            default:
                $sourceImage = false;
        }

        if ($sourceImage !== false) {
            // Получаем размеры оригинала
            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Максимальные размеры для студийных фото (достаточно для веба)
            $maxWidth = 1920;
            $maxHeight = 1080;

            // Вычисляем новые размеры с сохранением пропорций
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight, 1);
            $newWidth = (int)($originalWidth * $ratio);
            $newHeight = (int)($originalHeight * $ratio);

            // Создаём новое изображение
            $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Для PNG сохраняем прозрачность
            if ($mime === 'image/png') {
                imagealphablending($optimizedImage, false);
                imagesavealpha($optimizedImage, true);
                $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
                imagefilledrectangle($optimizedImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Ресемплинг (качественное масштабирование)
            imagecopyresampled(
                $optimizedImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );

            // Сохраняем оптимизированное изображение
            switch ($mime) {
                case 'image/jpeg':
                case 'image/jpg':
                    $imageOptimized = imagejpeg($optimizedImage, $filePath, 85); // Качество 85%
                    break;
                case 'image/png':
                    $imageOptimized = imagepng($optimizedImage, $filePath, 6); // Компрессия 6 (0-9)
                    break;
                case 'image/webp':
                    $imageOptimized = imagewebp($optimizedImage, $filePath, 85); // Качество 85%
                    break;
            }

            // Освобождаем память
            imagedestroy($sourceImage);
            imagedestroy($optimizedImage);
        }

        // Устанавливаем правильные права доступа (644) для веб-сервера
        chmod($filePath, 0644);

        // URL для доступа к фото
        $photoUrl = '/media/home/' . $fileName;

        // Удаление старого фото на этой позиции (если есть)
        $stmt = $pdo->prepare("SELECT id, photo_url FROM studio_photos WHERE position = ?");
        $stmt->execute([$position]);
        $oldPhoto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($oldPhoto) {
            // Удаляем старый файл
            $oldFilePath = dirname(__DIR__, 3) . str_replace('/media', '/media', $oldPhoto['photo_url']);
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
            // Обновляем запись
            $stmt = $pdo->prepare("UPDATE studio_photos SET photo_url = ?, created_at = NOW() WHERE position = ?");
            $stmt->execute([$photoUrl, $position]);
        } else {
            // Создаём новую запись
            $stmt = $pdo->prepare("INSERT INTO studio_photos (photo_url, position, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$photoUrl, $position]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Фото успешно загружено',
            'photo_url' => $photoUrl,
            'position' => $position
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка базы данных: ' . $e->getMessage()
        ]);
    }
}

// DELETE - удалить фото
elseif ($method === 'DELETE') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $position = isset($data['position']) ? (int)$data['position'] : null;

        if ($position === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Не указана позиция фото'
            ]);
            exit();
        }

        // Получаем фото для удаления файла
        $stmt = $pdo->prepare("SELECT photo_url FROM studio_photos WHERE position = ?");
        $stmt->execute([$position]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$photo) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Фото не найдено'
            ]);
            exit();
        }

        // Удаляем файл
        $filePath = dirname(__DIR__, 3) . str_replace('/media', '/media', $photo['photo_url']);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Удаляем запись из БД
        $stmt = $pdo->prepare("DELETE FROM studio_photos WHERE position = ?");
        $stmt->execute([$position]);

        echo json_encode([
            'success' => true,
            'message' => 'Фото успешно удалено'
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка удаления: ' . $e->getMessage()
        ]);
    }
}

else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не поддерживается'
    ]);
}
