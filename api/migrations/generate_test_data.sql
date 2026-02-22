-- =====================================================
-- ГЕНЕРАЦИЯ ТЕСТОВЫХ ДАННЫХ
-- =====================================================
-- Дата: 22.02.2026
-- Описание: Очистка БД и создание 20 тестовых заказов
-- =====================================================

-- ⚠️ ВНИМАНИЕ! Этот скрипт удалит ВСЕ данные заказов и оплат!

-- =====================================================
-- 1. ОЧИСТКА БД
-- =====================================================

-- Удаляем историю изменений
DELETE FROM booking_history;

-- Удаляем расходы (возвраты)
DELETE FROM expenses WHERE category = 2;

-- Удаляем доходы
DELETE FROM income;

-- Удаляем заказы
DELETE FROM bookings;

-- Сбрасываем AUTO_INCREMENT
ALTER TABLE bookings AUTO_INCREMENT = 1;
ALTER TABLE income AUTO_INCREMENT = 1;
ALTER TABLE booking_history AUTO_INCREMENT = 1;

-- =====================================================
-- 2. ЯНВАРЬ 2026 - 10 ЗАКАЗОВ
-- =====================================================

-- Заказ 1: Новый (без оплаты)
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status)
VALUES ('МБ1112026', '2026-01-05', '2026-01-15 10:00:00', 7, '2026-01-22', 1, '+79001234567', 1, 1, 5000, 0, 5000, 5000, 0, 'unpaid', 'new');

-- Заказ 2: Новый (с предоплатой 2000)
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status)
VALUES ('МБ2122026', '2026-01-06', '2026-01-16 14:00:00', 7, '2026-01-23', 2, '+79001234568', 2, 1, 7000, 0, 7000, 7000, 2000, 'partially_paid', 'new');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-01-06', 2, 2, 2000, 'advance');

-- Заказ 3: В работе (полная оплата)
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status)
VALUES ('МБ3132026', '2026-01-07', '2026-01-17 11:00:00', 7, '2026-01-24', 3, '+79001234569', 1, 1, 5000, 0, 5000, 5000, 5000, 'fully_paid', 'in_progress');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-01-07', 3, 3, 5000, 'full_payment');

-- Заказ 4: Выполнен (клиент принял полностью)
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked, processed_at)
VALUES ('МБ4142026', '2026-01-08', '2026-01-18 15:00:00', 7, '2026-01-25', 8, '+79001234570', 3, 1, 10000, 0, 10000, 10000, 10000, 'fully_paid', 'completed', 1, '2026-01-25 18:00:00');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-01-08', 4, 8, 10000, 'full_payment');

-- Заказ 5: Выполнен частично (автовозврат 50%)
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked, processed_at)
VALUES ('МБ5152026', '2026-01-09', '2026-01-19 12:00:00', 7, '2026-01-26', 5, '+79001234571', 2, 1, 7000, 0, 7000, 7000, 7000, 'fully_paid', 'completed_partially', 1, '2026-01-26 19:00:00');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-01-09', 5, 5, 7000, 'full_payment');
INSERT INTO expenses (date, amount, category, description, booking_id, refund_type) VALUES ('2026-01-26', 3500, 2, 'Возврат средств по заказу (автоматический)', 5, 'partial');

-- Заказ 6: Не выполнен (автовозврат 100%)
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked, processed_at)
VALUES ('МБ6162026', '2026-01-10', '2026-01-20 16:00:00', 7, '2026-01-27', 6, '+79001234572', 1, 1, 5000, 0, 5000, 5000, 5000, 'fully_paid', 'not_completed', 1, '2026-01-27 20:00:00');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-01-10', 6, 6, 5000, 'full_payment');
INSERT INTO expenses (date, amount, category, description, booking_id, refund_type) VALUES ('2026-01-27', 5000, 2, 'Возврат средств по заказу (автоматический)', 6, 'full');

-- Заказ 7: Отменён клиентом (возврат предоплаты)
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked)
VALUES ('МБ7172026', '2026-01-11', '2026-01-21 13:00:00', 7, '2026-01-28', 7, '+79001234573', 2, 1, 7000, 0, 7000, 7000, 3000, 'partially_paid', 'cancelled_by_client', 1);
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-01-11', 7, 7, 3000, 'advance');
INSERT INTO expenses (date, amount, category, description, booking_id, refund_type) VALUES ('2026-01-21', 3000, 2, 'Возврат средств по заказу (автоматический)', 7, 'prepayment');

-- Заказ 8: Отменён фотографом
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked)
VALUES ('МБ8182026', '2026-01-12', '2026-01-22 09:00:00', 7, '2026-01-29', 1, '+79001234567', 3, 1, 10000, 0, 10000, 10000, 0, 'unpaid', 'cancelled_by_photographer', 1);

-- Заказ 9: Клиент не пришёл (возврат предоплаты)
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked)
VALUES ('МБ9192026', '2026-01-13', '2026-01-23 17:00:00', 7, '2026-01-30', 2, '+79001234568', 1, 1, 5000, 0, 5000, 5000, 2500, 'partially_paid', 'client_no_show', 1);
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-01-13', 9, 2, 2500, 'advance');
INSERT INTO expenses (date, amount, category, description, booking_id, refund_type) VALUES ('2026-01-23', 2500, 2, 'Возврат средств по заказу (автоматический)', 9, 'prepayment');

-- Заказ 10: Новый (с акцией, 100% оплата)
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, promotion_id, base_price, discount, final_price, total_amount, paid_amount, payment_status, status)
VALUES ('МБ101102026', '2026-01-14', '2026-01-24 14:00:00', 7, '2026-01-31', 3, '+79001234569', 2, 1, 1, 7000, 1400, 5600, 5600, 5600, 'fully_paid', 'new');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-01-14', 10, 3, 5600, 'full_payment');

-- =====================================================
-- 3. ФЕВРАЛЬ 2026 - 10 ЗАКАЗОВ
-- =====================================================

-- Заказ 11: Новый
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status)
VALUES ('МБ11212026', '2026-02-01', '2026-02-10 10:00:00', 7, '2026-02-17', 8, '+79001234570', 1, 1, 5000, 0, 5000, 5000, 0, 'unpaid', 'new');

-- Заказ 12: Новый (с предоплатой)
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status)
VALUES ('МБ12222026', '2026-02-02', '2026-02-11 15:00:00', 7, '2026-02-18', 5, '+79001234571', 2, 1, 7000, 0, 7000, 7000, 3000, 'partially_paid', 'new');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-02-02', 12, 5, 3000, 'advance');

-- Заказ 13: В работе
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status)
VALUES ('МБ13232026', '2026-02-03', '2026-02-12 11:00:00', 7, '2026-02-19', 6, '+79001234572', 3, 1, 10000, 0, 10000, 10000, 10000, 'fully_paid', 'in_progress');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-02-03', 13, 6, 10000, 'full_payment');

-- Заказ 14: Выполнен
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked, processed_at)
VALUES ('МБ14242026', '2026-02-04', '2026-02-13 12:00:00', 7, '2026-02-20', 7, '+79001234573', 1, 1, 5000, 0, 5000, 5000, 5000, 'fully_paid', 'completed', 1, '2026-02-20 18:00:00');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-02-04', 14, 7, 5000, 'full_payment');

-- Заказ 15: Выполнен частично
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked, processed_at)
VALUES ('МБ15252026', '2026-02-05', '2026-02-14 16:00:00', 7, '2026-02-21', 1, '+79001234567', 2, 1, 7000, 0, 7000, 7000, 7000, 'fully_paid', 'completed_partially', 1, '2026-02-21 19:00:00');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-02-05', 15, 1, 7000, 'full_payment');
INSERT INTO expenses (date, amount, category, description, booking_id, refund_type) VALUES ('2026-02-21', 3500, 2, 'Возврат средств по заказу (автоматический)', 15, 'partial');

-- Заказ 16: Не выполнен
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked, processed_at)
VALUES ('МБ16262026', '2026-02-06', '2026-02-15 13:00:00', 7, '2026-02-22', 2, '+79001234568', 3, 1, 10000, 0, 10000, 10000, 10000, 'fully_paid', 'not_completed', 1, '2026-02-22 20:00:00');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-02-06', 16, 2, 10000, 'full_payment');
INSERT INTO expenses (date, amount, category, description, booking_id, refund_type) VALUES ('2026-02-22', 10000, 2, 'Возврат средств по заказу (автоматический)', 16, 'full');

-- Заказ 17: Отменён клиентом
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked)
VALUES ('МБ17272026', '2026-02-07', '2026-02-16 09:00:00', 7, '2026-02-23', 3, '+79001234569', 1, 1, 5000, 0, 5000, 5000, 2000, 'partially_paid', 'cancelled_by_client', 1);
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-02-07', 17, 3, 2000, 'advance');
INSERT INTO expenses (date, amount, category, description, booking_id, refund_type) VALUES ('2026-02-16', 2000, 2, 'Возврат средств по заказу (автоматический)', 17, 'prepayment');

-- Заказ 18: Отменён фотографом
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked)
VALUES ('МБ18282026', '2026-02-08', '2026-02-17 14:00:00', 7, '2026-02-24', 8, '+79001234570', 2, 1, 7000, 0, 7000, 7000, 0, 'unpaid', 'cancelled_by_photographer', 1);

-- Заказ 19: Клиент не пришёл
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, base_price, discount, final_price, total_amount, paid_amount, payment_status, status, is_locked)
VALUES ('МБ19292026', '2026-02-09', '2026-02-18 17:00:00', 7, '2026-02-25', 5, '+79001234571', 3, 1, 10000, 0, 10000, 10000, 5000, 'partially_paid', 'client_no_show', 1);
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-02-09', 19, 5, 5000, 'advance');
INSERT INTO expenses (date, amount, category, description, booking_id, refund_type) VALUES ('2026-02-18', 5000, 2, 'Возврат средств по заказу (автоматический)', 19, 'prepayment');

-- Заказ 20: В работе (с акцией)
INSERT INTO bookings (order_number, booking_date, shooting_date, processing_days, delivery_date, client_id, phone, shooting_type_id, quantity, promotion_id, base_price, discount, final_price, total_amount, paid_amount, payment_status, status)
VALUES ('МБ201002026', '2026-02-10', '2026-02-19 11:00:00', 7, '2026-02-26', 6, '+79001234572', 1, 1, 1, 5000, 1000, 4000, 4000, 4000, 'fully_paid', 'in_progress');
INSERT INTO income (date, booking_id, client_id, amount, category) VALUES ('2026-02-10', 20, 6, 4000, 'full_payment');

-- =====================================================
-- КОНЕЦ ГЕНЕРАЦИИ
-- =====================================================

SELECT 'Тестовые данные успешно созданы!' as status;

-- Проверка результатов
SELECT
    MONTH(shooting_date) as month,
    status,
    COUNT(*) as count
FROM bookings
GROUP BY MONTH(shooting_date), status
ORDER BY month, status;
