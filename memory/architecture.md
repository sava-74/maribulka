# Architecture - Архитектура проекта

## Структура проекта

```
maribulka/
├── maribulka-vue/          # Frontend (Vue 3)
│   ├── src/
│   │   ├── components/     # Vue компоненты
│   │   ├── stores/         # Pinia stores
│   │   ├── assets/         # Статика (CSS, изображения)
│   │   └── App.vue
│   └── dist/               # Билд (деплоится на сервер)
│
├── api/                    # Backend (PHP)
│   ├── bookings.php
│   ├── studio_photos.php
│   ├── studio_description.php
│   └── ...
│
└── deploy.ps1              # Скрипт деплоя
```

---

## Tech Stack

### Frontend
- **Framework:** Vue 3 + TypeScript
- **Build:** Vite
- **State:** Pinia
- **Routing:** Vue Router
- **Tables:** TanStack Table (Vue)

### Backend
- **Language:** PHP
- **Database:** MySQL
- **Server:** BeGet shared hosting

### Библиотеки
- **Календарь:** @fullcalendar/vue3
  - Плагины: dayGrid, timeGrid, interaction
- **Редактор:** Quill (@vueup/vue-quill)
- **Иконки:** @mdi/light-js (Material Design Icons Light)
- **Диаграммы:** Chart.js v4.5.1
  - Используется в Reports.vue для финансовых отчётов
  - Регистрация: BarController, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend

---

## Ключевые компоненты

### Календарь записей
- `BookingsFullCalendar.vue` - календарь с режимами месяц/день
- `AddBookingModal.vue` - создание записи с умными слотами
- `EditBookingModal.vue` - редактирование записи
- `BookingActionsModal.vue` - контекстное меню при клике
- `ViewBookingModal.vue` - просмотр записи

### Таблицы
- `ClientsTable.vue` - клиенты
- `PromotionsTable.vue` - акции
- `ShootingTypesTable.vue` - типы съёмок
- `BookingsCalendar.vue` - таблица записей (под календарём)
- `ExpensesTable.vue` - **НОВОЕ** расходы с фильтрацией по месяцам
- `ExpenseCategoriesTable.vue` - **НОВОЕ** справочник категорий расходов
- `IncomeTable.vue` - доходы/платежи

### Финансовые отчёты
- `Reports.vue` - **НОВОЕ** финансовые отчёты с диаграммами Chart.js
  - Диаграмма: Расходы по категориям (горизонтальный bar chart)
  - Диаграмма: Доход по источникам (типам съёмок)
  - Фильтрация: месяц/квартал/год
  - Метрики: доход, расход, прибыль, рентабельность

### Домашняя страница
- `Home.vue` - главная страница
- `UploadPhotoModal.vue` - загрузка фото студии
- `EditStudioDescriptionModal.vue` - редактор описания

### Вкладки
- `Accounting.vue` - родительский компонент с 4 вкладками:
  - **Запись** - BookingsFullCalendar + BookingsCalendar
  - **Приход** - IncomeTable
  - **Расход** - ExpensesTable
  - **Отчёты** - Reports (диаграммы Chart.js)
- `References.vue` - справочники с 4 вкладками:
  - **Клиенты** - ClientsTable
  - **Типы съёмок** - ShootingTypesTable
  - **Акции** - PromotionsTable
  - **Категории расходов** - ExpenseCategoriesTable

---

## Pinia Stores

### bookings.ts
- Управление записями (CRUD)
- Алгоритм свободных слотов
- Фильтрация и сортировка

### finance.ts ✨ НОВОЕ
- Управление финансами (доходы и расходы)
- **State:**
  - income[] - платежи за текущий месяц
  - expenses[] - расходы за текущий месяц
  - refundableBookings[] - заказы для возврата средств
  - currentMonth / currentExpenseMonth (YYYY-MM)
- **Computed:**
  - totalIncome, totalExpenses
  - profit, profitability
- **Actions:**
  - fetchIncome(month?), fetchExpenses(month?)
  - fetchIncomeByBooking(id) - платежи по заказу
  - fetchRefundableBookings() - заказы new/failed с оплатой
  - fetchIncomeByShootingType(month?) - доход по типам съёмок
  - createExpense, updateExpense, deleteExpense
  - deleteIncome

### references.ts

- Справочники:
  - Типы съёмок (shooting_types)
  - Акции (promotions)
  - Клиенты (clients)
  - **Категории расходов (expense_categories)** ✨ НОВОЕ

### home.ts
- Фото студии (4 фото на главной)
- Описание студии (rich text)
- Действующая акция (для баннера)

### auth.ts
- Аутентификация
- Роль пользователя (admin/user)

---

## База данных

### Таблицы

#### bookings

```sql
id, booking_date, shooting_date, processing_days, delivery_date,
client_id, phone, shooting_type_id, quantity, promotion_id,
base_price, discount, final_price, total_amount, paid_amount,
payment_status (enum: unpaid/partially_paid/fully_paid),
status (enum: new/completed/delivered/cancelled/failed),
cancel_reason, created_at, updated_at
```

#### clients

```sql
id, name, phone, email, notes, created_at
```

#### shooting_types

```sql
id, name, duration_minutes, base_price, description, created_at
```

#### promotions

```sql
id, name, discount_percent, start_date, end_date, created_at
```

#### studio_photos

```sql
id, photo_url, position, created_at
```

#### studio_description

```sql
id, content (TEXT), updated_at
```

#### expenses ✨ НОВОЕ

```sql
id, date, amount, category_id, description, created_at
```

#### expense_categories ✨ НОВОЕ

```sql
id, name, is_active (TINYINT), created_at
```

**Важная категория:** ID=2 "Возврат средств" - используется для автозаполнения возвратов

#### income ✨ НОВОЕ

```sql
id, booking_id, amount, payment_date, category, created_at
```

---

## API Endpoints

### Записи

- `GET /api/bookings.php` - список записей
- `GET /api/bookings.php?action=income_by_type&month=YYYY-MM` - **НОВОЕ** доход по типам съёмок
- `GET /api/bookings.php?action=refundable` - **НОВОЕ** заказы для возврата средств
- `POST /api/bookings.php?action=create` - создать запись
- `POST /api/bookings.php?action=update` - обновить запись
- `POST /api/bookings.php?action=delete` - удалить запись
- `POST /api/bookings.php?action=complete` - отметить "Состоялась"
- `POST /api/bookings.php?action=deliver` - провести заказ
- `POST /api/bookings.php?action=cancel` - отменить заказ

### Финансы ✨ НОВОЕ

- `GET /api/income.php?month=YYYY-MM` - платежи за месяц
- `GET /api/income.php?booking_id=X` - платежи по заказу
- `POST /api/income.php` - создать платёж
- `DELETE /api/income.php?id=X` - удалить платёж

- `GET /api/expenses.php?month=YYYY-MM` - расходы за месяц
- `POST /api/expenses.php` - создать расход
- `PUT /api/expenses.php` - обновить расход
- `DELETE /api/expenses.php?id=X` - удалить расход

- `GET /api/expense-categories.php` - список категорий расходов
- `GET /api/expense-categories.php?check_relations=1&id=X` - проверка связей
- `POST /api/expense-categories.php` - создать категорию
- `PUT /api/expense-categories.php` - обновить категорию
- `DELETE /api/expense-categories.php?id=X` - удалить категорию

### Справочники

- `GET /api/clients.php`
- `GET /api/shooting_types.php`
- `GET /api/promotions.php`

### Домашняя страница
- `GET /api/studio_photos.php` - получить фото
- `POST /api/studio_photos.php` - загрузить фото (только admin)
- `DELETE /api/studio_photos.php?position=N` - удалить фото
- `GET /api/studio_description.php` - получить описание
- `POST /api/studio_description.php` - обновить описание (только admin)

### Аутентификация

- `POST /api/auth.php?action=login`
- `POST /api/auth.php?action=logout`
- `GET /api/auth.php?action=check`

---

## Файловая структура CSS

Все стили в отдельных CSS файлах (см. [styles.md](styles.md)):

```
assets/
├── theme.css          # CSS переменные
├── buttons.css        # Все кнопки
├── calendar.css       # FullCalendar
├── tables.css         # Таблицы
├── modal.css          # Модальные окна
├── layout.css         # Layout, вкладки
├── sidebar.css        # Боковое меню
├── topbar.css         # Верхняя панель
├── content.css        # Основной контент
├── home.css           # Домашняя страница
├── panel.css          # Панели и карточки
├── responsive.css     # Медиа-запросы
├── app.css            # Глобальные стили
└── reports.css        # ✨ НОВОЕ - Стили финансовых отчётов
```

---

## Алгоритм свободных слотов

См. подробнее в [calendar.md](calendar.md)

**Кратко:**
- Каждая запись занимает: `duration_minutes + 30 мин перерыв`
- Свободный слот = кандидатный блок не пересекается с занятыми
- Время выбирается ТОЛЬКО после выбора типа съёмки
- В EditBookingModal текущая запись исключена из занятых
- На сегодня: слоты раньше текущего времени отфильтрованы
