# Styles - Организация стилей

## 🔥 СТРОГОЕ ПРАВИЛО (ЗАКОН ПРОЕКТА!)

### НИКАКИХ `<style>` блоков в .vue файлах!

**Только:** `<template>` + `<script>`

---

## Структура CSS файлов

### Один тип стилей = один файл

```
assets/
├── style.css          # CSS переменные (ОБНОВЛЕНО 2026-02-25, до 05.03.2026 в theme.css)
├── buttons.css        # ВСЕ кнопки
├── padGlass.css       # Панели с вкладками (padGlass-*)
├── calendar.css       # Календарь FullCalendar
├── tables.css         # Таблицы (table-*, filter-*, accounting-table)
├── modal.css          # Модальные окна (РЕФАКТОРИНГ 2026-02-25)
├── layout.css         # Layout, accounting
├── sidebar.css        # Боковое меню
├── topbar.css         # Верхняя панель
├── content.css        # Основной контент
├── home.css           # Домашняя страница
└── responsive.css     # Мобильная адаптация (ОЧИЩЕН 2026-02-25, пустой файл)
```

---

## Правила организации

1. **НЕТ дубликатов** - один стиль в одном месте
2. **НЕТ inline стилей** в template (только классы)
3. **Импорт CSS** только в главных компонентах
4. При изменении стилей - **ВСЕГДА** в соответствующий .css файл
5. **ИСПОЛЬЗОВАТЬ переменные** из style.css (100% применение!)

---

## 🆕 CSS переменные (style.css) - ОБНОВЛЕНО 2026-02-25

**Примечание:** До 05.03.2026 CSS переменные находились в файле `theme.css`, затем перенесены в `style.css`.

### Цвета и общие настройки

```css
--generalColor: #a614ff4f;              /* Основной цвет бордюров (фиолетовый) */
--bgGeneral: #f0f0f0;                   /* Светлый фон для всего приложения */
--neon-blue: #00F3FF;                   /* Дополнительный неоновый цвет */
--generalTextColor: #333;               /* Основной цвет текста */

/* Фоны */
--glass-bg: rgba(255, 255, 255, 0.4);   /* Фон панелей (эффект стекла) */
--glass-bgModal: rgb(228 228 228 / 85%); /* Фон модальных окон (НОВОЕ!) */

/* Размытие */
--glass-bgFilter: blur(5px);            /* Размытие для панелей */
--glass-bgFilterOverlay: blur(10px);    /* Размытие для оверлея модалок (НОВОЕ!) */
--glass-bgFilterModal: blur(3px);       /* Размытие для модальных окон (НОВОЕ!) */

/* Панели */
--panelRadius: 12px;                    /* Радиус скругления панелей */
--glass-shadowPanel: 4px 4px 6px rgba(0, 0, 0, 0.2); /* Тень панелей */
```

### Размеры текста

```css
--generalTextSizeExtraSmall: 6px;       /* Очень мелкие элементы */
--generalTextSizeSmall: 8px;            /* Основной текст модалок */
--generalTextSize: 10px;                /* Заголовки модалок, лейблы */
--generalTextSizeH1: 12px;              /* Заголовки H1 */
--generalTextSizeH2: 14px;              /* Подзаголовки H2 */
--generalTextSizeH3: 16px;              /* Важные заголовки H3 */
--generalTextSizeH4: 18px;              /* Очень важные заголовки H4 */
```

### Кнопки

```css
--glass-bgButton: rgba(255, 255, 255, 0.4);       /* Фон кнопок */
--glass-bgButtonActive: rgba(57, 255, 20, 0.3);   /* Фон активной кнопки */
--glass-widthButton: 20px;                        /* Ширина квадратной кнопки */
--glass-heightButton: 20px;                       /* Высота квадратной кнопки */
--glass-widthButtonText: 63px;                    /* Ширина кнопки с текстом */
--glass-heightButtonText: 20px;                   /* Высота кнопки с текстом */
--glass-borderRadiusButton: 6px;                  /* Скругление кнопок */
--svg-colorButton: #333;                          /* Цвет иконок в кнопках */
--svg-sizeButton: 18px;                           /* Размер иконок SVG */
--glass-shadowButton: 4px 4px 8px rgba(0, 0, 0, 0.2);       /* Тень кнопок */
--glass-shadowButtonUp: 8px 8px 16px rgba(0, 0, 0, 0.2);    /* Тень поднятых кнопок */
```

### Таблицы и статусы

```css
--generalTabBorderColor: #000;                    /* Цвет границ таблицы */
--tableHeaderBg: #f0f0f0;                         /* Фон заголовков */
--tableHoverBg: rgba(255, 255, 255, 0.1);         /* Hover эффект */
--tableSelectedBorder: #4ade80;                   /* Рамка выбранной строки */
--tableSelectedBg: rgba(74, 222, 128, 0.2);       /* Фон выбранной строки */
--incomeColor: #4ade80;                           /* Доход (зелёный) */
--expenseColor: #f87171;                          /* Расход (красный) */
--statusCancelledColor: #9ca3af;                  /* Отменённые (серый) */
--warningColor: #fbbf24;                          /* Предупреждения (жёлтый) */
--infoColor: #3b82f6;                             /* Информация (синий) */
--linkColor: #1e40af;                             /* Ссылки телефона */
--tableTextColor: #000;                           /* Основной текст в таблицах */
--textMutedLightColor: rgba(255, 255, 255, 0.7);  /* Приглушённый светлый текст */
--text-colorAlert: #ff0000;                       /* Текст предупреждений */
```

### Календарь

```css
--calendar-bg: rgba(252, 250, 250, 0.8);          /* Фон сетки календаря */
--calendar-colorDayGrid: rgba(57, 255, 20, 0.1);  /* Подсветка ячеек дней */
--calendar-colorToday: rgba(28, 20, 255, 0.15);   /* Выделение текущего дня */
--calendar-colorMetka: rgb(35, 138, 26);          /* Цвет "ещё" для события */
```

### Вкладки

```css
--glass-tab-inactive-border: rgba(128, 128, 128, 0.5); /* Цвет границы неактивных вкладок */
```

---

## 🆕 Стили модалок (modal.css) - ПОЛНЫЙ РЕФАКТОРИНГ 2026-02-25

### 📊 Базовые правила модалок

| Параметр | Значение | Переменная |
|----------|----------|------------|
| **Текст (основной)** | 8px | `var(--generalTextSizeSmall)` |
| **Заголовки** | 10px | `var(--generalTextSize)` |
| **Padding** | 5px | `5px` (везде единообразно!) |
| **Gap** | 5px | `5px` (везде единообразно!) |
| **Border-radius** | 12px | `var(--panelRadius)` |
| **Border-radius (инпуты)** | 6px | `var(--glass-borderRadiusButton)` |
| **Width** | auto | По содержимому! |
| **Фон модалки** | rgb(228 228 228 / 85%) | `var(--glass-bgModal)` |
| **Blur overlay** | blur(10px) | `var(--glass-bgFilterOverlay)` |
| **Blur glass** | blur(3px) | `var(--glass-bgFilterModal)` |
| **Тень** | 4px 4px 6px | `var(--glass-shadowPanel)` |

### 🎯 Базовые контейнеры

```css
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.1);
  backdrop-filter: var(--glass-bgFilterOverlay); /* blur(10px) */
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-glass {
  background: var(--glass-bgModal);              /* rgb(228 228 228 / 85%) */
  backdrop-filter: var(--glass-bgFilterModal);   /* blur(3px) */
  border: 1px solid var(--generalColor);
  border-radius: var(--panelRadius);
  padding: 5px;                                  /* Везде 5px! */
  width: 150px;                                  /* Базовая ширина (min) */
  box-shadow: var(--glass-shadowPanel);
  display: flex;
  flex-direction: column;
  gap: 5px;                                      /* Везде 5px! */
  color: var(--generalTextColor);
  font-size: var(--generalTextSizeSmall);        /* 8px - основной текст */
}

/* Заголовок модалки (БЕЗ h2!) */
.modal-glassTitle {
  margin: 0;
  text-align: center;
  font-size: var(--generalTextSize);             /* 10px - заголовки */
  color: var(--generalTextColor);
}
```

### 📏 Модификаторы размера (только расположение!)

```css
.modal-wide {
  max-width: 600px;
  min-width: 250px;
}

.modal-large {
  max-width: 600px;
  min-width: 200px;
  max-height: 400px;
}

.view-modal {
  max-width: 600px;
  max-height: 80vh;
}

.actions-modal {
  width: auto;
  min-width: 200px;
}
```

### 📝 Формы

```css
.input-group {
  display: flex;
  flex-direction: column;
  gap: 2px 5px;
}

.input-row {
  display: flex;
  gap: 2px 5px;
  flex-wrap: nowrap;
}

.input-field {
  display: flex;
  flex-direction: column;
  gap: 1px;
  flex: 1;
  min-width: 50px;
}

.input-label {
  font-size: var(--generalTextSize);             /* 10px */
  color: var(--generalTextColor);
  font-weight: 500;
}

.modal-input {
  all: unset;
  background: var(--glass-bg);
  border: 1px solid var(--generalColor);
  border-radius: var(--glass-borderRadiusButton); /* 6px */
  padding: 5px;
  font-size: var(--generalTextSizeSmall);        /* 8px */
  color: var(--generalTextColor);
  box-sizing: border-box;
}

.modal-textarea {
  width: 100%;
  padding: 5px;
  font-size: var(--generalTextSizeSmall);        /* 8px */
  border: 1px solid var(--generalColor);
  border-radius: var(--glass-borderRadiusButton);
  background: var(--glass-bg);
  color: var(--generalTextColor);
  resize: vertical;
  min-height: 40px;
}
```

### 🎨 Информационные блоки (БЕЗ фонов!)

**ВАЖНО:** Все информационные блоки используют ЕДИНЫЙ фон `var(--glass-bg)` из `.modal-glass`!
Различие только в **цветах текста** через семантические переменные.

```css
.payment-info {
  border-radius: var(--glass-borderRadiusButton);
  padding: 5px;
  margin-bottom: 5px;
  /* БЕЗ background! */
}

.payment-info .remaining-amount {
  font-weight: 600;
  color: var(--expenseColor);  /* Красный для долга */
}

.delete-info {
  border-radius: var(--glass-borderRadiusButton);
  padding: 5px;
  margin-bottom: 5px;
  /* БЕЗ background! */
}

.delete-warning {
  color: var(--expenseColor);  /* Красный для предупреждения */
  font-weight: 600;
  text-align: center;
  margin-top: 5px;
}

.price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: var(--generalTextSizeSmall);
  color: var(--generalTextColor);
}

.price-value.discount-value {
  color: var(--expenseColor);  /* Красный для скидки */
}

.price-value.total-value {
  font-size: var(--generalTextSize);
  color: var(--generalTextColor);
}
```

### ⚠️ КРИТИЧНО: Разметка модалок

**ВСЕГДА использовать `.modal-glassTitle` вместо `<h2>`!**

```vue
<!-- ❌ НЕПРАВИЛЬНО -->
<div class="modal-glass">
  <h2>Заголовок</h2>
</div>

<!-- ✅ ПРАВИЛЬНО -->
<div class="modal-glass">
  <div class="modal-glassTitle">Заголовок</div>
</div>
```

**Обновлено 31 компонент:** AlertModal, ConfirmModal, LoginModal + все модалки Accounting + Home модалки.

---

## Sticky элементы

### 🚨 КРИТИЧНО: НЕ использовать position: fixed!

**ВСЕГДА использовать `position: sticky`**

### Эталон: Sidebar (sidebar.css)

```css
.side-bar {
  position: fixed;
  top: 40px;
  left: 0;
  bottom: 10px;
  width: 20px; /* Сложенное состояние */
  background: var(--glass-bg);
  backdrop-filter: var(--glass-bgFilter);
  border: 1px solid var(--generalColor);
  border-top-right-radius: var(--panelRadius);
  border-bottom-right-radius: var(--panelRadius);
  border-left: none;
  box-shadow: var(--glass-shadowPanel);
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 101;
}

.side-bar.expanded {
  width: 132px; /* Развернутое состояние */
}
```

### Эталон: Topbar (topbar.css)

```css
.top-bar {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: 40px;
  min-width: 266px;
  background: var(--glass-bg);
  backdrop-filter: var(--glass-bgFilter);
  border: 1px solid var(--generalColor);
  border-bottom-left-radius: var(--panelRadius);
  border-bottom-right-radius: var(--panelRadius);
  border-top: none;
  box-shadow: var(--glass-shadowPanel);
  z-index: 1000;
}

.owner-photo {
  width: var(--glass-widthButton) !important;
  height: var(--glass-heightButton) !important;
  border-bottom-left-radius: var(--panelRadius);
  border-bottom-right-radius: var(--panelRadius);
  border: 1px solid var(--generalColor);
  object-fit: cover;
}

.site-name {
  font-weight: bold;
  color: var(--generalTextColor);
  font-size: clamp(8px, var(--generalTextSizeSmall), 10px);
}
```

### Эталон: Панели с вкладками (padGlass.css)

```css
.glass-panel-tabs-sticky {
  position: sticky;
  top: 86px;  /* Отступ от топ-бара + sidebar margin */
  z-index: 100;
}
```

### Почему sticky, а не fixed?

| `position: sticky` | `position: fixed` |
|-------------------|-------------------|
| ✅ Автоматически адаптируется под размер экрана | ❌ Требует media queries |
| ✅ Работает без жёстких left/right | ❌ Ломается на мобилке |
| ✅ Относительно родительского контейнера | ❌ Относительно viewport |

---

## Классы кнопок

### glass-button (20x20, только иконка)

```css
.glass-button {
  width: var(--glass-widthButton);
  height: var(--glass-heightButton);
  background: var(--glass-bgButton);
  border: 1px solid var(--generalColor);
  border-radius: var(--glass-borderRadiusButton);
  cursor: pointer;
  box-shadow: var(--glass-shadowButton);
  /* ... */
}
```

**Использование:**
```vue
<button class="glass-button">
  <svg-icon :path="mdilIcon" />
</button>
```

### glass-button-text (63x20, иконка + текст)

```css
.glass-button-text {
  width: var(--glass-widthButtonText);
  height: var(--glass-heightButton);
  /* ... */
}
```

**Использование:**
```vue
<button class="glass-button-text">
  <svg-icon :path="mdilIcon" />
  <span>Текст</span>
</button>
```

---

## Классы панелей с вкладками (padGlass.css)

### 📂 Базовая панель

```css
.glass-panel {
  background: var(--glass-bg);
  border: 1px solid var(--generalColor);
  border-radius: var(--panelRadius);
  box-shadow: var(--glass-shadowPanel);
  backdrop-filter: blur(60px);
  padding: 6px;
}
```

### 📂 Структура панели с вкладками

```vue
<div class="glass-panel-tabs glass-panel-tabs-sticky">
  <div class="glass-panel-tabs-nav">
    <div class="tabs-group-left">
      <button class="glass-button-tab" :class="{ active: activeTab === 'tab1' }">
        Вкладка 1
      </button>
      <button class="glass-button-tab" :class="{ active: activeTab === 'tab2' }">
        Вкладка 2
      </button>
    </div>
    <div class="tabs-group-right">
      <button class="glass-button-tab period-filter-button">
        Период ▼
      </button>
    </div>
  </div>
  <div class="glass-panel-tabs-content">
    <!-- Контент вкладки -->
  </div>
</div>
```

### 🎨 Стили вкладок

```css
.glass-button-tab {
  height: 20px;
  padding: 4px;
  font-size: var(--generalTextSizeSmall);
  background: transparent;
  border: 1px solid var(--glass-tab-inactive-border);
  border-bottom: 1px solid var(--generalColor);
  border-radius: var(--glass-borderRadiusButton) var(--glass-borderRadiusButton) 0 0;
  backdrop-filter: blur(60px);
  cursor: pointer;
  z-index: 1;
}

.tabs-group-left .glass-button-tab:first-child {
  margin-left: var(--panelRadius); /* Сдвиг первой вкладки вправо */
}

.glass-button-tab.active {
  background: var(--glass-bg);
  border-color: var(--generalColor);
  border-bottom: 1px solid var(--bgGeneral);
  z-index: 3;
  margin-bottom: -1px;
}

.glass-panel-tabs-content {
  top: -1px;
  background: var(--glass-bg);
  border: 1px solid var(--generalColor);
  border-radius: var(--panelRadius);
  box-shadow: var(--glass-shadowPanel);
  backdrop-filter: blur(60px);
  padding: 6px;
  position: relative;
  z-index: 2;
}
```

---

## Импорт CSS в компонентах

**ТОЛЬКО в главных компонентах:**

```vue
<!-- App.vue -->
<script setup lang="ts">
import './assets/style.css'
import './assets/buttons.css'
import './assets/modal.css'
import './assets/layout.css'
import './assets/sidebar.css'
import './assets/topbar.css'
import './assets/padGlass.css'
import './assets/tables.css'
// ...
</script>
```

**НЕ импортировать в каждом компоненте!**

---

## 📝 Классы таблиц (tables.css)

### 📋 Панель инструментов

```css
.table-toolbar {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.table-actions {
  display: flex;
  gap: 8px;
}
```

### 🔍 Панель фильтров

```css
.filters-panel {
  display: flex;
  gap: 6px;
  margin-bottom: 3px;
  padding: 5px;
  background: rgba(255, 255, 255, 0.4);
  border: 1px solid var(--glass-tab-inactive-border);
  flex-wrap: wrap;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 50px;
}

.filter-label {
  font-size: var(--generalTextSize);
  color: var(--generalTextColor);
  font-weight: 600;
}

.filter-select {
  height: 13px;
  padding: 0 5px;
  background: rgba(255, 255, 255, 0.4);
  color: var(--generalTextColor);
  font-size: var(--generalTextSizeSmall);
  border: 1px solid var(--glass-tab-inactive-border);
  cursor: pointer;
  box-sizing: border-box;
}
```

---

## 🗂️ Мобильная адаптация (responsive.css)

**СОСТОЯНИЕ:** Файл очищен 2026-02-25!

Файл оставлен **пустым** для будущей реализации мобильной адаптации по roadmap.md (Этап 1: Desktop дизайн → Этап 2-8).

**16 компонентов** импортируют responsive.css, но он пустой = безопасно.

**Когда будем делать мобилку:**
- Заполним responsive.css новыми стилями
- Следуя roadmap.md и единому брейкпоинту `≤768px`

---

## 📚 История рефакторинга

### 2026-02-26: Анализ стилей кнопок
- ✅ Проанализированы все CSS файлы проекта
- ✅ Найдены стили кнопок в 7 файлах: buttons.css, padGlass.css, tiptap.css, topbar.css, sidebar.css, calendar.css, modal.css
- ✅ Выявлено 6 проблем: дублирование, закомментированный код, отсутствие комментариев, разбросанность стилей
- ✅ Составлена структура нового buttons.css (13 блоков)
- ✅ Определены правила комментирования (БЕЗ конкретных значений переменных!)
- ✅ Создан план рефакторинга (6 этапов)
- ✅ Подробности в [buttons-refactoring.md](buttons-refactoring.md)

### 2026-02-25: Полный рефакторинг модалок
- ✅ Создана переменная `--glass-bgFilterModal: blur(3px)`
- ✅ Создана переменная `--glass-bgFilterOverlay: blur(10px)`
- ✅ Создана переменная `--glass-bgModal: rgb(228 228 228 / 85%)`
- ✅ Удалена мобильная адаптация из modal.css (-68 строк)
- ✅ Все размеры текста → переменные (`--generalTextSizeSmall`, `--generalTextSize`)
- ✅ Единый padding: 5px (везде!)
- ✅ Единый gap: 5px (везде!)
- ✅ Все border-radius → `var(--panelRadius)` / `var(--glass-borderRadiusButton)`
- ✅ Все width → auto (по содержимому!)
- ✅ Все цвета → семантические переменные
- ✅ Удалены фоны из информационных блоков (единый `var(--glass-bg)`)
- ✅ Создан класс `.modal-glassTitle` (вместо `.modal-glass h2`)
- ✅ Обновлено 31 компонент: `<h2>` → `<div class="modal-glassTitle">`
- ✅ Очищен responsive.css (удалены дубликаты и хардкод)
- ✅ **Использование переменных в modal.css:** 100%
