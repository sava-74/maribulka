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
- **Редактор:** CKEditor 5 v47.6.0 (@ckeditor/ckeditor5-vue)
  - Заменил TipTap 07.03.2026
  - Glass-стилизация тулбара
  - Float изображений (left/right/block)
  - Кастомные модалки (БЕЗ window.prompt)
  - Стили в `editor.css`
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
- **Навигация по вкладкам:** `App.vue` (через `navStore.currentPage`)
- **Учёт (4 вкладки):**
  - **Запись** - BookingsFullCalendar + BookingsCalendar
  - **Приход** - IncomeTable
  - **Расход** - ExpensesTable
  - **Отчёты** - Reports (диаграммы Chart.js)
- **Справочники (4 вкладки):** `References.vue`
  - **Клиенты** - ClientsTable
  - **Типы съёмок** - ShootingTypesTable
  - **Акции** - PromotionsTable
  - **Категории расходов** - ExpenseCategoriesTable

---

## UI Компоненты (переиспользуемые)

**selectBox** — кастомный селект с поиском
- **Файлы:** `components/ui/selectBox/SelectBox.vue`, `assets/selectBox.css`
- **Props:** `options`, `placeholder`, `modelValue`
- **Events:** `update:modelValue`, `search`

**datePicker** — выбор дат (flatpickr)
- **Файлы:** `components/ui/datePicker/DatePicker.vue`, `assets/datePicker.css`
- **Props:** `modelValue`, `mode` (single/range)
- **Events:** `update:modelValue`, `change`

**searchTable** — таблица с поиском и фильтрами
- **Файлы:** `components/ui/searchTable/SearchTable.vue`, `assets/searchTable.css`
- **Props:** `data`, `columns`, `filters`
- **Events:** `search`, `filter`, `sort`

**SwitchToggle** — переключатель вкл/выкл
- **Файлы:** `components/ui/SwitchToggle/SwitchToggle.vue`, `assets/switchToggle.css`
- **Props:** `modelValue`, `label`
- **Events:** `update:modelValue`

**padLoader** — лоадер в стиле padGlass
- **Файлы:** `components/ui/padLoader/padLoader.vue`, `assets/padLoader.css`
- **Props:** `text` (опционально)

---

## Навигация

**Store:** `navigation.ts` (navStore)

**Страницы (currentPage):**

| Страница | Компонент | Описание |
|----------|-----------|----------|
| `home` | `Home.vue` | Главная страница |
| `calendar` | `CalendarPanel.vue` + `CalendarSidebar.vue` | Календарь записей |
| `bookings` | `BookingsCalendar.vue` | Таблица записей |
| `income` | `IncomeTable.vue` | Таблица доходов |
| `expenses` | `ExpensesTable.vue` | Таблица расходов |
| `users` | `Users.vue` | Пользователи и разрешения |
| `salary_types` | `SalaryTypes.vue` | Справочник типов зарплат |
| `clients` | `References.vue` (вкладка) | Справочник клиентов |
| `shooting_types` | `References.vue` (вкладка) | Типы съёмок |
| `promotions` | `References.vue` (вкладка) | Акции |
| `expense_categories` | `References.vue` (вкладка) | Категории расходов |
| `sandbox` | `SandboxView.vue` | Песочница (glass-btn система) |

**Переключение:**
```typescript
navStore.currentPage = 'calendar' // Переход на страницу
```

**TopBar:** Кнопки навигации используют `navStore.currentPage` для подсветки активной

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

### Аутентификация

- `POST /api/login.php` — Авторизация
- `GET/POST /api/session.php` — Проверка сессии / Выход

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

### Пользователи и разрешения

- `GET /api/users.php` - список пользователей
- `GET /api/permissions.php` - права пользователей

### Домашняя страница

- `GET /api/home_blocks.php` - блоки главной страницы
- `GET /api/studio_description.php` - получить описание
- `POST /api/studio_description.php` - обновить описание (только admin)
- `GET /api/studio_photos.php` - получить фото
- `POST /api/studio_photos.php` - загрузить фото (только admin)
- `DELETE /api/studio_photos.php?position=N` - удалить фото

---

## Файловая структура API

```
api/
├── login.php              # POST — Авторизация
├── session.php            # GET/POST — Проверка/выход
├── bookings.php           # Записи
├── clients.php            # Клиенты
├── expenses.php           # Расходы
├── expense-categories.php # Категории расходов
├── income.php             # Доходы
├── promotions.php         # Акции
├── shooting-types.php     # Типы съёмок
├── salary-types.php       # Типы зарплат
├── users.php              # Пользователи
├── permissions.php        # Разрешения
├── home_blocks.php        # Блоки главной
├── studio_description.php # Описание студии
└── studio_photos.php      # Фото студии
```

Все стили в отдельных CSS файлах (см. [styles.md](styles.md)):

```
maribulka-vue/src/
├── style.css                    # Глобальные стили + темы
├── assets/
│   ├── buttonGlass.css          # Кнопки
│   ├── padGlass.css             # Панели
│   ├── modal.css                # Модалки
│   ├── calendar.css             # Календарь
│   ├── calendar-table.css       # Таблица календаря
│   ├── table.css                # Таблицы
│   ├── income-table.css         # Таблица доходов
│   ├── home.css                 # Главная страница
│   ├── editor.css               # CKEditor 5
│   ├── animations.css           # Анимации
│   └── validAlertModal.css      # Alert модалка
├── components/
│   ├── ui/*/
│   │   ├── selectBox.css
│   │   ├── datePicker.css
│   │   ├── searchTable.css
│   │   └── switchToggle.css
│   ├── salaryTypes/salaryTypes.css
│   └── sandbox/sandbox.css      # Песочница
└── assets/oldCss/               # Старые CSS (не импортируются)
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
