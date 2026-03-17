# SelectBox Move + UI Patterns Documentation Plan

> **For agentic workers:** REQUIRED: Use superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:**
1. Переподключить импорты SelectBox после перемещения файлов в `src/components/ui/selectBox/`
2. Задокументировать 3 самодостаточных UI-паттерна в памяти (`ui-patterns.md`)

**Context:**
Пользователь вручную переместил:
- `src/components/SelectBox.vue` → `src/components/ui/selectBox/SelectBox.vue`
- `src/assets/selectBox.css` → `src/components/ui/selectBox/selectBox.css`

Старые файлы удалены. Теперь нужно обновить все импорты.

**Architecture:**
После выполнения все три UI-паттерна живут в `src/components/ui/`:
```
src/components/ui/
├── selectBox/
│   ├── SelectBox.vue
│   └── selectBox.css
├── datePicker/
│   ├── DatePicker.vue
│   └── datePicker.css
└── searchTable/
    ├── SearchTable.vue
    └── searchTable.css
```

**Tech Stack:** Vue 3, TypeScript, CSS-переменные из `style.css`.

---

## Chunk 1: Обновить импорты SelectBox

### Task 1: Обновить импорт CSS в `main.ts`

**Files:**
- Modify: `maribulka-vue/src/main.ts`

- [x] **Step 1: Заменить строку импорта selectBox.css**

В `main.ts` найти строку:
```ts
import './assets/selectBox.css'
```
Заменить на:
```ts
import './components/ui/selectBox/selectBox.css'
```

---

### Task 2: Обновить импорт в `RefundExpenseModal.vue`

**Files:**
- Modify: `maribulka-vue/src/components/finance/expenses/RefundExpenseModal.vue`

- [x] **Step 1: Заменить импорт компонента**

Найти строку:
```ts
import SelectBox from '../../SelectBox.vue'
```
Заменить на:
```ts
import SelectBox from '../../ui/selectBox/SelectBox.vue'
```

---

### Task 3: Обновить импорт в `AddExpenseModal.vue`

**Files:**
- Modify: `maribulka-vue/src/components/finance/expenses/AddExpenseModal.vue`

- [x] **Step 1: Заменить импорт компонента**

Найти строку:
```ts
import SelectBox from '../../SelectBox.vue'
```
Заменить на:
```ts
import SelectBox from '../../ui/selectBox/SelectBox.vue'
```

---

### Task 4: Обновить импорт в `BookingFormModal.vue`

**Files:**
- Modify: `maribulka-vue/src/components/calendar/BookingFormModal.vue`

- [x] **Step 1: Заменить импорт компонента**

Найти строку:
```ts
import SelectBox from '../SelectBox.vue'
```
Заменить на:
```ts
import SelectBox from '../ui/selectBox/SelectBox.vue'
```

---

## Chunk 2: Документация 3 UI-паттернов

### Task 5: Создать `ui-patterns.md` в памяти

**Files:**
- Create: `C:\Users\sava\.claude\projects\d--GitHub-maribulka\memory\ui-patterns.md`
- Modify: `C:\Users\sava\.claude\projects\d--GitHub-maribulka\memory\MEMORY.md`

- [x] **Step 1: Создать `ui-patterns.md`**

Файл содержит подробное описание трёх паттернов:

**Паттерн 1 — SelectBox** (`src/components/ui/selectBox/`)
- Что: кастомный дропдаун, замена нативного `<select>`, glass-стиль
- Когда: везде где нужен выпадающий список (формы, фильтры)
- Props: `modelValue`, `options: { value, label }[]`, `placeholder?`, `disabled?`
- Тип option: `{ value: string | number, label: string }`
- Попап через `<Teleport to="body">` — не нужен `position: relative` на родителе
- CSS: самодостаточный, все классы с префиксом `combo-box-`
- CSS-классы:
  - `.combo-box-wrap` — обёртка-триггер (стеклянный инпут)
  - `.combo-box-wrap--open` — открытое состояние
  - `.combo-box-wrap--disabled` — заблокирован
  - `.combo-box-value` — текст выбранного значения
  - `.combo-box-placeholder` — плейсхолдер (вторичный цвет)
  - `.combo-box-arrow` — иконка стрелки (rotate 180° при открытии)
  - `.combo-box-dropdown` — список (fixed, z-index 9999)
  - `.combo-box-option` — вариант списка
  - `.combo-box-option--selected` — выбранный вариант

Пример использования:
```vue
<SelectBox
  v-model="form.categoryId"
  :options="categories.map(c => ({ value: c.id, label: c.name }))"
  placeholder="Выберите категорию"
/>
```

---

**Паттерн 2 — DatePicker** (`src/components/ui/datePicker/`)
- Что: кастомный выбор даты/периода, glass-стиль, заменил flatpickr (15.03.2026)
- Когда: любой выбор даты или диапазона дат
- Props:
  - `mode?: 'single' | 'range'` (по умолчанию `'single'`)
  - `modelValue?: string | DateRange`
  - `minDate?: string` (YYYY-MM-DD)
  - `maxDate?: string` (YYYY-MM-DD)
  - `placeholder?: string`
  - `showToday?: boolean` (по умолчанию `true`) — кнопка "Сегодня"
  - `showPresets?: boolean` (по умолчанию `false`) — кнопки "1 мес / 3 мес / 6 мес" (только range)
- Экспортирует тип: `DateRange { from: string, to: string }` (YYYY-MM-DD)
- v-model:
  - `single` → `string` (YYYY-MM-DD)
  - `range` → `DateRange`
- Попап через `<Teleport to="body">`, позиционируется автоматически
- Esc закрывает попап; незавершённый range сбрасывается при закрытии
- CSS: самодостаточный, все классы с префиксом `dp-`
- CSS-классы ключевые:
  - `.dp-wrap` — обёртка (flex, содержит инпут + кнопки)
  - `.dp-input` — поле отображения даты (стиль как у SelectBox)
  - `.dp-input--open` — открытое состояние
  - `.dp-btn-today` — кнопка "Сегодня" / пресеты
  - `.dp-popup` — попап-календарь (fixed)
  - `.dp-day--selected` — выбранный день
  - `.dp-day--range` — дни внутри диапазона
  - `.dp-day--today` — сегодня (рамка)
  - `.dp-day--disabled` — недоступный день

Пример single:
```vue
<DatePicker v-model="form.date" mode="single" />
```

Пример range:
```vue
<DatePicker
  v-model="period"
  mode="range"
  :showPresets="true"
/>
// period: DateRange = { from: '2026-03-01', to: '2026-03-31' }
```

Импорт типа DateRange:
```ts
import DatePicker, { type DateRange } from '@/components/ui/datePicker/DatePicker.vue'
```

---

**Паттерн 3 — SearchTable** (`src/components/ui/searchTable/`)
- Что: стеклянный инпут поиска для таблиц с debounce 400мс и счётчиком результатов
- Когда: поиск/фильтрация по таблице
- Props:
  - `modelValue: string` — текущий поисковый запрос
  - `count?: number` — количество найденных строк (показывает `[N]` рядом с иконкой)
  - `placeholder?: string` (по умолчанию `'Поиск...'`)
- Debounce 400мс встроен — не нужно делать снаружи
- `count` отображается только когда `modelValue` не пустой и `count >= 1`
- CSS: самодостаточный, все классы с префиксом `search-table`
- CSS-классы:
  - `.search-table` — обёртка (стеклянный инпут, flex)
  - `.search-table--active` — есть текст поиска (рамка вторичным цветом)
  - `.search-table__icon` — иконка лупы
  - `.search-table__count` — счётчик `[N]`
  - `.search-table__input` — поле ввода (прозрачный фон)

Пример использования:
```vue
<SearchTable
  v-model="searchQuery"
  :count="filteredRows.length"
  placeholder="Поиск по расходам..."
/>
```

Фильтрация в computed:
```ts
const filteredRows = computed(() => {
  if (!searchQuery.value) return allRows.value
  const q = searchQuery.value.toLowerCase()
  return allRows.value.filter(r =>
    r.description.toLowerCase().includes(q) ||
    r.category.toLowerCase().includes(q)
  )
})
```

- [x] **Step 2: Обновить `MEMORY.md` — добавить ссылку на `ui-patterns.md`**

В раздел оглавления добавить строку:
```
- **[ui-patterns.md](ui-patterns.md)** - 🆕 три UI-паттерна: SelectBox, DatePicker, SearchTable — API, CSS-классы, примеры использования
```
