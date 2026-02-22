# Changelog - История изменений

## 22.02.2026 - Актуализация памяти проекта 📋

### Системный аудит

Проведён комплексный аудит всех файлов проекта. Обнаружены критические расхождения между реальным состоянием проекта и документацией в памяти.

**Обновлены файлы памяти:**
- architecture.md - добавлена информация о Chart.js, finance.ts, новых компонентах и API
- changelog.md - детализация изменений 18-20 февраля
- finance.md - создан новый файл с полным описанием финансовой системы

**Ключевые находки:**
- Chart.js v4.5.1 успешно интегрирован (20.02.2026)
- Финансовая система полностью функциональна
- Фильтрация по периодам РАБОТАЕТ корректно (вопреки ошибочной записи в changelog от 20.02)
- Активная разработка мобильной адаптации (стили topbar, sidebar, кнопки)

---

## 20.02.2026 - Финансовые отчёты с Chart.js диаграммами ✨

### ✅ Статус: ПОЛНОСТЬЮ РЕАЛИЗОВАНО

**Установлено:**
- Chart.js v4.5.1 (commit c5a5748)
- Минимальная регистрация компонентов: BarController, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend

**Реализовано:**

**1. Компонент Reports.vue**
- Новый компонент финансовых отчётов с диаграммами
- Путь: `components/accounting/Reports.vue`
- CSS: `assets/reports.css` (новый файл)

**2. Диаграммы (Chart.js):**
- **Расходы по категориям** (горизонтальный bar chart)
  - Группировка расходов по category_name
  - Первая строка "Всего" (серый цвет #6b7280)
  - Остальные категории (цветная палитра)
  - Кастомный плагин `barLabels` для отображения сумм

- **Доход по источникам** (типам съёмок)
  - Горизонтальный bar chart
  - Данные из fetchIncomeByShootingType() API
  - Формат: "{count} - {total} ₽"
  - Кастомный плагин `incomeBarLabels`

**3. Фильтрация по периодам:**
- Режимы: месяц / квартал / год
- Селект периода + input[type="month"]
- Функция filterByPeriod() для вычисления диапазонов
- Квартал: автоматический расчёт (месяц 0-3 → Q1, 4-7 → Q2, и т.д.)

**4. Метрики отчёта:**
- Доход (periodIncomeTotal)
- Расход (periodExpensesTotal)
- Прибыль (periodProfit = income - expenses)
- Рентабельность (profitability = profit / income * 100%)
- Все с динамическими цветами (зелёный/красный)

**5. Sticky панель фильтров:**
- `.reports-header-panel` с position: sticky
- top: 86px (под topbar)
- z-index: 100

**Файлы:**
- `components/accounting/Reports.vue` (новый)
- `assets/reports.css` (новый)
- `stores/finance.ts` (обновлён - fetchIncomeByShootingType)

**API:**
- `GET /api/bookings.php?action=income_by_type&month=YYYY-MM` (новый endpoint)

**Коммиты:**
- c5a5748 - Chart.js установлен
- 1ef3b49 - Отчёты расходы по категориям готовы
- fd66175 - Добавлена диаграмма для доходов

---

## 18-20.02.2026 - Система управления финансами ✨

### Полный функционал доходов и расходов

**Установлено:**
- Полная CRUD система для расходов
- Справочник категорий расходов
- Логика возвратов средств с автозаполнением

**1. База данных:**

Новые таблицы:
```sql
-- Расходы
CREATE TABLE expenses (
  id INT PRIMARY KEY AUTO_INCREMENT,
  date DATE NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  category_id INT NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES expense_categories(id)
);

-- Категории расходов
CREATE TABLE expense_categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  is_active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Платежи (доходы)
CREATE TABLE income (
  id INT PRIMARY KEY AUTO_INCREMENT,
  booking_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  category VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id)
);
```

**2. API Endpoints:**

Новые PHP файлы:
- `api/expenses.php` - CRUD расходов
  - GET ?month=YYYY-MM - расходы за месяц
  - POST - создать расход
  - PUT - обновить
  - DELETE ?id=X - удалить

- `api/expense-categories.php` - справочник категорий
  - GET - список активных
  - GET ?check_relations=1&id=X - проверка связей
  - POST - создать
  - PUT - обновить
  - DELETE ?id=X - удалить

- `api/income.php` - управление платежами
  - GET ?month=YYYY-MM - платежи за месяц
  - GET ?booking_id=X - платежи по заказу
  - POST - создать платёж
  - DELETE ?id=X - удалить

Обновлены endpoints:
- `api/bookings.php?action=refundable` - заказы для возврата (new/failed с оплатой)
- `api/bookings.php?action=income_by_type&month=YYYY-MM` - доход по типам съёмок

**3. Pinia Store: finance.ts**

Новый store для управления финансами:
```typescript
State:
- income[] - платежи
- expenses[] - расходы
- refundableBookings[] - заказы для возврата
- currentMonth / currentExpenseMonth

Computed:
- totalIncome, totalExpenses
- profit, profitability

Actions:
- fetchIncome(month?)
- fetchExpenses(month?)
- fetchIncomeByBooking(id)
- fetchRefundableBookings()
- fetchIncomeByShootingType(month?)
- createExpense, updateExpense, deleteExpense
- deleteIncome
```

**4. Компоненты:**

Новые таблицы:
- `ExpensesTable.vue` - таблица расходов с фильтрацией по месяцам
- `ExpenseCategoriesTable.vue` - справочник категорий
- `IncomeTable.vue` - таблица платежей

Модальные окна расходов:
- `AddExpenseModal.vue` - создание расхода
- `EditExpenseModal.vue` - редактирование
- `ViewExpenseModal.vue` - просмотр
- `AddExpenseCategoryModal.vue` - создание категории
- `EditExpenseCategoryModal.vue` - редактирование категории

Другие:
- `AddPaymentModal.vue` - добавление платежа
- `ViewIncomeModal.vue` - просмотр платежа

**5. Логика возвратов средств:**

Категория ID=2 "Возврат средств" - специальная обработка:
- При выборе категории возврата открывается селект заказов
- Источник: refundableBookings (статусы new/failed с paid_amount > 0)
- Автозаполнение:
  - amount = booking.paid_amount
  - description = "{order_number} - {client_name}"
- Сброс полей при смене категории

**6. Вкладки:**

Обновлён Accounting.vue:
- Запись → BookingsFullCalendar
- Приход → IncomeTable
- Расход → ExpensesTable ✨ НОВОЕ
- Отчёты → Reports ✨ НОВОЕ

Обновлён References.vue:
- Клиенты → ClientsTable
- Типы съёмок → ShootingTypesTable
- Акции → PromotionsTable
- Категории расходов → ExpenseCategoriesTable ✨ НОВОЕ

---

## 21.02.2026 - Оптимизация UI и мобильная адаптация

### Правки стилей

**Проблемы:**
- Стили меню клиентов нуждались в улучшении
- Кнопки были слишком большие на мобилке
- Sidebar и topbar требовали доработки

**Решения:**
- Общий стиль для всех меню (commit 2ec48f1)
- Уменьшены кнопки для мобильной версии (commit d58a48c)
- Исправлен sidebar (commit d58a48c)
- Доработаны стили topbar (commit b6e7699)
- Фиксик кнопок (commit 8d785f0)

**Файлы:**
- `assets/topbar.css` - обновлён
- `assets/sidebar.css` - обновлён
- `assets/buttons.css` - обновлён

---

## 17.02.2026 - Синхронизация авторизации Frontend ↔ Backend

### Проблема
- Frontend (localStorage) и Backend (PHP session) не синхронизированы
- После выхода/таймаута сессии бэкенд возвращает 403, но фронтенд думает что пользователь авторизован
- Кнопки редактирования видны, но API запросы отклоняются

### Решение

**1. Новый API endpoint:** `api/auth.php`
- `GET /api/auth.php?action=check` - проверка сессии
- `POST /api/auth.php?action=login` - вход
- `POST /api/auth.php?action=logout` - выход

**2. Обновлён `stores/auth.ts`**
- Метод `checkSession()` - проверяет PHP сессию через API
- Метод `logout()` - теперь async, вызывает API
- Синхронизация `localStorage` с бэкенд сессией

**3. Автопроверка сессии:** `App.vue`
- При загрузке приложения вызывается `authStore.checkSession()`
- Если сессия невалидна - очищается `localStorage` и `isAdmin = false`

**4. Обновлены компоненты:**
- `LoginModal.vue` - использует `/api/auth.php?action=login`
- `TopBar.vue` - async logout через API

**5. Устаревший файл:**
- ❌ `api/login.php` - НЕ УДАЛЯТЬ! (может использоваться в старых запросах)

### Как это работает
1. Пользователь открывает сайт → `App.vue` вызывает `checkSession()`
2. API проверяет PHP сессию
3. Если сессия валидна → `isAdmin = true`
4. Если сессия истекла → `isAdmin = false`, `localStorage` очищен
5. При выходе → вызов API `/logout` + очистка локального состояния

---

## 17.02.2026 - Очистка SQL файлов

### Удалены дубликаты и мусор
- ❌ Удалена папка `backup_api_with_copy/` (дубликаты SQL и PHP)
- ❌ Удалена папка `sql/` с недописанной миграцией

### Дописаны таблицы в init-database.sql
- ✅ Добавлена таблица `studio_photos` в основной файл инициализации
- ✅ Добавлена таблица `studio_description` (правильный формат!)
  - Поле `created_at` TIMESTAMP
  - Поле `updated_at` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  - ENGINE=InnoDB, utf8mb4_unicode_ci
  - Дефолтное описание студии
- ✅ Добавлены DROP TABLE для обеих таблиц

### Итоговая структура SQL файлов
```
api/
├── init-database.sql          # Полная схема БД + тестовые данные
├── add_failed_status.sql      # Миграция: статус 'failed'
├── add_notes_field.sql        # Миграция: поле 'notes' в bookings
└── migrations/
    └── create_studio_photos.sql  # Миграция: таблица studio_photos
```

---

## 16.02.2026 - Редактор описания студии

### Rich Text Editor (Quill)

**Установка библиотеки:**
```bash
npm install quill @vueup/vue-quill
```

**БД - таблица studio_description:**
```sql
CREATE TABLE studio_description (
  id INT PRIMARY KEY AUTO_INCREMENT,
  content TEXT NOT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Новые файлы:**
- `EditStudioDescriptionModal.vue` - модалка с Quill редактором
- `api/studio_description.php` - API endpoint (GET/POST, защита admin)
- Обновлён `stores/home.ts` - методы `fetchDescription()`, `updateDescription()`

**Функционал редактора:**
- Заголовки (H1, H2, H3)
- Форматирование текста (жирный, курсив, подчёркнутый, зачёркнутый)
- Списки (нумерованные, маркированные)
- Выравнивание текста
- Цвет текста и фона
- Загрузка изображений с автоматическим ресайзом (200x300px, base64)
- Ссылки
- Очистка форматирования

**Мобильная адаптация:**
- Модалка фиксированной ширины 600px
- Горизонтальный скролл на экранах <600px
- `display: block` на overlay для вертикального скролла

**Исправление ссылок:**
- Автоматическое добавление `https://` для относительных ссылок
- Обработка кликов - открытие в новой вкладке
- `target="_blank"` и `rel="noopener noreferrer"` для всех ссылок

**Безопасность:**
- ⚠️ XSS защита ОТСУТСТВУЕТ! (`v-html` без санитизации)
- Текущая защита: только админ может редактировать (проверка на бэкенде)
- TODO: добавить DOMPurify

---

## 14.02.2026

### 1. Мобильная адаптация модальных окон

**Проблемы:**
- Большие модалки обрезались сверху/снизу
- Маленькие модалки были прижаты к верху

**Решения:**
- **Большие модалки:** `display: block` + `overflow-y: auto` на overlay
- **Маленькие модалки:** новый класс `.modal-small` с центрированием
- **Файл:** `modal.css` (строки 550-588)

**Изменённые компоненты:**
- `AlertModal.vue` - добавлен класс `modal-small`
- `ConfirmModal.vue` - добавлен класс `modal-small`
- `LoginModal.vue` - добавлен класс `modal-small`

### 2. Календарь - финансовая информация в режиме дня

**Вторая строка записи:**
- `unpaid` → `, долг {сумма} ₽`
- `partially_paid` → `, оплачено {сумма} ₽, долг {остаток} ₽`
- `fully_paid` → ничего не добавляем

**Файл:** `BookingsFullCalendar.vue` (строки 198-211)

### 3. AddBookingModal - поле оплаты

**Проблемы:**
1. Поле оплаты по умолчанию равнялось итоговой сумме
2. Использовало `position: absolute` → ломало вёрстку

**Решения:**
1. Удалён `watch`, теперь `paymentAmount = 0` по умолчанию
2. Создан класс `.payment-input-inline` (inline, фиксированная ширина 60px)

**Файлы:**
- `AddBookingModal.vue` (строка 25, удалены строки 115-118, line 417)
- `modal.css` (строки 284-293)

### 4. Проект переименован "FotoMari" → "Марибулька"

**index.html:**
- `<html lang="ru">`
- `<title>Марибулька</title>`
- `<link rel="icon" href="/camera.svg">`

**camera.svg:**
- Новый SVG файл с иконкой камеры
- `fill="currentColor"` для динамической окраски

**theme.css:**
- `link[rel="icon"] { color: var(--generalColor); }`
- Favicon окрашен в генеральный цвет (#39FF14)

### 5. Убрана лишняя прокрутка в content

**Проблема:** Даже когда контент влезал, была прокрутка вверх на ~половину экрана.

**Решение:**
- Убран `min-height: 100vh` из `.accounting`
- Добавлен `min-height: 400px` для предотвращения схлопывания

**Файл:** `layout.css`

### 6. Мобильный календарь - цвета точек

**Проблема:** В режиме дня все записи становились красными если хоть одна красная.

**Решение:**
- Приоритет красного ТОЛЬКО в режиме месяца на мобилке
- Условие: `if (windowWidth.value <= 768 && !isDayView.value)`

**Файл:** `BookingsFullCalendar.vue` (строки 153-159)

---

## 13.02.2026 - Таблица "Акции"

### 1. Timeline визуализация акций

**Новый компонент:** `PromotionsTimeline.vue`

**Функционал:**
- График акций на год (365/366 дней)
- Блоки акций позиционируются по датам начала/конца
- Черточки для каждого дня, длинные для 1-го числа месяца
- Подписи месяцев внутри шкалы
- Цвета акций: 10 уникальных пастельных цветов (ротация по ID)
- Формат даты в тултипе: ДД.ММ.ГГ

### 2. Форматирование дат и процентов

**Файлы:** `PromotionsTable.vue`, `ViewPromotionModal.vue`

**Изменения:**
- Дата: ДД.ММ.ГГ (вместо ДД.ММ.ГГГГ)
- Проценты: целое число (`Math.round()`)
- Заголовки таблицы сокращены:
  - "ID" → "№"
  - "Скидка %" → "%"
  - "Дата начала" → "Начало"
  - "Дата окончания" → "Конец"

### 3. Стили таблицы "Акции"

**Файл:** `tables.css`

**Изменения:**
- Центрирование колонок: ID, Дата начала, Дата окончания (строки 378-382)
- Мобильная адаптация (≤768px):
  - Примечание (ClientsTable, 4-я колонка): `max-width: 100px`
  - Описание (ShootingTypesTable, 5-я колонка): `max-width: 100px`
  - Текст обрезается многоточием
- Размер шрифта таблиц: 12px (было 14px)
- `max-height` для `.table-containerTab`: `calc(100vh - 300px)`

### 4. API проверка пересечений периодов акций

**Файл:** `api/promotions.php`

**Функционал:**
- При создании/редактировании акции проверяется пересечение дат
- HTTP 409 Conflict при пересечении с сообщением о конфликтующей акции
- Игнорируются бессрочные акции (start_date/end_date = NULL)

---

## 13.02.2026 - Домашняя страница

### 1. Структура домашней страницы

**Файлы:** `Home.vue`, `home.css`

**Компоненты:**
- Баннер акции (sticky, желтый фон, красный текст)
- 4 фото студии (горизонтальная линия без зазоров)
- Описание студии (текстовый блок)

### 2. Баннер действующей акции

**Файл:** `home.css` (строки 8-23)

**Особенности:**
- `position: sticky` - прилипает к топу при прокрутке
- Десктоп: `top: 87px; margin-top: -8px;`
- Мобилка: `top: 69px;`
- Жёлтый фон (#FFD700), красный текст (#DC143C)
- Текст auto-shrink: `clamp(16px, 4vw, 32px)`
- Показывается только если есть действующая акция

### 3. Загрузка фото студии

**Файлы:** `UploadPhotoModal.vue`, `api/studio_photos.php`, `stores/home.ts`

**БД:** Таблица `studio_photos` (id, photo_url, position, created_at)

**Хранение:**
- Медиа: `/home/s/sava7424/maribulka.rf/media/home/` (вне dist!)
- Симлинк: `dist/media -> ../../media`
- URL: `/media/home/studio_123.jpg`

**Обработка:**
- Автоматическое сжатие (макс 1920x1080, качество 85%)
- Форматы: JPG, PNG, WEBP
- Размер: макс 5 МБ
- Замена старого фото при загрузке на ту же позицию

### 4. Модалка загрузки фото

**Файл:** `UploadPhotoModal.vue`

**Функционал:**
- Preview загруженного фото (max-height: 200px)
- Статичная надпись "Выбрать файл"
- Кнопки: Загрузить, Удалить, Закрыть
- Доступна только для админа

### 5. GitHub Actions и деплой

**Файлы:** `.github/workflows/deploy.yml`, `deploy.ps1`

**Исправления:**
- Проверка сайта: HTTPS вместо HTTP (редирект 301 → 200)
- Проверка API: HTTPS вместо HTTP
- Добавлен симлинк media в deploy.ps1 (строка 84)
- PowerShell скрипт проверяет HTTPS с `-MaximumRedirection 5`

---

## 09.02.2026 - Исправления багов

### 1. Баг проведения заказа

**Проблема:** Сравнение `DATETIME > DATE` всегда true из-за времени.

**Решение:** Разделили логику на два action:
- `complete`: проверяет `DATE(shooting_date) <= today`, ставит status='completed'
- `deliver`: проверяет `status='completed'`, ставит status='delivered' + processed_at=NOW()

**Файл:** `api/bookings.php` (lines 375-410)

### 2. Телефон в ViewBookingModal

**Изменения:**
- Сделан кликабельной ссылкой `<a href="tel:...">`
- Цвет: темно-синий (#1e40af)

---

## Текущие задачи

- ✅ Надписи в режиме день
- ✅ Модалки 'Новая' и 'Редактировать'
- ✅ Алгоритм свободных слотов
- ✅ Защита от записи в прошлом
- ✅ Мобильная адаптация календаря
- ✅ Цвета статусов
- ✅ Телефон в календаре контрастный
- ✅ Таблица "Акции" (PromotionsTable)
- ✅ Домашняя страница (баннер акции, 4 фото)
- ✅ Блок описания студии (rich text editor)
- ✅ Финансовая система (доходы, расходы, категории)
- ✅ Финансовые отчёты с диаграммами Chart.js
- ⏳ Кнопка скрыть/показать таблицу
- ⏳ Доработка мобильной адаптации
