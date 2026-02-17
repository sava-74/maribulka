# План: Вкладка "Расходы" + Справочник категорий

**Дата начала:** 17.02.2026
**Статус:** В работе 🚧

---

## 🎯 Цель

Реализовать полноценную вкладку "Расходы" в бухгалтерии + справочник "Категории расходов"

---

## 📐 Архитектурные решения

### 1. Разделение сущностей
- ✅ **Справочник "Категории расходов"** → вкладка **"Справочники"** (рядом с Клиентами, Типами съёмок, Акциями)
- ✅ **Таблица "Расходы"** → вкладка **"Бухгалтерия"** (рядом с Записью, Приходом)

### 2. Отчёты - потом!
- ⏳ Вкладка "Отчёты" будет реализована **позже**
- ❌ Сейчас НЕ занимаемся отчётами

### 3. Стилистика
- ✅ Таблица "Расходы" = копия стилей таблицы "Приход" (IncomeTable.vue)
- ✅ TanStack Table без чекбоксов
- ✅ Модалки по эталону (AlertModal, ConfirmModal)
- ✅ Иконки только @mdi/light-js

---

## 📋 План работ (СТРОГО СЛЕДОВАТЬ!)

### ✅ Шаг 1: БД - Создать справочник категорий расходов

**Файл:** `api/migrations/create_expense_categories.sql`

```sql
CREATE TABLE expense_categories (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL COMMENT 'Название категории',
  is_active TINYINT(1) DEFAULT 1 COMMENT 'Активна ли категория',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Дефолтные категории
INSERT INTO expense_categories (name) VALUES
('Материалы'),
('Аренда студии'),
('Транспорт'),
('Программное обеспечение'),
('Оборудование'),
('Возврат средств'),
('Прочее');
```

### ✅ Шаг 2: БД - Миграция expenses: ENUM → INT

**Файл:** `api/migrations/migrate_expenses_category.sql`

```sql
-- Шаг 1: Создать временную таблицу для маппинга
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

-- Шаг 2: Добавить новую колонку
ALTER TABLE expenses ADD COLUMN category_id INT(11) AFTER category;

-- Шаг 3: Перенести данные
UPDATE expenses e
INNER JOIN category_mapping cm ON e.category = cm.old_value
SET e.category_id = cm.new_id;

-- Шаг 4: Удалить старую колонку
ALTER TABLE expenses DROP COLUMN category;

-- Шаг 5: Переименовать новую колонку
ALTER TABLE expenses CHANGE category_id category INT(11) NOT NULL;

-- Шаг 6: Добавить внешний ключ
ALTER TABLE expenses
ADD CONSTRAINT fk_expense_category
FOREIGN KEY (category) REFERENCES expense_categories(id);
```

### ✅ Шаг 3: Backend - API для категорий

**Файл:** `api/expense_categories.php`

- По эталону `shooting_types.php`
- CRUD: GET, POST (create), POST (update), DELETE
- Защита admin для создания/редактирования/удаления

### ✅ Шаг 4: Backend - Обновить expenses.php

- Обновить JOIN: `LEFT JOIN expense_categories ec ON e.category = ec.id`
- Возвращать `category_name` вместо ENUM

### ✅ Шаг 5: Frontend - Справочник категорий

**Компоненты:**
- `ExpenseCategoriesTable.vue` - таблица категорий (эталон: ClientsTable.vue)
- `AddExpenseCategoryModal.vue` - модалка добавления
- `EditExpenseCategoryModal.vue` - модалка редактирования

**Обновить:**
- `References.vue` - добавить вкладку "Категории расходов"
- `stores/references.ts` - добавить `expenseCategories`

### ✅ Шаг 6: Frontend - Таблица расходов

**Обновить компонент:**
- `ExpensesTable.vue` - переделать по стилям IncomeTable.vue

**Создать модалки:**
- `AddExpenseModal.vue` - форма добавления (дата, категория dropdown, сумма, описание, booking_id?)
- `EditExpenseModal.vue` - форма редактирования
- `ViewExpenseModal.vue` - просмотр расхода

**Обновить:**
- `stores/finance.ts` - обновить типы, добавить методы CRUD

### ✅ Шаг 7: Обновить init-database.sql

Добавить таблицу `expense_categories` и обновить структуру `expenses`

---

## 🗂️ Структура таблицы "Расходы"

### Колонки:
1. **Дата** - дата расхода
2. **Категория** - название категории (из справочника)
3. **Сумма** - сумма расхода (₽)
4. **Описание** - описание расхода
5. **Связь со съёмкой** - опционально (для возвратов средств)

### Кнопки:
- ➕ Добавить расход
- 👁️ Просмотр (disabled если нет выделения)
- ✏️ Редактировать (disabled если нет выделения)
- 🗑️ Удалить (disabled если нет выделения)
- 🔍 Фильтры (месяц, категория)
- 🔄 Обновить

### Итоги:
- **Всего за месяц:** {сумма} ₽

---

## ✅ Дефолтные категории расходов

1. Материалы
2. Аренда студии
3. Транспорт
4. Программное обеспечение
5. Оборудование
6. Возврат средств *(связь со съёмкой обязательна!)*
7. Прочее

---

## 📝 Примечания

- **Связь со съёмкой:** При возврате средств нужна ссылка на booking_id
- **Стили:** Копируем стили из IncomeTable.vue
- **Справочник категорий:** В отдельной вкладке "Справочники", НЕ в бухгалтерии!
