-- =====================================================
-- ИСПРАВЛЯЮЩАЯ МИГРАЦИЯ: Доработка expenses и остальных таблиц
-- =====================================================
-- Дата: 22.02.2026
-- Описание: Добавляет недостающие колонки и VIEW
-- =====================================================

-- =====================================================
-- 1. ТАБЛИЦА EXPENSES
-- =====================================================

-- 1.1 Добавить refund_type
ALTER TABLE expenses
ADD COLUMN refund_type ENUM('prepayment', 'partial', 'full') NULL
COMMENT 'Тип возврата: prepayment (предоплата), partial (частичный), full (полный)'
AFTER booking_id;

-- 1.2 Добавить аудит
ALTER TABLE expenses
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 2. ТАБЛИЦА INCOME
-- =====================================================

ALTER TABLE income
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 3. ТАБЛИЦА EXPENSE_CATEGORIES
-- =====================================================

ALTER TABLE expense_categories
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 4. ТАБЛИЦА CLIENTS
-- =====================================================

ALTER TABLE clients
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 5. ТАБЛИЦА SHOOTING_TYPES
-- =====================================================

ALTER TABLE shooting_types
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 6. ТАБЛИЦА PROMOTIONS
-- =====================================================

ALTER TABLE promotions
ADD COLUMN created_by INT NULL
COMMENT 'Кто создал запись'
AFTER created_at,
ADD COLUMN updated_by INT NULL
COMMENT 'Кто последний обновил запись'
AFTER created_by;

-- =====================================================
-- 7. VIEW v_booking_profit
-- =====================================================

-- 7.1 Удалить старый VIEW если существует
DROP VIEW IF EXISTS v_booking_profit;

-- 7.2 Создать VIEW для расчёта прибыли
-- ВАЖНО: используем expenses.category (не category_id!)
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
LEFT JOIN expenses e ON b.id = e.booking_id AND e.category = 2
GROUP BY b.id;

-- =====================================================
-- 8. ТАБЛИЦА BOOKING_HISTORY
-- =====================================================

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
-- 9. ИНДЕКСЫ ДЛЯ ОПТИМИЗАЦИИ
-- =====================================================

-- 9.1 Индекс для expenses.booking_id
CREATE INDEX idx_expenses_booking_id ON expenses(booking_id);

-- 9.2 Индекс для bookings.status
CREATE INDEX idx_bookings_status ON bookings(status);

-- 9.3 Индекс для bookings.master_id
CREATE INDEX idx_bookings_master_id ON bookings(master_id);

-- =====================================================
-- КОНЕЦ МИГРАЦИИ
-- =====================================================

SELECT 'Fix migration completed successfully!' as status;
