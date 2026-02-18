# Память проекта Maribulka

## 📋 Оглавление памяти

Основная память разбита на тематические файлы для удобства:

- **[deployment.md](file:///d:/GitHub/maribulka/memory/deployment.md)** - сервер BeGet, деплой, SSH, медиа-файлы
- **[architecture.md](file:///d:/GitHub/maribulka/memory/architecture.md)** - структура проекта, ключевые файлы, БД
- **[styles.md](file:///d:/GitHub/maribulka/memory/styles.md)** - организация CSS, паттерны, переменные
- **[calendar.md](file:///d:/GitHub/maribulka/memory/calendar.md)** - календарь записей, статусы, цвета, слоты
- **[mobile.md](file:///d:/GitHub/maribulka/memory/mobile.md)** - мобильная адаптация, брейкпоинты
- **[changelog.md](file:///d:/GitHub/maribulka/memory/changelog.md)** - история изменений по датам
- **[patterns.md](file:///d:/GitHub/maribulka/memory/patterns.md)** - эталоны кода, таблицы, модалки
- **[traps.md](file:///d:/GitHub/maribulka/memory/traps.md)** - известные ловушки и баги
- **[expenses-plan.md](file:///d:/GitHub/maribulka/memory/expenses-plan.md)** - план работ: Расходы + Категории расходов

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
- **Единый брейкпоинт:** `≤768px`
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

Подробнее в [architecture.md](architecture.md)

---

## 📝 Текущие задачи

- ✅ Домашняя страница (баннер акции, 4 фото)
- ✅ Rich text редактор описания студии
- ✅ Синхронизация авторизации Frontend ↔ Backend
- ✅ **Вкладка "Расходы" + Справочник категорий** (полностью завершено!)
- ✅ **Возвраты средств** (категория ID=2, автозаполнение, валидация)
- ⏳ Кнопка скрыть/показать таблицу
- ⏳ Доработка мобильной адаптации

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
