# SearchTable Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Создать универсальный компонент поиска `SearchTable.vue` и реорганизовать каталог `ui/` — перенести DatePicker в подпапку, добавить новый паттерн поиска, интегрировать в `BookingsTable.vue`.

**Architecture:** Компонент `SearchTable.vue` — простой `v-model` инпут с иконкой лупы, счётчиком результатов и дебаунсом 400ms. Фильтрация на фронтенде через TanStack `globalFilter`. Каждый UI-паттерн живёт в своей подпапке `ui/<name>/`.

**Tech Stack:** Vue 3, TypeScript, TanStack Table (`@tanstack/vue-table`), `@mdi/js` + `@jamescoyle/vue-icon`, CSS переменные из `style.css`.

**Spec:** `docs/superpowers/specs/2026-03-15-search-table-design.md`

---

## Chunk 1: Реорганизация `ui/` — перемещение DatePicker

### Task 1: Переместить DatePicker в подпапку

**Files:**
- Create: `maribulka-vue/src/components/ui/datePicker/DatePicker.vue` (перемещение)
- Create: `maribulka-vue/src/components/ui/datePicker/datePicker.css` (перемещение)
- Delete: `maribulka-vue/src/components/ui/DatePicker.vue`
- Delete: `maribulka-vue/src/components/ui/datePicker.css`
- Modify: `maribulka-vue/src/main.ts:11`
- Modify: `maribulka-vue/src/components/calendar/BookingsTable.vue:11`
- Modify: `maribulka-vue/src/components/calendar/BookingFormModal.vue:10`

- [ ] **Step 1: Создать папку и скопировать файлы**

```bash
cd maribulka-vue
mkdir src/components/ui/datePicker
cp src/components/ui/DatePicker.vue src/components/ui/datePicker/DatePicker.vue
cp src/components/ui/datePicker.css src/components/ui/datePicker/datePicker.css
```

- [ ] **Step 2: Удалить старые файлы**

```bash
rm src/components/ui/DatePicker.vue
rm src/components/ui/datePicker.css
```

- [ ] **Step 3: Обновить импорт в `main.ts`**

Файл: `maribulka-vue/src/main.ts`, строка 11.

Было:
```typescript
import './components/ui/datePicker.css'
```

Стало:
```typescript
import './components/ui/datePicker/datePicker.css'
```

- [ ] **Step 4: Обновить импорт в `BookingsTable.vue`**

Файл: `maribulka-vue/src/components/calendar/BookingsTable.vue`, строка 11.

Было:
```typescript
import DatePicker, { type DateRange } from '../ui/DatePicker.vue'
```

Стало:
```typescript
import DatePicker, { type DateRange } from '../ui/datePicker/DatePicker.vue'
```

- [ ] **Step 5: Обновить импорт в `BookingFormModal.vue`**

Файл: `maribulka-vue/src/components/calendar/BookingFormModal.vue`, строка 10.

Было:
```typescript
import DatePicker from '../ui/DatePicker.vue'
```

Стало:
```typescript
import DatePicker from '../ui/datePicker/DatePicker.vue'
```

- [ ] **Step 6: Проверить сборку**

```bash
cd maribulka-vue
npm run build
```

Ожидаем: сборка без ошибок. DatePicker работает как прежде.

---

## Chunk 2: Создание паттерна `SearchTable`

### Task 2: Создать `searchTable.css`

**Files:**
- Create: `maribulka-vue/src/components/ui/searchTable/searchTable.css`

- [ ] **Step 1: Создать папку и файл CSS**

```bash
mkdir maribulka-vue/src/components/ui/searchTable
```

Создать файл `maribulka-vue/src/components/ui/searchTable/searchTable.css`:

```css
/* ========================================
   SearchTable — стеклянный инпут поиска
   searchTable.css
   ======================================== */

/* ── Обёртка ── */
.search-table {
  position: relative;
  display: flex;
  align-items: center;
  gap: 6px;
  box-sizing: border-box;
  padding: 8px 12px;
  font-size: var(--genTextSize);
  font-family: inherit;
  color: var(--text-primary);
  background: var(--glass-bg);
  border: 1px solid var(--glass-border);
  border-radius: calc(var(--padRadius) / 2);
  outline: none;
  transition: border-color 0.2s ease;
  min-width: 180px;
}

.search-table--active {
  border-color: var(--text-secondary);
}

/* ── Иконка лупы ── */
.search-table__icon {
  flex-shrink: 0;
  width: 18px;
  height: 18px;
  color: var(--text-secondary);
  transition: color 0.2s ease;
}

.search-table--active .search-table__icon {
  color: var(--text-primary);
}

/* ── Счётчик результатов ── */
.search-table__count {
  flex-shrink: 0;
  font-size: var(--genTextSize);
  color: var(--text-secondary);
  white-space: nowrap;
}

/* ── Поле ввода ── */
.search-table__input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: var(--genTextSize);
  font-family: inherit;
  color: var(--text-primary);
  min-width: 0;
}

.search-table__input::placeholder {
  color: var(--text-secondary);
}
```

### Task 3: Создать `SearchTable.vue`

**Files:**
- Create: `maribulka-vue/src/components/ui/searchTable/SearchTable.vue`

- [ ] **Step 1: Создать компонент**

Создать файл `maribulka-vue/src/components/ui/searchTable/SearchTable.vue`:

```vue
<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiMagnify } from '@mdi/js'

const props = defineProps<{
  modelValue: string
  count?: number
  placeholder?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

let debounceTimer: ReturnType<typeof setTimeout> | null = null

function onInput(e: Event) {
  const value = (e.target as HTMLInputElement).value
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    emit('update:modelValue', value)
  }, 400)
}

onUnmounted(() => {
  if (debounceTimer) clearTimeout(debounceTimer)
})
</script>

<template>
  <div class="search-table" :class="{ 'search-table--active': modelValue }">
    <svg-icon type="mdi" :path="mdiMagnify" class="search-table__icon" />
    <span v-if="modelValue && (count ?? 0) >= 1" class="search-table__count">[{{ count }}]</span>
    <input
      class="search-table__input"
      type="text"
      :value="modelValue"
      :placeholder="placeholder ?? 'Поиск...'"
      @input="onInput"
    />
  </div>
</template>
```

### Task 4: Подключить CSS в `main.ts`

**Files:**
- Modify: `maribulka-vue/src/main.ts`

- [ ] **Step 1: Добавить импорт после `datePicker/datePicker.css`**

Файл: `maribulka-vue/src/main.ts`.

После строки:
```typescript
import './components/ui/datePicker/datePicker.css'
```

Добавить:
```typescript
import './components/ui/searchTable/searchTable.css'
```

- [ ] **Step 2: Проверить сборку**

```bash
cd maribulka-vue
npm run build
```

Ожидаем: сборка без ошибок.

---

## Chunk 3: Интеграция в `BookingsTable.vue`

### Task 5: Интегрировать SearchTable в BookingsTable

**Files:**
- Modify: `maribulka-vue/src/components/calendar/BookingsTable.vue`

- [ ] **Step 1: Добавить импорты в script**

В блоке импортов `@tanstack/vue-table` добавить `getFilteredRowModel`:

Было:
```typescript
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  type ColumnDef,
  type SortingState,
  FlexRender
} from '@tanstack/vue-table'
```

Стало:
```typescript
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  type ColumnDef,
  type SortingState,
  FlexRender
} from '@tanstack/vue-table'
```

- [ ] **Step 2: Добавить импорт компонента и ref**

После строки `import DatePicker, { type DateRange } from '../ui/datePicker/DatePicker.vue'` добавить:

```typescript
import SearchTable from '../ui/searchTable/SearchTable.vue'
```

После строки `const sorting = ref<SortingState>(...)` добавить:

```typescript
const searchQuery = ref('')
```

- [ ] **Step 3: Обновить `useVueTable`**

Добавить `globalFilter` в `state`, `onGlobalFilterChange`, `getFilteredRowModel`, `globalFilterFn`.

Было:
```typescript
const table = useVueTable({
  get data() { return bookingsStore.bookings },
  columns,
  state: {
    get sorting() { return sorting.value },
  },
  onSortingChange: updater => {
    sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater
  },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
})
```

Стало:
```typescript
const table = useVueTable({
  get data() { return bookingsStore.bookings },
  columns,
  state: {
    get sorting() { return sorting.value },
    get globalFilter() { return searchQuery.value },
  },
  onSortingChange: updater => {
    sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater
  },
  onGlobalFilterChange: updater => {
    searchQuery.value = typeof updater === 'function' ? updater(searchQuery.value) : updater
  },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  globalFilterFn: 'includesString',
})
```

- [ ] **Step 4: Добавить `SearchTable` в template**

Найти в template:
```html
<div class="bookings-table-filter">
  <DatePicker mode="range" v-model="dateRange" :showPresets="true" />
</div>
```

Заменить на:
```html
<div class="bookings-table-filter">
  <DatePicker mode="range" v-model="dateRange" :showPresets="true" />
  <SearchTable
    v-model="searchQuery"
    :count="table.getFilteredRowModel().rows.length"
    placeholder="Поиск по записям..."
  />
</div>
```

- [ ] **Step 5: Финальная проверка сборки**

```bash
cd maribulka-vue
npm run build
```

Ожидаем: сборка без ошибок, нет TypeScript ошибок.

- [ ] **Step 6: Проверить в браузере**

```bash
npm run dev
```

Проверить:
1. DatePicker работает как прежде
2. `SearchTable` отображается рядом с DatePicker в одной строке
3. Ввод текста → через 400ms таблица фильтруется
4. Если найдено >= 1 результат → счётчик `[N]` появляется
5. Если 0 результатов → счётчик скрыт
6. Очистка инпута → таблица показывает все записи
7. Смена периода → поисковая строка остаётся, таблица обновляется
8. При активном вводе — рамка инпута подсвечивается

---

## Итоговая структура файлов

```
src/components/ui/
├── datePicker/
│   ├── DatePicker.vue       ← перемещён
│   └── datePicker.css       ← перемещён
└── searchTable/
    ├── SearchTable.vue      ← новый
    └── searchTable.css      ← новый

src/main.ts                  ← обновлён 1 импорт datePicker (Chunk 1) + добавлен 1 импорт searchTable (Chunk 2)
src/components/calendar/
  ├── BookingsTable.vue      ← обновлён импорт + интеграция SearchTable
  └── BookingFormModal.vue   ← обновлён импорт DatePicker
```
