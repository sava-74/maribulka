-- Миграция: Изменение expenses.category из ENUM в INT с внешним ключом

-- Шаг 1: Создать временную таблицу для маппинга старых ENUM значений на новые ID
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

-- Шаг 2: Добавить новую колонку category_id
ALTER TABLE expenses ADD COLUMN category_id INT(11) AFTER category;

-- Шаг 3: Перенести данные из старой колонки в новую
UPDATE expenses e
INNER JOIN category_mapping cm ON e.category = cm.old_value
SET e.category_id = cm.new_id;

-- Шаг 4: Удалить старую ENUM колонку
ALTER TABLE expenses DROP COLUMN category;

-- Шаг 5: Переименовать новую колонку в category
ALTER TABLE expenses CHANGE category_id category INT(11) NOT NULL;

-- Шаг 6: Добавить внешний ключ на expense_categories
ALTER TABLE expenses
ADD CONSTRAINT fk_expense_category
FOREIGN KEY (category) REFERENCES expense_categories(id);

-- Готово! Теперь expenses.category ссылается на expense_categories.id
