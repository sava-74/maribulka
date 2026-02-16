-- Добавление поля "Примечания" в таблицу bookings
ALTER TABLE bookings
ADD COLUMN notes TEXT NULL
AFTER cancel_reason;
