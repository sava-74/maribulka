CREATE TABLE IF NOT EXISTS home_blocks (
  id TINYINT UNSIGNED NOT NULL,
  content LONGTEXT NOT NULL DEFAULT '',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO home_blocks (id, content) VALUES
  (1, '<p>Блок 1</p>'),
  (2, '<p>Блок 2</p>'),
  (3, '<p>Блок 3</p>'),
  (4, '<p>Блок 4</p>');
