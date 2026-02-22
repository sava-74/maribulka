-- =====================================================
-- МИГРАЦИЯ БД: Бизнес-процесс заказа фотосессии
-- =====================================================
-- Дата: 22.02.2026
-- Автор: Claude Sonnet 4.5
-- Описание: Добавление новых статусов, возвратов, мультипользователя
-- =====================================================

-- =====================================================
-- 1. ТАБЛИЦА BOOKINGS
-- =====================================================

-- 1.1 Обновить ENUM статусов (8 статусов)
ALTER TABLE bookings
MODIFY COLUMN status ENUM(
    'new',
    'in_progress',
    'completed',
    'completed_partially',
    'not_completed',
    'cancelled_by_photographer',
    'cancelled_by_client',
    'client_no_show'
) NOT NULL DEFAULT 'new'
COMMENT 'Статус заказа';

-- 1.2 Добавить блокировку редактирования
ALTER TABLE bookings
ADD COLUMN is_locked TINYINT(1) DEFAULT 0
COMMENT 'Блокировка редактирования после завершения заказа'
AFTER status;

-- 1.3 Добавить мастера (фотограф, выполняющий заказ)
ALTER TABLE bookings
ADD COLUMN master_id INT NULL
COMMENT 'Фотограф (мастер), выполняющий заказ'
AFTER client_id;

-- 1.4 Добавить аудит (кто создал/обновил)
ALTER TABLE bookings
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER updated_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 2. ТАБЛИЦА EXPENSES
-- =====================================================

-- 2.1 Добавить связь с заказом (для возвратов)
ALTER TABLE expenses
ADD COLUMN booking_id INT NULL
COMMENT 'ID заказа (только для возвратов, category_id=2)'
AFTER category_id,
ADD CONSTRAINT fk_expenses_booking
    FOREIGN KEY (booking_id)
    REFERENCES bookings(id)
    ON DELETE SET NULL;

-- 2.2 Добавить тип возврата
ALTER TABLE expenses
ADD COLUMN refund_type ENUM('prepayment', 'partial', 'full') NULL
COMMENT 'Тип возврата: prepayment (предоплата), partial (частичный), full (полный)'
AFTER booking_id;

-- 2.3 Добавить аудит
ALTER TABLE expenses
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 3. ТАБЛИЦА INCOME
-- =====================================================

-- 3.1 Добавить аудит
ALTER TABLE income
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 4. ТАБЛИЦА EXPENSE_CATEGORIES
-- =====================================================

-- 4.1 Добавить аудит
ALTER TABLE expense_categories
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 5. ТАБЛИЦА CLIENTS
-- =====================================================

-- 5.1 Добавить аудит
ALTER TABLE clients
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 6. ТАБЛИЦА SHOOTING_TYPES
-- =====================================================

-- 6.1 Добавить аудит
ALTER TABLE shooting_types
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 7. ТАБЛИЦА PROMOTIONS
-- =====================================================

-- 7.1 Добавить аудит
ALTER TABLE promotions
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 8. VIEW v_booking_profit
-- =====================================================

-- 8.1 Удалить старый VIEW если существует
DROP VIEW IF EXISTS v_booking_profit;

-- 8.2 Создать VIEW для расчёта прибыли
CREATE VIEW v_booking_profit AS
SELECT
    b.id as booking_id,
    b.status,
    b.shooting_date,
    b.total_amount,
    COALESCE(SUM(i.amount), 0) as gross_income,
    COALESCE(SUM(e.amount), 0) as refund_amount,
    (COALESCE(SUM(i.amount), 0) - COALESCE(SUM(e.amount), 0)) as net_profit,
    CASE
        WHEN COALESCE(SUM(e.amount), 0) = 0 THEN 'full'
        WHEN COALESCE(SUM(e.amount), 0) >= COALESCE(SUM(i.amount), 0) THEN 'zero'
        ELSE 'partial'
    END as profit_type
FROM bookings b
LEFT JOIN income i ON b.id = i.booking_id
LEFT JOIN expenses e ON b.id = e.booking_id AND e.category_id = 2
GROUP BY b.id;

-- =====================================================
-- 9. ТАБЛИЦА BOOKING_HISTORY (опционально)
-- =====================================================

-- 9.1 Создать таблицу истории изменений заказов
CREATE TABLE IF NOT EXISTS booking_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    reason TEXT NULL,
    changed_by INT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_history_booking
        FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON DELETE CASCADE,

    INDEX idx_booking_id (booking_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='История изменений статусов заказов';

-- =====================================================
-- 10. ИНДЕКСЫ ДЛЯ ОПТИМИЗАЦИИ
-- =====================================================

-- 10.1 Индекс для expenses.booking_id (для быстрого поиска возвратов)
CREATE INDEX idx_expenses_booking_id ON expenses(booking_id);

-- 10.2 Индекс для bookings.status (для фильтрации)
CREATE INDEX idx_bookings_status ON bookings(status);

-- 10.3 Индекс для bookings.master_id (для фильтрации по мастерам)
CREATE INDEX idx_bookings_master_id ON bookings(master_id);

-- =====================================================
-- 11. ОБНОВЛЕНИЕ СУЩЕСТВУЮЩИХ ДАННЫХ
-- =====================================================

-- 11.1 Конвертировать старые статусы в новые
-- 'delivered' → 'completed' (выполнен)
UPDATE bookings SET status = 'completed' WHERE status = 'delivered';

-- 'cancelled' → 'cancelled_by_client' (по умолчанию считаем что отменил клиент)
UPDATE bookings SET status = 'cancelled_by_client' WHERE status = 'cancelled';

-- 'failed' → 'client_no_show' (клиент не пришёл)
UPDATE bookings SET status = 'client_no_show' WHERE status = 'failed';

-- 11.2 Заблокировать завершённые заказы
UPDATE bookings
SET is_locked = 1
WHERE status IN (
    'completed',
    'completed_partially',
    'not_completed',
    'cancelled_by_photographer',
    'cancelled_by_client',
    'client_no_show'
);

-- =====================================================
-- КОНЕЦ МИГРАЦИИ
-- =====================================================

-- Проверка результатов:
SELECT 'Migration completed successfully!' as status;

-- Проверка структуры bookings:
SHOW COLUMNS FROM bookings;

-- Проверка VIEW:
SELECT * FROM v_booking_profit LIMIT 5;

-- Проверка конвертации статусов:
SELECT status, COUNT(*) as count
FROM bookings
GROUP BY status
ORDER BY count DESC;
