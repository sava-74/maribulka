-- =====================================================
-- ОТКАТ МИГРАЦИИ: Бизнес-процесс заказа фотосессии
-- =====================================================
-- Дата: 22.02.2026
-- Описание: Откатывает изменения migration_business_process.sql
-- =====================================================

-- ⚠️ ВНИМАНИЕ! Этот скрипт удалит все данные, добавленные миграцией!
-- Убедитесь, что у вас есть бэкап БД перед выполнением!

-- =====================================================
-- 1. УДАЛИТЬ ТАБЛИЦУ BOOKING_HISTORY
-- =====================================================

DROP TABLE IF EXISTS booking_history;

-- =====================================================
-- 2. УДАЛИТЬ VIEW
-- =====================================================

DROP VIEW IF EXISTS v_booking_profit;

-- =====================================================
-- 3. УДАЛИТЬ ИНДЕКСЫ
-- =====================================================

ALTER TABLE expenses DROP INDEX IF EXISTS idx_expenses_booking_id;
ALTER TABLE bookings DROP INDEX IF EXISTS idx_bookings_status;
ALTER TABLE bookings DROP INDEX IF EXISTS idx_bookings_master_id;

-- =====================================================
-- 4. ОТКАТИТЬ СТАТУСЫ ЗАКАЗОВ
-- =====================================================

-- Конвертировать новые статусы обратно в старые
UPDATE bookings SET status = 'delivered' WHERE status = 'completed';
UPDATE bookings SET status = 'cancelled' WHERE status IN ('cancelled_by_client', 'cancelled_by_photographer');
UPDATE bookings SET status = 'failed' WHERE status = 'client_no_show';

-- Удалить новые статусы (которых не было в старой версии)
-- ВНИМАНИЕ! Эти заказы будут конвертированы в 'cancelled'!
UPDATE bookings SET status = 'cancelled'
WHERE status IN ('in_progress', 'completed_partially', 'not_completed');

-- =====================================================
-- 5. ТАБЛИЦА BOOKINGS - УДАЛИТЬ КОЛОНКИ
-- =====================================================

-- 5.1 Удалить аудит
ALTER TABLE bookings
DROP COLUMN IF EXISTS updated_by,
DROP COLUMN IF EXISTS created_by;

-- 5.2 Удалить master_id
ALTER TABLE bookings
DROP COLUMN IF EXISTS master_id;

-- 5.3 Удалить is_locked
ALTER TABLE bookings
DROP COLUMN IF EXISTS is_locked;

-- 5.4 Вернуть старый ENUM статусов
ALTER TABLE bookings
MODIFY COLUMN status ENUM(
    'new',
    'completed',
    'delivered',
    'cancelled',
    'failed'
) NOT NULL DEFAULT 'new';

-- =====================================================
-- 6. ТАБЛИЦА EXPENSES - УДАЛИТЬ КОЛОНКИ
-- =====================================================

-- 6.1 Удалить аудит
ALTER TABLE expenses
DROP COLUMN IF EXISTS updated_by,
DROP COLUMN IF EXISTS created_by;

-- 6.2 Удалить refund_type
ALTER TABLE expenses
DROP COLUMN IF EXISTS refund_type;

-- 6.3 Удалить booking_id (сначала внешний ключ, потом колонку)
ALTER TABLE expenses
DROP FOREIGN KEY IF EXISTS fk_expenses_booking;

ALTER TABLE expenses
DROP COLUMN IF EXISTS booking_id;

-- =====================================================
-- 7. ТАБЛИЦА INCOME - УДАЛИТЬ КОЛОНКИ
-- =====================================================

ALTER TABLE income
DROP COLUMN IF EXISTS updated_by,
DROP COLUMN IF EXISTS created_by;

-- =====================================================
-- 8. ТАБЛИЦА EXPENSE_CATEGORIES - УДАЛИТЬ КОЛОНКИ
-- =====================================================

ALTER TABLE expense_categories
DROP COLUMN IF EXISTS updated_by,
DROP COLUMN IF EXISTS created_by;

-- =====================================================
-- 9. ТАБЛИЦА CLIENTS - УДАЛИТЬ КОЛОНКИ
-- =====================================================

ALTER TABLE clients
DROP COLUMN IF EXISTS updated_by,
DROP COLUMN IF EXISTS created_by;

-- =====================================================
-- 10. ТАБЛИЦА SHOOTING_TYPES - УДАЛИТЬ КОЛОНКИ
-- =====================================================

ALTER TABLE shooting_types
DROP COLUMN IF EXISTS updated_by,
DROP COLUMN IF EXISTS created_by;

-- =====================================================
-- 11. ТАБЛИЦА PROMOTIONS - УДАЛИТЬ КОЛОНКИ
-- =====================================================

ALTER TABLE promotions
DROP COLUMN IF EXISTS updated_by,
DROP COLUMN IF EXISTS created_by;

-- =====================================================
-- КОНЕЦ ОТКАТА
-- =====================================================

-- Проверка результатов:
SELECT 'Rollback completed successfully!' as status;

-- Проверка структуры bookings:
SHOW COLUMNS FROM bookings;

-- Проверка статусов:
SELECT status, COUNT(*) as count
FROM bookings
GROUP BY status
ORDER BY count DESC;
