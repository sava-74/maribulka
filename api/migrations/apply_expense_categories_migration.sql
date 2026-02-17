-- ============================================
-- МИГРАЦИЯ: Справочник категорий расходов
-- Дата: 17.02.2026
-- ============================================

-- Шаг 1: Создать таблицу expense_categories
CREATE TABLE IF NOT EXISTS expense_categories (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL COMMENT 'Название категории',
  is_active TINYINT(1) DEFAULT 1 COMMENT 'Активна ли категория',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Шаг 2: Добавить дефолтные категории
INSERT INTO expense_categories (name) VALUES
('Материалы'),
('Аренда студии'),
('Транспорт'),
('Программное обеспечение'),
('Оборудование'),
('Возврат средств'),
('Прочее');

-- Шаг 3: Создать временную таблицу маппинга
CREATE TEMPORARY TABLE category_mapping (
  old_value VARCHAR(50),
  new_id INT
);

INSERT INTO category_mapping (old_value, new_id) VALUES
('materials', 1),
('rent', 2),
('transport', 3),
('software', 4),
('equipment', 5),
('refund', 6),
('other', 7);

-- Шаг 4: Добавить новую колонку category_id
ALTER TABLE expenses ADD COLUMN category_id INT(11) AFTER category;

-- Шаг 5: Перенести данные из ENUM в INT
UPDATE expenses e
INNER JOIN category_mapping cm ON e.category = cm.old_value
SET e.category_id = cm.new_id;

-- Шаг 6: Удалить старую ENUM колонку
ALTER TABLE expenses DROP COLUMN category;

-- Шаг 7: Переименовать новую колонку в category
ALTER TABLE expenses CHANGE category_id category INT(11) NOT NULL;

-- Шаг 8: Добавить внешний ключ
ALTER TABLE expenses
ADD CONSTRAINT fk_expense_category
FOREIGN KEY (category) REFERENCES expense_categories(id);

-- ============================================
-- ГОТОВО! Миграция применена
-- ============================================
