# Business Process - Бизнес-процесс заказа фотосессии

**Дата актуализации:** 22.02.2026
**Файлы диаграммы:** `бизнес процес заказа фото сессии.png`, `бизнес процес заказа фото сессии.json`

---

## 📋 СТАТУСЫ ЗАКАЗА (8 штук)

```sql
ENUM(
    'new',                       -- 1. Новый
    'in_progress',               -- 2. В работе
    'completed',                 -- 3. Выполнен
    'completed_partially',       -- 4. Клиент принял заказ частично
    'not_completed',             -- 5. Клиент не принял заказ
    'cancelled_by_photographer', -- 6. Отменён фотографом
    'cancelled_by_client',       -- 7. Отменён клиентом
    'client_no_show'             -- 8. Клиент не пришёл
)
```

---

## 💰 СТАТУСЫ ОПЛАТЫ (3 типа)

1. **Предоплата** - частичная оплата (paid_amount < total_amount)
2. **Оплачен** - полная оплата 100% (paid_amount = total_amount)
3. **Возврат** - трёх видов:
   - Возврат предоплаты (при отмене)
   - Частичный возврат (клиент принял частично)
   - Полный возврат 100% (клиент НЕ принял)

---

## 🔄 ПЕРЕХОДЫ СТАТУСОВ

### ❌ НЕТ автоматических переходов!

**Все** статусы меняются **только по подтверждению фотографа**. Никаких автоматических смен по времени или событиям.

---

## 🎬 ОСНОВНОЙ ПОТОК

### 1️⃣ Оформление заказа

```
Оформление заказа (new)
    ↓
Предоплата?
    ├─ ДА → Приход → Касса+ → Заказ "новый"
    └─ НЕТ → Заказ "новый"
```

---

### 2️⃣ Кнопка "Подтвердить съёмку" (new → in_progress)

**Местоположение:** Таблица заказов
**Текущий статус:** `new`

**Логика:**

```
Фотограф нажал "Подтвердить съёмку"
    ↓
Проверить предоплату (paid_amount > 0)?
    ├─ НЕТ → Открыть IncomeModal (добавить оплату)
    │        ↓
    │        После оплаты → Статус: new → in_progress
    │
    └─ ЕСТЬ → Статус: new → in_progress (сразу)

❗ Дату НЕ меняем! (остаётся shooting_date)
```

**API эндпоинт:** `POST /api/bookings.php?action=confirm_session`

---

### 3️⃣ Кнопка "Выдать заказ" (in_progress → completed/partially/not_completed)

**ПЕРЕИМЕНОВАТЬ!** Старое название: "Выдать съёмку" ❌ → **"Выдать заказ"** ✅

**Текущий статус:** `in_progress`

**Модалка:** "Завершение заказа"

**Шаг 1: Проверить оплату 100%**

```
Оплата 100% (paid_amount = total_amount)?
    ├─ ДА → Открыть модалку завершения
    └─ НЕТ → Открыть IncomeModal (собрать остаток) → потом модалку завершения
```

**Шаг 2: Модалка с 3 радио-кнопками**

```
⚪ Клиент принял заказ полностью
   → Статус: completed
   → Записать в v_booking_profit: net_profit = total_amount, profit_type = 'full'
   → is_locked = 1

⚪ Клиент принял заказ частично
   → Статус: completed_partially
   → Открыть RefundModal (ввести сумму возврата)
   → Создать запись в expenses (category_id=2, refund_type='partial', booking_id=X)
   → Записать в v_booking_profit: net_profit = total_amount - refund, profit_type = 'partial'
   → is_locked = 1

⚪ Клиент не принял заказ
   → Статус: not_completed
   → Открыть RefundModal (возврат 100%)
   → Создать запись в expenses (category_id=2, refund_type='full', booking_id=X, amount=total_amount)
   → Записать в v_booking_profit: net_profit = 0, profit_type = 'zero'
   → is_locked = 1
```

**API эндпоинты:**
- `POST /api/bookings.php?action=complete_full`
- `POST /api/bookings.php?action=complete_partial`
- `POST /api/bookings.php?action=not_completed`

---

### 4️⃣ Кнопка "Отменить запись" (new → cancelled_*/client_no_show)

**Доступна для статусов:** `new`, `in_progress`

**Модалка:** "Отмена заказа"

**Шаг 1: Выбрать причину отмены (3 радио-кнопки)**

```
⚪ Отменил фотограф → статус: cancelled_by_photographer
⚪ Отменил клиент → статус: cancelled_by_client
⚪ Клиент не пришёл → статус: client_no_show
```

**Шаг 2: Проверить предоплату**

```
Предоплата была (paid_amount > 0)?
    ├─ НЕТ → Кнопка "ОК" → Закрыть заказ с выбранным статусом + is_locked=1
    │
    └─ ЕСТЬ → Проверить причину отмены:
              │
              ├─ Отменил фотограф → Кнопка "Возврат предоплаты"
              │                     ↓
              │                     RefundModal (возврат 100%)
              │                     ↓
              │                     expenses (category_id=2, refund_type='prepayment', booking_id=X)
              │                     ↓
              │                     Кнопка меняется на "ОК"
              │                     ↓
              │                     Закрыть заказ + is_locked=1
              │
              ├─ Отменил клиент → [то же самое]
              │
              └─ Клиент не пришёл → Предоплата НЕ возвращается!
                                    ↓
                                    Создать Приход (предоплата остаётся как прибыль)
                                    ↓
                                    Кнопка "ОК" → Закрыть заказ + is_locked=1
```

**API эндпоинты:**
- `POST /api/bookings.php?action=cancel` (параметр: cancel_reason)
- `POST /api/bookings.php?action=client_no_show`

---

## 🔒 БЛОКИРОВКА РЕДАКТИРОВАНИЯ

**Правило:** После блоков со **звёздочкой (*)** изменения в заказе **запрещены**.

Блоки с * на диаграмме:
1. `*Заказ "выполнен"` (completed)
2. `*Клиент не пришёл` (client_no_show)

**Статусы с `is_locked = 1`:**
- `completed`
- `completed_partially`
- `not_completed`
- `cancelled_by_photographer`
- `cancelled_by_client`
- `client_no_show`

**Проверка в API:**

```php
// В bookings.php перед UPDATE
if ($booking['is_locked']) {
    http_response_code(403);
    echo json_encode(['error' => 'Заказ заблокирован для редактирования']);
    exit;
}
```

---

## 💸 ФИНАНСОВЫЕ РАСЧЁТЫ

### Формулы

**Чистая прибыль:**
```
Прибыль - Расход = Чистая прибыль
```

**Рентабельность ROM:**
```
(Чистая прибыль / Расход) × 100% = Рентабельность ROM
```

### Таблица `expenses` с возвратами

**Категория ID=2:** "Возврат средств"

**Добавлено поле:**
- `booking_id INT NULL` - ID заказа (для связи возврата с заказом)
- `refund_type ENUM('prepayment', 'partial', 'full')` - тип возврата

**Примеры:**

```sql
-- Возврат предоплаты (отмена заказа)
INSERT INTO expenses (date, amount, category_id, booking_id, refund_type, description)
VALUES ('2026-02-22', 2000, 2, 123, 'prepayment', 'Возврат предоплаты (отмена заказа #МБ123...)');

-- Частичный возврат (клиент принял частично)
INSERT INTO expenses (date, amount, category_id, booking_id, refund_type, description)
VALUES ('2026-02-22', 1500, 2, 123, 'partial', 'Частичный возврат (заказ #МБ123...)');

-- Полный возврат (клиент НЕ принял)
INSERT INTO expenses (date, amount, category_id, booking_id, refund_type, description)
VALUES ('2026-02-22', 5000, 2, 123, 'full', 'Полный возврат (заказ #МБ123...)');
```

---

## 📊 VIEW для расчёта прибыли

**Вместо отдельной таблицы `profit` используем VIEW:**

```sql
CREATE VIEW v_booking_profit AS
SELECT
    b.id as booking_id,
    b.status,
    b.shooting_date,
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
```

**Использование:**

```sql
-- Прибыль по заказу
SELECT * FROM v_booking_profit WHERE booking_id = 123;

-- Общая прибыль за месяц
SELECT SUM(net_profit) FROM v_booking_profit
WHERE YEAR(shooting_date) = 2026 AND MONTH(shooting_date) = 2;

-- Рентабельность за месяц
SELECT
    SUM(net_profit) as total_profit,
    (SELECT SUM(amount) FROM expenses WHERE MONTH(date) = 2 AND YEAR(date) = 2026) as total_expenses,
    (SUM(net_profit) / (SELECT SUM(amount) FROM expenses WHERE MONTH(date) = 2) * 100) as rom
FROM v_booking_profit
WHERE YEAR(shooting_date) = 2026 AND MONTH(shooting_date) = 2;
```

---

## 👥 БУДУЩАЯ ФУНКЦИОНАЛЬНОСТЬ: Мультипользователь

### Планируемые колонки (добавляем СЕЙЧАС!)

**Для всех таблиц:**
- `created_by INT NULL` - кто создал запись (user_id)
- `updated_by INT NULL` - кто последний обновил запись (user_id)

**Для таблицы `bookings`:**
- `master_id INT NULL` - какой фотограф (мастер) выполняет заказ

**Таблица `users` (создадим в будущем):**

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'photographer') NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Логику НЕ реализуем сейчас!** Просто добавим колонки, чтобы потом не переделывать БД.

---

## 📝 ИТОГО: Изменения в БД

### 1. Таблица `bookings`

```sql
-- Обновить ENUM статусов
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
) NOT NULL DEFAULT 'new';

-- Добавить блокировку
ALTER TABLE bookings
ADD COLUMN is_locked TINYINT(1) DEFAULT 0 COMMENT 'Блокировка редактирования после завершения';

-- Мультипользователь (будущее)
ALTER TABLE bookings
ADD COLUMN master_id INT NULL COMMENT 'Фотограф (мастер), выполняющий заказ',
ADD COLUMN created_by INT NULL COMMENT 'Кто создал запись',
ADD COLUMN updated_by INT NULL COMMENT 'Кто последний обновил';
```

### 2. Таблица `expenses`

```sql
-- Связь с заказом
ALTER TABLE expenses
ADD COLUMN booking_id INT NULL COMMENT 'ID заказа (только для возвратов, category_id=2)',
ADD FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL;

-- Тип возврата
ALTER TABLE expenses
ADD COLUMN refund_type ENUM('prepayment', 'partial', 'full') NULL COMMENT 'Тип возврата (для категории ID=2)';

-- Мультипользователь (будущее)
ALTER TABLE expenses
ADD COLUMN created_by INT NULL COMMENT 'Кто создал запись',
ADD COLUMN updated_by INT NULL COMMENT 'Кто последний обновил';
```

### 3. Таблица `income`

```sql
-- Мультипользователь (будущее)
ALTER TABLE income
ADD COLUMN created_by INT NULL COMMENT 'Кто создал запись',
ADD COLUMN updated_by INT NULL COMMENT 'Кто последний обновил';
```

### 4. Таблица `expense_categories`

```sql
-- Мультипользователь (будущее)
ALTER TABLE expense_categories
ADD COLUMN created_by INT NULL COMMENT 'Кто создал запись',
ADD COLUMN updated_by INT NULL COMMENT 'Кто последний обновил';
```

### 5. Таблица `clients`

```sql
-- Мультипользователь (будущее)
ALTER TABLE clients
ADD COLUMN created_by INT NULL COMMENT 'Кто создал запись',
ADD COLUMN updated_by INT NULL COMMENT 'Кто последний обновил';
```

### 6. Таблица `shooting_types`

```sql
-- Мультипользователь (будущее)
ALTER TABLE shooting_types
ADD COLUMN created_by INT NULL COMMENT 'Кто создал запись',
ADD COLUMN updated_by INT NULL COMMENT 'Кто последний обновил';
```

### 7. Таблица `promotions`

```sql
-- Мультипользователь (будущее)
ALTER TABLE promotions
ADD COLUMN created_by INT NULL COMMENT 'Кто создал запись',
ADD COLUMN updated_by INT NULL COMMENT 'Кто последний обновил';
```

### 8. VIEW `v_booking_profit`

```sql
CREATE VIEW v_booking_profit AS
SELECT
    b.id as booking_id,
    b.status,
    b.shooting_date,
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
```

### 9. Таблица `booking_history` (опционально)

```sql
CREATE TABLE booking_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    reason TEXT,
    changed_by INT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🚀 ПЛАН РЕАЛИЗАЦИИ

### Фаза 1: БД (1 день) ⬅️ НАЧИНАЕМ С ЭТОГО!
1. ✅ Обновить ENUM `bookings.status` (8 статусов)
2. ✅ Добавить `is_locked` в `bookings`
3. ✅ Добавить `booking_id` в `expenses`
4. ✅ Добавить `refund_type` в `expenses`
5. ✅ Добавить `master_id`, `created_by`, `updated_by` в `bookings`
6. ✅ Добавить `created_by`, `updated_by` во все остальные таблицы
7. ✅ Создать VIEW `v_booking_profit`
8. ✅ Создать таблицу `booking_history` (опционально)

### Фаза 2: Backend API (2-3 дня)
1. Новые эндпоинты:
   - `POST /api/bookings.php?action=confirm_session` (new → in_progress)
   - `POST /api/bookings.php?action=complete_full` (in_progress → completed)
   - `POST /api/bookings.php?action=complete_partial` (in_progress → completed_partially)
   - `POST /api/bookings.php?action=not_completed` (in_progress → not_completed)
   - `POST /api/bookings.php?action=cancel` (new → cancelled_*)
   - `POST /api/bookings.php?action=client_no_show` (new → client_no_show)
2. API для VIEW `v_booking_profit`:
   - `GET /api/profit.php?month=YYYY-MM`

### Фаза 3: Pinia Store (1 день)
1. Добавить actions в `bookings.ts`
2. Обновить `finance.ts` для работы с profit API

### Фаза 4: UI компоненты (3-4 дня)
1. Обновить таблицу заказов:
   - Добавить кнопку **"Подтвердить съёмку"**
   - Переименовать "Выдать съёмку" → **"Выдать заказ"**
   - Обновить кнопку **"Отменить запись"**
2. Создать модалки:
   - `CompleteOrderModal.vue` (3 радио-кнопки)
   - `CancelOrderModal.vue` (3 радио-кнопки + условная кнопка возврата)
   - `RefundModal.vue` (ввод суммы возврата)
3. Обновить `Reports.vue`:
   - Добавить график прибыли из VIEW `v_booking_profit`
   - Показывать валовый доход vs чистую прибыль

### Фаза 5: Тестирование (1-2 дня)
1. Тестирование всех переходов статусов
2. Проверка блокировки редактирования
3. Проверка корректности расчёта прибыли

---

**ИТОГО:** ~8-11 дней разработки
