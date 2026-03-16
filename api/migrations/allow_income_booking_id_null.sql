-- Разрешить NULL в колонке booking_id таблицы income
-- Нужно для записей взносов в кассу без привязки к заказу

ALTER TABLE income MODIFY COLUMN booking_id INT NULL;
