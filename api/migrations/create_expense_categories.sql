-- Таблица для справочника категорий расходов
CREATE TABLE IF NOT EXISTS expense_categories (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL COMMENT 'Название категории',
  is_active TINYINT(1) DEFAULT 1 COMMENT 'Активна ли категория',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Дефолтные категории расходов
INSERT INTO expense_categories (name) VALUES
('Материалы'),
('Аренда студии'),
('Транспорт'),
('Программное обеспечение'),
('Оборудование'),
('Возврат средств'),
('Прочее');
