-- Добавить статус 'failed' в ENUM для поля status

ALTER TABLE bookings
MODIFY COLUMN status ENUM('new', 'completed', 'delivered', 'cancelled', 'failed')
DEFAULT 'new';
