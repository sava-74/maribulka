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

### Домашняя страница
- `Home.vue` - главная страница
- `UploadPhotoModal.vue` - загрузка фото студии
- `EditStudioDescriptionModal.vue` - редактор описания

### Вкладки
- `Accounting.vue` - родительский компонент вкладки "Запись"

---

## Pinia Stores

### bookings.ts
- Управление записями (CRUD)
- Алгоритм свободных слотов
- Фильтрация и сортировка

### references.ts
- Справочники:
  - Типы съёмок (shooting_types)
  - Акции (promotions)
  - Клиенты (clients)

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
status (enum: new/completed/delivered/cancelled),
cancel_reason, created_at, updated_at
```

**TODO:** Добавить `processed_at` DATETIME - дата/время проведения заказа

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

**Создана:** 16.02.2026 02:08:25

---

## API Endpoints

### Записи
- `GET /api/bookings.php` - список записей
- `POST /api/bookings.php?action=create` - создать запись
- `POST /api/bookings.php?action=update` - обновить запись
- `POST /api/bookings.php?action=delete` - удалить запись
- `POST /api/bookings.php?action=complete` - отметить "Состоялась"
- `POST /api/bookings.php?action=deliver` - провести заказ

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
└── home.css           # Домашняя страница
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
