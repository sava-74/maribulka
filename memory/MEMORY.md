# Память проекта Maribulka

## 📋 Оглавление памяти

Основная память разбита на тематические файлы для удобства:

- **[topbar-design.md](topbar-design.md)** - 🆕 топбар: 4 кнопки, Лаунчпад, HTML из эталона
- **[roadmap.md](roadmap.md)** - генеральный план развития проекта (8 этапов!)
- **[glass-panel-guide.md](glass-panel-guide.md)** - 🆕 универсальная система панелей, миграция
- **[deployment.md](deployment.md)** - сервер BeGet, деплой, SSH, медиа-файлы
- **[architecture.md](architecture.md)** - структура проекта, ключевые файлы, БД
- **[styles.md](styles.md)** - организация CSS, паттерны, переменные
- **[calendar.md](calendar.md)** - календарь записей, статусы, цвета, слоты
- **[mobile.md](mobile.md)** - мобильная адаптация, брейкпоинты
- **[changelog.md](changelog.md)** - история изменений по датам
- **[patterns.md](patterns.md)** - эталоны кода, таблицы, модалки
- **[traps.md](traps.md)** - известные ловушки и баги
- **[business-processes.md](business-processes.md)** - бизнес-процессы, правила работы с заказами
- **[expenses-plan.md](expenses-plan.md)** - план работ: Расходы + Категории расходов
- **[buttons-refactoring.md](buttons-refactoring.md)** - рефакторинг кнопок (26.02.2026)
- **[glass-btn-guide.md](glass-btn-guide.md)** - 🆕 ЭТАЛОН новой системы glass-btn (04.03.2026)

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

### 0. ВСЕГДА: план → одобрение → применение

**ЗАПРЕЩЕНО** вносить любые изменения в код без предварительного плана и одобрения пользователя.
Нарушение этого правила вызывает **сильное недовольство** пользователя.

---

### 1. НЕТ локального PHP сервера!
Все API запросы идут через **Vite proxy** на удалённый BeGet.

**Build оптимизация (vite.config.ts):**
- Разбиение на чанки: `vue-vendor` (vue, pinia), `fullcalendar`, `charts` (chart.js, @tanstack/vue-table)
- Chunk size warning limit: 700kb

### 2. Организация стилей (ЗАКОН!)
- **НИКАКИХ** `<style>` блоков в .vue файлах!
- Один тип стилей = один CSS файл
- **НИКАКИХ** новых CSS стилей без явного разрешения пользователя!
- **ТОЛЬКО** существующие базовые классы из CSS файлов проекта
- **100% использование CSS переменных** из `style.css` (theme.css удалён 05.03.2026!)
- Подробнее в [styles.md](styles.md)

### 2а. Новая CSS архитектура (05.03.2026) — АКТУАЛЬНО!

**Три глобальных файла** (импортируются в `main.ts`):

| Файл | Назначение |
|------|-----------|
| `style.css` | Переменные тем `[data-theme]`, body, орбы, scrollbar |
| `buttonGlass.css` | Кнопки `.btnGlass` + модификаторы + `.btn-theme` |
| `padGlass.css` | Панели `.padGlass` + модификаторы |
| `modal.css` | Оверлей `.modal-overlay`, инпуты, субстиль `.padGlass.modal-sm`, `.ButtonFooter` |

**Тема** устанавливается на `<html>` через `document.documentElement.setAttribute('data-theme', 'dark')` — НЕ на `#app`!

**Все старые CSS** перенесены в `src/assets/oldCss/` (theme.css, buttons.css, modal.css и др.)

**❌ НЕЛЬЗЯ:**
- Менять классы эталона при переносе в новые файлы — только переменные (`--sb-*` → `--glass-*`)
- Переименовывать структуру HTML из эталона
- "Адаптировать" код эталона — копировать строго один в один

**Эталон** — `src/sandbox/SandboxView.vue` + `src/sandbox/sandbox.css`

### 3. Backend считает, Frontend получает результат

- **Backend:** агрегация (SUM, COUNT, AVG) на уровне БД
- **Frontend:** получает только итоговые данные (числа, объекты)
- **❌ НЕ гонять** массивы записей по сети для подсчёта на клиенте!
- Пример: `GET /api/expenses.php?balance=true` → `{ totalIncome, totalExpenses, balance }`

### 4. Sticky элементы
- Использовать `position: sticky`, **НЕ** `fixed`
- Эталон: `.accounting-nav` в layout.css
- Подробнее в [styles.md](styles.md)

### 5. Мобильная адаптация
- **Единый брейкпоинт:** `≤768px`
- Подробнее в [mobile.md](mobile.md)

### 6. Медиа-файлы
- Хранятся **вне dist/** в `/home/s/sava7424/maribulka.rf/media/`
- Симлинк создаётся автоматически при деплое
- Подробнее в [deployment.md](deployment.md)

---

## 🎯 Ключевые паттерны

- **Модалки:** Только кастомные (AlertModal, ConfirmModal, LoginModal), НЕ browser alert/confirm
  - **Структура:** `.modal-overlay` → `.padGlass.modal-sm` → контент → `.ButtonFooter`
  - **Заголовок:** `.modal-glassTitle` (БЕЗ `<h2>`!)
  - **Субстиль маленьких:** `.padGlass.modal-sm` — `min-width: auto; gap: 12px; padding: 20px`
  - **Footer:** `.ButtonFooter PosCenter/PosRight/PosLeft/PosSpace`
  - **Кнопки в модалках:** `btnGlass iconText` + обязательно `inner-glow` + `top-shine` в каждой!
- **Иконки:** Только `@mdi/js` (Material Design Icons)
  - **Использование:** `<svg-icon type="mdi" :path="mdiCheckCircleOutline" />` (БЕЗ пропа `:size`!)
  - **Размер/цвет:** через CSS переменные
- **Кнопки (новая система, 05.03.2026):** `.btnGlass` — базовый класс
  - **Модификаторы:**
    - `.btnGlass.bigIcon` — большая иконка-кнопка (TopBar, панели)
    - `.btnGlass.iconText` — иконка + текст (модалки, формы), `min-width: 100px`
    - `.btnGlass.icon-only` — только иконка (pill)
  - **Обязательно в каждой кнопке:** `<span class="inner-glow"></span>` + `<span class="top-shine"></span>`
  - **Footer система:** `.ButtonFooter` + `.PosRight/.PosLeft/.PosSpace/.PosCenter`
  - ⚠️ Старая система `.buttonGL` — в admin-части (Учёт), НЕ трогать без задачи!
- **Панели:** `.padGlass` — базовый, `.padGlass-top` — топбар, `.padGlass-work` — рабочая панель
- **Таблицы:** `.table-toolbar`, `.table-actions`, `.filter-select` (унифицированные стили)
- **ID заказа:** МБ{id}{magicNumber}{year}

Подробнее в [patterns.md](patterns.md) и [styles.md](styles.md)

---

## 📊 Структура проекта

- **Frontend:** Vue 3 + TypeScript + Vite
- **Backend:** PHP API (api/)
- **БД:** MySQL на BeGet
- **Библиотеки:**
  - FullCalendar (календарь записей)
  - **TipTap v3.20** (rich-text редактор, заменил Quill 26.02.2026)
    - Включён resize изображений (ручки по углам/сторонам)
    - Base64 изображения (allowBase64: true)
    - Кастомная модалка ввода URL ссылки (БЕЗ window.prompt)
  - @mdi/js (иконки Material Design)
  - @jamescoyle/vue-icon (компонент для MDI иконок)
  - TanStack Table (таблицы)
  - flatpickr (выбор дат, диапазоны)

Подробнее в [architecture.md](architecture.md)

---

## 📝 Текущие задачи

- ✅ Домашняя страница (баннер акции, 4 фото)
- ✅ **Миграция с Quill на TipTap** (26.02.2026, v3.20.0)
  - Убраны уязвимости npm (2 moderate XSS в quill)
  - Resize изображений с сохранением пропорций
  - Кастомная модалка ввода URL (`.modal-footerUrl`)
  - Минимальные стили (БЕЗ переопределения HTML тегов)
- ✅ Синхронизация авторизации Frontend ↔ Backend
- ✅ **Вкладка "Расходы" + Справочник категорий** (полностью завершено!)
- ✅ **Возвраты средств** (категория ID=2, автозаполнение, валидация)
- ✅ **Бизнес-процесс: Отмена заказа с возвратом** (кнопка "Р" в модалке отмены)
- ✅ **Бизнес-процесс: Правила редактирования** (кнопки edit/delete активны только для status=new + дата не наступила)
- ✅ **Баг: кнопки редактирования** (парсинг shooting_date с временем)
- ✅ **Баг: баланс возврата** (API баланса кассы, состояние загрузки)
- ✅ **Этап 2 начало: Универсальные панели** (glass-panel.css создан!)
- ✅ **Рефакторинг стилей таблиц** (tables.css: унифицированы фильтры, переименованы классы)
- ✅ **Единый фильтр периода** (все вкладки Учёт используют общий фильтр с/по, flatpickr с пресетами)
- ✅ **Полный рефакторинг модалок** (25.02.2026: 100% переменные, h2→div, единый фон, responsive.css очищен)
- ✅ **Анализ стилей кнопок** (26.02.2026: найдено 7 файлов, ~40 селекторов, план готов)
- ✅ **РЕФАКТОРИНГ КНОПОК ЗАВЕРШЁН** (02.03.2026)
  - ✅ Переименование `.glass-button` → `.buttonGL` (47 файлов, 160 вхождений)
  - ✅ Три текстовых суб-стиля (textFix 100px / text auto / textFull 100%)
  - ✅ Система ButtonFooter (4 модификатора: PosRight/Left/Space/Center)
  - ✅ Миграция всех 31 модального компонента — кнопки с текстом и правильным расположением
  - ✅ Мобильные стили кнопок очищены (удалено уменьшение buttonGL до 26px)
  - ⏳ Ещё не сделано: рефакторинг FullCalendar кнопок (после строки 227 buttons.css)
- ✅ **Новый дизайн TopBar + Авторизация + Модалки** (05.03.2026)
  - `data-theme` на `<html>`, темы работают глобально
  - 3 анимированных орба, TopBar 4 кнопки
  - Авторизация: кнопка 1 = `mdiAccountOutline`/`mdiMenu`, вход/выход
  - AlertModal, ConfirmModal, LoginModal — новая система (`padGlass` + `btnGlass`)
  - Новые CSS: `style.css`, `buttonGlass.css`, `padGlass.css`, `modal.css`
- ✅ **Фикс структуры App + отступы мобилки** (05.03.2026 вечер)
  - `App.vue`: корневой `<div class="app-root">` + орбы в `<div class="app-bg-layer">`
  - Орбы: диагональные траектории через весь экран, orb-1 зелёно-бирюзовый (`#00ff88 → #00b4d8`)
- ✅ **Мобильная адаптация LaunchPad + TopBar** (06.03.2026)
  - Брейкпоинт `@media (max-width: 480px)` → `@media (pointer: coarse)` — по типу устройства
  - TopBar на мобилке: `left: 5px; right: 5px; width: auto` вместо `calc`
  - LaunchPad на мобилке: `display: block; overflow-y: auto; padding: 95px 5px 0 5px`
  - `body min-width`: 280px → 355px; скролл по X в TopBar
  - Закрытие LaunchPad кнопкой с анимацией: `defineExpose({ close })` + `launchpadRef?.close()`
- 🚧 **Этап 1: Desktop дизайн** (в работе)
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
