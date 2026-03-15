# SearchTable — Дизайн-спецификация

**Дата:** 2026-03-15
**Статус:** Approved by user

---

## Цель

Создать универсальный переиспользуемый компонент поиска `SearchTable.vue` для фильтрации данных в таблицах проекта. Первое применение — `BookingsTable.vue`.

---

## Реорганизация каталога `ui/`

**До:**
```
src/components/ui/
├── DatePicker.vue
└── datePicker.css
```

**После:**
```
src/components/ui/
├── datePicker/
│   ├── DatePicker.vue
│   └── datePicker.css
└── searchTable/
    ├── SearchTable.vue
    └── searchTable.css
```

### Файлы с импортами DatePicker (требуют обновления пути):

| Файл | Старый импорт | Новый импорт |
|------|--------------|--------------|
| `src/main.ts` | `'./components/ui/datePicker.css'` | `'./components/ui/datePicker/datePicker.css'` |
| `src/components/calendar/BookingsTable.vue` | `'../ui/DatePicker.vue'` | `'../ui/datePicker/DatePicker.vue'` |
| `src/components/calendar/BookingFormModal.vue` | `'../ui/DatePicker.vue'` | `'../ui/datePicker/DatePicker.vue'` |

---

## Компонент `SearchTable.vue`

### Props и Emits

```typescript
const props = defineProps<{
  modelValue: string       // v-model — текст поиска
  count?: number           // кол-во найденных строк (от родителя)
  placeholder?: string     // по умолчанию 'Поиск...'
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
```

### Поведение

- **Дебаунс 400ms** — emit срабатывает через 400ms после последнего ввода
- **Счётчик `[N]`** — отображается внутри инпута слева от текста, только если `count >= 1` И `modelValue` не пустой
- **Пустой инпут** → emit('') → родитель сбрасывает globalFilter → таблица показывает все строки
- **Активный фильтр** (текст введён) — рамка инпута подсвечивается через CSS-класс
- **Без кнопок** — очистка только вручную (backspace/delete)

### HTML структура

```html
<div class="search-table" :class="{ 'search-table--active': modelValue }">
  <svg-icon type="mdi" :path="mdiMagnify" class="search-table__icon" />
  <span v-if="modelValue && count >= 1" class="search-table__count">[{{ count }}]</span>
  <input
    class="search-table__input"
    type="text"
    :value="modelValue"
    :placeholder="placeholder ?? 'Поиск...'"
    @input="onInput"
  />
</div>
```

### CSS паттерн (`searchTable.css`)

- Использует **только** CSS переменные из `style.css`
- Стиль инпута — **точно как `.dp-input`** из `datePicker.css`: `padding: 8px 12px`, `font-size: var(--genTextSize)`, `background: var(--glass-bg)`, `border: 1px solid var(--glass-border)`, `border-radius: calc(var(--padRadius) / 2)`. Паттерн самодостаточный — стиль описан в своём CSS, не ссылается на datePicker.
- Активное состояние — подсветка рамки через `.search-table--active`
- Иконка лупы — цвет `var(--text-secondary)`, при активном — `var(--text-primary)`
- Счётчик — цвет `var(--text-secondary)`, `font-size: var(--genTextSize)` (primarysize, не мельче)

---

## Интеграция в `BookingsTable.vue`

### Шаг 1 — добавить в script

```typescript
import { getFilteredRowModel } from '@tanstack/vue-table'
import SearchTable from '../ui/searchTable/SearchTable.vue'

const searchQuery = ref('')
```

### Шаг 2 — добавить в useVueTable

```typescript
const table = useVueTable({
  // ...существующий код...
  state: {
    get sorting() { return sorting.value },
    get globalFilter() { return searchQuery.value },
  },
  onGlobalFilterChange: val => { searchQuery.value = val },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),   // добавить
  globalFilterFn: 'includesString',              // добавить
})
```

> ⚠️ **Важно:** `globalFilter` работает на фронтенде по данным загруженным за выбранный период.
> При смене периода бэкенд отдаёт новые данные, поисковая строка не сбрасывается — фильтр применяется к новым данным автоматически.
> Ограничение: поиск идёт по **сырым значениям полей**, не по отображаемому тексту.
> Поиск "Новый" **не найдёт** статус `new` — в данных хранится `"new"`, а "Новый" это результат `getStatusText()`.
> Аналогично с датами: ищем `"2026-03-15"`, не `"15.03.26"`.
> Поиск работает по: ID заказа, имени клиента, телефону, типу съёмки — этого достаточно.

### Шаг 3 — добавить в template (рядом с DatePicker)

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

### При смене периода

- `dateRange` меняется → `bookingsStore.fetchBookings()` загружает новые данные
- `searchQuery` **не сбрасывается** — TanStack автоматически применяет тот же `globalFilter` к новым данным

---

## Порядок выполнения работ

1. Переместить `DatePicker.vue` и `datePicker.css` в `ui/datePicker/`
2. Обновить 3 импорта (main.ts, BookingsTable.vue, BookingFormModal.vue)
3. Создать `ui/searchTable/searchTable.css`
4. Создать `ui/searchTable/SearchTable.vue`
5. Добавить `import './components/ui/searchTable/searchTable.css'` в `main.ts`
6. Интегрировать `SearchTable` в `BookingsTable.vue`
7. Проверить сборку `npm run build`

---

## Ограничения

- **NO `<style>` блоков** в `.vue` файлах — только внешний CSS
- **Только CSS переменные** из `style.css` — никаких хардкодных цветов
- **Только `@mdi/js` иконки** — `mdiMagnify` для лупы
- **NO кнопки сброса** — очистка только вручную
