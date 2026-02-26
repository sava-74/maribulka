# Память проекта Maribulka

## 📋 Оглавление памяти

Основная память разбита на тематические файлы для удобства:

- **[deployment.md](file:///d:/GitHub/maribulka/memory/deployment.md)** - сервер BeGet, деплой, SSH, медиа-файлы
- **[architecture.md](file:///d:/GitHub/maribulka/memory/architecture.md)** - структура проекта, ключевые файлы, БД
- **[finance.md](file:///d:/GitHub/maribulka/memory/finance.md)** - ✨ НОВЫЙ - финансовая система, Chart.js, отчёты
- **[styles.md](file:///d:/GitHub/maribulka/memory/styles.md)** - организация CSS, паттерны, переменные
- **[calendar.md](file:///d:/GitHub/maribulka/memory/calendar.md)** - календарь записей, статусы, цвета, слоты
- **[mobile.md](file:///d:/GitHub/maribulka/memory/mobile.md)** - мобильная адаптация, брейкпоинты
- **[changelog.md](file:///d:/GitHub/maribulka/memory/changelog.md)** - история изменений по датам
- **[patterns.md](file:///d:/GitHub/maribulka/memory/patterns.md)** - эталоны кода, таблицы, модалки
- **[traps.md](file:///d:/GitHub/maribulka/memory/traps.md)** - известные ловушки и баги
- **[expenses-plan.md](file:///d:/GitHub/maribulka/memory/expenses-plan.md)** - ✅ РЕАЛИЗОВАНО - план работ: Расходы + Категории расходов
- **[icons.md](file:///d:/GitHub/maribulka/memory/icons.md)** - список иконок
- **[filters-plan.md](file:///d:/GitHub/maribulka/memory/filters-plan.md)** - план работ: Фильтры и поиск
- **[business-process.md](file:///d:/GitHub/maribulka/memory/business-process.md)** - ✨ НОВЫЙ - бизнес-процесс заказа, статусы, переходы, финансы

---

## ⚠️ Критическое замечание

**Все общение и разработка в рамках проекта Maribulka должно происходить исключительно на русском языке.**

---

## ⚡ Быстрый старт

### Разработка
```bash
npm run dev  # http://localhost:5173
```

### Деплой (3 шага!)
```bash
git add . && git commit -m "..."
git push
.\deploy.ps1  # Windows
```

---

## 🔥 Критические правила

### 1. НЕТ локального PHP сервера!
Все API запросы идут через **Vite proxy** на удалённый BeGet.

### 2. Организация стилей (ЗАКОН!)
- **НИКАКИХ** `<style>` блоков в .vue файлах!
- Один тип стилей = один CSS файл
- Подробнее в [styles.md](styles.md)

### 3. Sticky элементы
- Использовать `position: sticky`, **НЕ** `fixed`
- Эталон: `.accounting-nav` в layout.css
- Подробнее в [styles.md](styles.md)

### 4. Мобильная адаптация
- **Единый брейкпоинт:** `≤350px`
- Подробнее в [mobile.md](mobile.md)

### 5. Медиа-файлы
- Хранятся **вне dist/** в `/home/s/sava7424/maribulka.rf/media/`
- Симлинк создаётся автоматически при деплое
- Подробнее в [deployment.md](deployment.md)

---

## 🎯 Ключевые паттерны

- **Модалки:** Только кастомные (AlertModal, ConfirmModal), НЕ browser alert/confirm
- **Иконки:** Только @mdi/light-js (Material Design Icons Light)
- **Кнопки:** `glass-button` (40x40), `glass-button-text` (150x40)
- **ID заказа:** МБ{id}{magicNumber}{year}

Подробнее в [patterns.md](patterns.md)

---

## 📊 Структура проекта

- **Frontend:** Vue 3 + TypeScript + Vite
- **Backend:** PHP API (api/)
- **БД:** MySQL на BeGet
- **Библиотеки:**
  - FullCalendar (календарь записей)
  - Quill (редактор описания)
  - @mdi/light-js (иконки)
  - TanStack Table (таблицы)
  - Chart.js v4.5.1 (диаграммы финансовых отчётов) ✨ НОВОЕ

Подробнее в [architecture.md](architecture.md)

---

## 📝 Текущие задачи

### ✅ Выполнено недавно (февраль 2026)

- ✅ Домашняя страница (баннер акции, 4 фото)
- ✅ Rich text редактор описания студии
- ✅ Синхронизация авторизации Frontend ↔ Backend
- ✅ **Финансовая система - полностью реализована!**
  - Вкладка "Расходы" + Справочник категорий
  - Возвраты средств (категория ID=2, автозаполнение)
  - Вкладка "Приход" (платежи, доходы)
  - API для expenses, income, expense-categories
  - Pinia store finance.ts
- ✅ **Финансовые отчёты с Chart.js диаграммами!**
  - Диаграмма "Расходы по категориям" (горизонтальный bar)
  - Диаграмма "Доход по источникам" (типам съёмок)
  - Фильтрация по периодам (месяц/квартал/год) - РАБОТАЕТ!
  - Метрики: доход, расход, прибыль, рентабельность
- ✅ **Актуализация памяти проекта** (22.02.2026)
  - Создан finance.md с полным описанием финансовой системы
  - Обновлён architecture.md
  - Обновлён changelog.md

### ⏳ В работе

- 🚀 **ФАЗА 1: Миграция БД для бизнес-процесса** (22.02.2026) ⬅️ **НАЧИНАЕМ!**
  - 8 новых статусов заказа (new, in_progress, completed, completed_partially, not_completed, cancelled_by_photographer, cancelled_by_client, client_no_show)
  - Блокировка редактирования (`is_locked`)
  - Связь возвратов с заказами (`expenses.booking_id`)
  - Типы возвратов (`expenses.refund_type`)
  - VIEW `v_booking_profit` для расчёта прибыли
  - Колонки для мультипользователя: `created_by`, `updated_by`, `master_id`
  - [Полный план в business-process.md](file:///d:/GitHub/maribulka/memory/business-process.md)

- ⏳ **Оптимизация UI и мобильная адаптация**
  - Борьба со стилями меню клиентов (commit 2ec48f1)
  - Уменьшение кнопок для мобилки (commit d58a48c)
  - Фиксы topbar и sidebar (commits b6e7699, 8d785f0)
- ⏳ **Работа с фильтрами** (таблицы заказов, клиентов, съёмок, акций) - [План](filters-plan.md)
- ⏳ Кнопка скрыть/показать таблицу

---

## 🔗 Данные для подключения

| Параметр | Значение |
|----------|----------|
| **SSH Host** | sava7424.beget.tech |
| **SSH User** | sava7424 |
| **SSH Key** | ~/.ssh/beget_maribulka |
| **DB Name** | sava7424_mari |
| **Сайт** | http://марибулька.рф |

Подробнее в [deployment.md](deployment.md)
