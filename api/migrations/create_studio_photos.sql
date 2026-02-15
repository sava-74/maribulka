-- Таблица для фото студии на главной странице
CREATE TABLE IF NOT EXISTS studio_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    photo_url VARCHAR(255) NOT NULL,
    position INT NOT NULL UNIQUE COMMENT 'Позиция фото (0-3)',
    created_at DATETIME NOT NULL,
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ограничение: максимум 4 фото
-- Позиции: 0, 1, 2, 3
