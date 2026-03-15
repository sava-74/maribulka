# DatePicker.vue Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Создать полностью самостоятельный компонент `DatePicker.vue` для выбора даты и диапазона дат — без зависимости от flatpickr, со стеклянными стилями проекта; затем мигрировать на него `BookingFormModal.vue` и `BookingsTable.vue` и удалить flatpickr из проекта.

**Architecture:** Компонент реализует собственный попап-календарь на чистом Vue 3 + TypeScript. Все стили в `datePicker.css` (префикс `.dp-`), изолированы от остальных CSS-файлов. v-model наружу отдаёт `YYYY-MM-DD` (single) или `{ from, to }` (range); отображение — `ДД.ММ.ГГ`.

**Tech Stack:** Vue 3, TypeScript, @mdi/js, @jamescoyle/vue-icon — никаких новых зависимостей.

**Spec:** `docs/superpowers/specs/2026-03-15-datepicker-design.md`

---

## File Structure

```text
СОЗДАТЬ:
  maribulka-vue/src/components/ui/DatePicker.vue   — компонент
  maribulka-vue/src/components/ui/datePicker.css   — все стили с префиксом .dp-

ИЗМЕНИТЬ:
  maribulka-vue/src/main.ts                        — заменить import flatpickr.css → import datePicker.css
  maribulka-vue/src/components/calendar/BookingFormModal.vue  — миграция на DatePicker
  maribulka-vue/src/components/calendar/BookingsTable.vue     — миграция на DatePicker

УДАЛИТЬ:
  maribulka-vue/src/assets/flatpickr.css
```

---

## Chunk 1: CSS — стили компонента

### Task 1: Создать datePicker.css

**Files:**

- Create: `maribulka-vue/src/components/ui/datePicker.css`

- [ ] **Step 1: Создать файл с переменными темы и базовыми стилями инпута**

```css
/* ========================================
   DatePicker — стеклянный компонент выбора даты
   datePicker.css
   ======================================== */

/* ── Переменные выбранного дня (по теме) ── */
[data-theme] {
  --dp-selected-bg: rgba(255, 255, 255, 0.25);
}
[data-theme="light"] {
  --dp-selected-bg: rgba(68, 87, 197, 0.25);
}

/* ── Обёртка: инпут + кнопка рядом ── */
.dp-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* ── Инпут (стиль combo-box-wrap из selectBox.css, скопирован) ── */
.dp-input {
  position: relative;
  box-sizing: border-box;
  width: 100%;
  padding: 8px 12px;
  font-size: var(--genTextSize);
  font-family: inherit;
  color: var(--text-primary);
  background: var(--glass-bg);
  border: 1px solid var(--glass-border);
  border-radius: calc(var(--padRadius) / 2);
  outline: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  user-select: none;
  transition: border-color 0.2s ease;
}

.dp-input:focus,
.dp-input--open {
  border-color: var(--text-secondary);
}

.dp-input-value {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dp-input-placeholder {
  color: var(--text-secondary);
}

.dp-input-arrow {
  flex-shrink: 0;
  width: 18px;
  height: 18px;
  color: var(--text-secondary);
  transition: transform 0.2s ease;
}

.dp-input--open .dp-input-arrow {
  transform: rotate(180deg);
}
```

- [ ] **Step 2: Добавить стили попапа**

```css
/* ── Попап-календарь ── */
.dp-popup {
  position: fixed;
  z-index: 1000;
  background: var(--glass-bg);
  backdrop-filter: blur(var(--padBlur)) saturate(180%);
  -webkit-backdrop-filter: blur(var(--padBlur)) saturate(180%);
  border: 2px solid var(--glass-border);
  border-style: groove;
  border-radius: var(--padRadius);
  box-shadow: var(--glass-shadow);
  color: var(--text-primary);
  min-width: 280px;
  padding: 12px;
  box-sizing: border-box;
}

/* ── Шапка: ← [Март ▼] 2026 → ── */
.dp-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 10px;
}

.dp-nav-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-primary);
  padding: 2px;
  display: flex;
  align-items: center;
  border-radius: calc(var(--padRadius) / 3);
  transition: background 0.15s;
}

.dp-nav-btn:hover {
  background: var(--glass-border);
}

.dp-header-center {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: var(--genTextSize);
}

.dp-year {
  color: var(--text-primary);
  font-size: var(--genTextSize);
}

/* ── Подпись режима range ── */
.dp-range-label {
  font-size: var(--genTextSizeSmall);
  color: var(--text-secondary);
  text-align: center;
  margin-bottom: 8px;
}

/* ── Дни недели ── */
.dp-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  margin-bottom: 4px;
  border-bottom: 1px solid var(--glass-border);
  padding-bottom: 4px;
}

.dp-weekday {
  text-align: center;
  font-size: var(--genTextSizeSmall);
  color: var(--text-secondary);
  padding: 4px 0;
}

/* ── Сетка дней ── */
.dp-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 2px;
}

.dp-day {
  text-align: center;
  padding: 6px 2px;
  font-size: var(--genTextSize);
  color: var(--text-primary);
  cursor: pointer;
  border-radius: calc(var(--padRadius) / 3);
  border: 1px solid transparent;
  transition: background 0.15s, border-color 0.15s;
  user-select: none;
}

.dp-day:hover:not(.dp-day--other):not(.dp-day--disabled) {
  background: var(--glass-border);
}

.dp-day--other {
  color: var(--text-secondary);
  opacity: 0.5;
  cursor: default;
  pointer-events: none;
}

.dp-day--today {
  border-color: var(--text-secondary);
}

.dp-day--selected {
  background: var(--dp-selected-bg);
  font-weight: 600;
}

.dp-day--range {
  background: var(--glass-border);
  opacity: 0.5;
}

.dp-day--range-hover {
  background: var(--glass-border);
  opacity: 0.3;
}

.dp-day--disabled {
  color: var(--text-secondary);
  opacity: 0.4;
  cursor: not-allowed;
  pointer-events: none;
}
```

- [ ] **Step 3: Добавить стили дропдауна месяца**

```css
/* ── Триггер дропдауна месяца ── */
.dp-month-trigger {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  font-size: var(--genTextSize);
  font-family: inherit;
  color: var(--text-primary);
  background: var(--glass-bg);
  border: 1px solid var(--glass-border);
  border-radius: calc(var(--padRadius) / 2);
  cursor: pointer;
  user-select: none;
  transition: border-color 0.2s ease;
}

.dp-month-trigger:hover {
  border-color: var(--text-secondary);
}

/* ── Дропдаун месяца (стиль combo-box-dropdown, скопирован) ── */
.dp-dropdown {
  position: fixed;
  z-index: 100000;
  background: var(--glass-bg);
  border: 2px solid var(--glass-border);
  border-style: groove;
  border-radius: var(--padRadius);
  backdrop-filter: blur(var(--padBlur)) saturate(180%);
  -webkit-backdrop-filter: blur(var(--padBlur)) saturate(180%);
  box-shadow: var(--glass-shadow);
  overflow-y: auto;
  max-height: 200px;
}

.dp-dropdown-option {
  padding: 8px 12px;
  font-size: var(--genTextSize);
  color: var(--text-secondary);
  cursor: pointer;
  transition: background 0.15s;
}

.dp-dropdown-option:hover {
  color: var(--text-primary);
  background: var(--glass-border);
}

.dp-dropdown-option--selected {
  color: var(--text-primary);
  font-weight: 600;
}
```

- [ ] **Step 4: Проверить файл визуально** — открыть `datePicker.css`, убедиться что нет хардкодов цветов и `!important`, все классы с префиксом `.dp-`

---

## Chunk 2: Компонент DatePicker.vue — логика и шаблон

### Task 2: Создать DatePicker.vue — типы и вспомогательные функции

**Files:**

- Create: `maribulka-vue/src/components/ui/DatePicker.vue`

- [ ] **Step 1: Создать скелет компонента с типами и утилитами дат**

```vue
<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {
  mdiChevronDown,
  mdiChevronLeft,
  mdiChevronRight,
  mdiClose,
  mdiCalendarToday
} from '@mdi/js'
// CSS импортируется глобально в main.ts — не дублировать здесь

// ── Типы ──────────────────────────────────────────────────────────

export interface DateRange {
  from: string  // YYYY-MM-DD
  to: string    // YYYY-MM-DD
}

interface Props {
  mode?: 'single' | 'range'
  modelValue?: string | DateRange
  minDate?: string        // YYYY-MM-DD
  maxDate?: string        // YYYY-MM-DD
  placeholder?: string
  showToday?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'single',
  showToday: true,
})

const emit = defineEmits<{
  'update:modelValue': [value: string | DateRange]
}>()

// ── Утилиты дат ──────────────────────────────────────────────────

/** YYYY-MM-DD → Date */
function parseDate(s: string): Date {
  const [y, m, d] = s.split('-').map(Number)
  return new Date(y!, m! - 1, d!)
}

/** Date → YYYY-MM-DD */
function toISO(d: Date): string {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

/** YYYY-MM-DD → ДД.ММ.ГГ */
function toDisplay(s: string): string {
  if (!s) return ''
  const [y, m, d] = s.split('-')
  const yy = y!.slice(-2)
  return `${d}.${m}.${yy}`
}

/** YYYY-MM-DD сравнение (только дата, без времени) */
function isSameDay(a: string, b: string): boolean {
  return a === b
}

function isBeforeDay(a: string, b: string): boolean {
  return a < b
}

function isAfterDay(a: string, b: string): boolean {
  return a > b
}

/** Сегодня в YYYY-MM-DD */
function todayISO(): string {
  return toISO(new Date())
}
</script>

<template>
  <div><!-- placeholder --></div>
</template>
```

- [ ] **Step 2: Добавить состояние открытия попапа и внутреннее состояние выбранной даты**

В `<script setup>` после утилит:

```ts
// ── Состояние ────────────────────────────────────────────────────

const isOpen = ref(false)

// Внутреннее значение (YYYY-MM-DD)
const internalSingle = ref<string>('')
const internalRange = ref<{ from: string; to: string }>({ from: '', to: '' })

// Текущий месяц/год в попапе
const viewYear = ref(new Date().getFullYear())
const viewMonth = ref(new Date().getMonth()) // 0-11

// Ссылки на DOM
const wrapRef = ref<HTMLElement | null>(null)
const popupRef = ref<HTMLElement | null>(null)

// range: шаг выбора ('from' | 'to')
const rangeStep = ref<'from' | 'to'>('from')

// hover-день для preview диапазона (YYYY-MM-DD)
const hoverDay = ref<string>('')

// Дропдаун месяца
const monthDropdownOpen = ref(false)
const monthDropdownStyle = ref({ top: '0px', left: '0px' })

const monthNames = [
  'Январь','Февраль','Март','Апрель','Май','Июнь',
  'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'
]
const weekdayNames = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс']

// Синхронизация props → internal при изменении снаружи
watch(() => props.modelValue, (val) => {
  if (props.mode === 'single') {
    internalSingle.value = (typeof val === 'string' ? val : '') ?? ''
  } else {
    const rv = val as DateRange | undefined
    internalRange.value = { from: rv?.from ?? '', to: rv?.to ?? '' }
  }
}, { immediate: true })
```

### Task 3: Вычисляемые свойства — сетка календаря

**Files:**

- Modify: `maribulka-vue/src/components/ui/DatePicker.vue`

- [ ] **Step 1: Добавить computed для отображаемого текста инпута**

```ts
/** Текст в инпуте */
const displayValue = computed(() => {
  if (props.mode === 'single') {
    return internalSingle.value ? toDisplay(internalSingle.value) : ''
  }
  const { from, to } = internalRange.value
  if (from && to) return `${toDisplay(from)} — ${toDisplay(to)}`
  if (from) return `${toDisplay(from)} — ...`
  return ''
})

/** Подпись в range-режиме */
const rangeLabel = computed(() => {
  if (props.mode !== 'range') return ''
  return rangeStep.value === 'from' ? 'Начало периода' : 'Конец периода'
})
```

- [ ] **Step 2: Добавить computed для сетки дней**

```ts
interface DayCell {
  iso: string      // YYYY-MM-DD
  day: number      // число месяца
  isOther: boolean // день соседнего месяца
  isToday: boolean
  isSelected: boolean
  isDisabled: boolean
  isInRange: boolean
  isRangeHover: boolean
}

const calendarDays = computed((): DayCell[] => {
  const today = todayISO()
  const year = viewYear.value
  const month = viewMonth.value

  // Первый и последний день месяца
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)

  // День недели первого дня (0=Вс → приводим к 0=Пн)
  let startDow = firstDay.getDay() // 0=вс..6=сб
  startDow = startDow === 0 ? 6 : startDow - 1 // 0=пн..6=вс

  const cells: DayCell[] = []

  // Дни предыдущего месяца
  for (let i = startDow - 1; i >= 0; i--) {
    const d = new Date(year, month, -i)
    const iso = toISO(d)
    cells.push(makeCell(iso, d.getDate(), true, today))
  }

  // Дни текущего месяца
  for (let d = 1; d <= lastDay.getDate(); d++) {
    const iso = toISO(new Date(year, month, d))
    cells.push(makeCell(iso, d, false, today))
  }

  // Дни следующего месяца (до 42 ячеек — 6 строк × 7)
  let nextDay = 1
  while (cells.length < 42) {
    const d = new Date(year, month + 1, nextDay++)
    const iso = toISO(d)
    cells.push(makeCell(iso, d.getDate(), true, today))
  }

  return cells
})

function makeCell(iso: string, day: number, isOther: boolean, today: string): DayCell {
  const { from, to } = internalRange.value
  const single = internalSingle.value
  const hover = hoverDay.value

  const isSelected = props.mode === 'single'
    ? iso === single
    : (iso === from || iso === to)

  // Диапазон между from и to
  const isInRange = props.mode === 'range' && !!from && !!to
    && isAfterDay(iso, from) && isBeforeDay(iso, to)

  // Hover-preview: from выбран, to ещё нет, курсор между from и hover
  const isRangeHover = props.mode === 'range'
    && !!from && !to && !!hover
    && isAfterDay(iso, from) && isBeforeDay(iso, hover)

  // Только дни текущего месяца могут быть disabled через minDate/maxDate
  // Дни соседних месяцев управляются через isOther (pointer-events: none в CSS)
  const isDisabled = !isOther && (
    (!!props.minDate && isBeforeDay(iso, props.minDate)) ||
    (!!props.maxDate && isAfterDay(iso, props.maxDate))
  )

  return {
    iso,
    day,
    isOther,
    isToday: iso === today,
    isSelected,
    isDisabled,
    isInRange,
    isRangeHover,
  }
}
```

### Task 4: Логика открытия/закрытия и позиционирования попапа

**Files:**

- Modify: `maribulka-vue/src/components/ui/DatePicker.vue`

- [ ] **Step 1: Добавить функции открытия/закрытия**

```ts
async function openPopup() {
  if (!wrapRef.value) return
  isOpen.value = true
  await nextTick()
  positionPopup()
}

function closePopup() {
  isOpen.value = false
  monthDropdownOpen.value = false
  hoverDay.value = ''
  if (props.mode === 'range' && internalRange.value.from && !internalRange.value.to) {
    // Если закрыли не завершив range — сбрасываем
    internalRange.value = { from: '', to: '' }
    rangeStep.value = 'from'
    emit('update:modelValue', { from: '', to: '' })
  }
}

function togglePopup() {
  isOpen.value ? closePopup() : openPopup()
}

function positionPopup() {
  if (!wrapRef.value || !popupRef.value) return
  const input = wrapRef.value.querySelector('.dp-input') as HTMLElement
  if (!input) return

  popupRef.value.style.visibility = 'hidden'
  popupRef.value.style.transform = 'none'

  requestAnimationFrame(() => {
    if (!wrapRef.value || !popupRef.value) return
    const inputRect = input.getBoundingClientRect()
    const popupW = popupRef.value.offsetWidth || 280
    const popupH = popupRef.value.offsetHeight || 300
    const vw = window.innerWidth
    const vh = window.innerHeight

    // Масштабирование под ширину инпута
    let scale = 1
    if (popupW > inputRect.width) {
      scale = inputRect.width / popupW
    }

    // Вертикаль: под инпутом или над
    let top = inputRect.bottom + 4
    if (top + popupH * scale > vh) {
      top = inputRect.top - popupH * scale - 4
    }

    // Горизонталь: прижать к правому краю если вылезает
    let left = inputRect.left
    if (left + popupW * scale > vw - 8) {
      left = vw - popupW * scale - 8
    }

    popupRef.value.style.top = `${top}px`
    popupRef.value.style.left = `${left}px`
    popupRef.value.style.transformOrigin = 'top left'
    popupRef.value.style.transform = scale < 1 ? `scale(${scale})` : 'none'
    popupRef.value.style.visibility = 'visible'
  })
}
```

- [ ] **Step 2: Добавить click-outside listener**

```ts
function onDocClick(e: MouseEvent) {
  if (!wrapRef.value) return
  if (!wrapRef.value.contains(e.target as Node) &&
      !popupRef.value?.contains(e.target as Node)) {
    closePopup()
  }
}

function onDocKeydown(e: KeyboardEvent) {
  if (!isOpen.value) return
  if (e.key === 'Escape') {
    closePopup()
  }
}

onMounted(() => {
  document.addEventListener('click', onDocClick, true)
  document.addEventListener('keydown', onDocKeydown)
})

onUnmounted(() => {
  document.removeEventListener('click', onDocClick, true)
  document.removeEventListener('keydown', onDocKeydown)
})
```

### Task 5: Логика выбора дней и навигации

**Files:**

- Modify: `maribulka-vue/src/components/ui/DatePicker.vue`

- [ ] **Step 1: Добавить выбор дня**

```ts
function selectDay(cell: DayCell) {
  if (cell.isDisabled || cell.isOther) return

  if (props.mode === 'single') {
    internalSingle.value = cell.iso
    // Если viewMonth не совпадает — навигируем
    const d = parseDate(cell.iso)
    viewYear.value = d.getFullYear()
    viewMonth.value = d.getMonth()
    emit('update:modelValue', cell.iso)
    closePopup()
    return
  }

  // range mode
  if (rangeStep.value === 'from') {
    internalRange.value = { from: cell.iso, to: '' }
    rangeStep.value = 'to'
  } else {
    let { from } = internalRange.value
    let to = cell.iso
    // Если to < from — меняем местами
    if (isBeforeDay(to, from!)) {
      ;[from, to] = [to, from!]
    }
    internalRange.value = { from: from!, to }
    rangeStep.value = 'from'
    hoverDay.value = ''
    emit('update:modelValue', { from: from!, to })
    closePopup()
  }
}

function onDayHover(cell: DayCell) {
  if (props.mode === 'range' && rangeStep.value === 'to' && !cell.isOther) {
    hoverDay.value = cell.iso
  }
}

function onDayLeave() {
  hoverDay.value = ''
}
```

- [ ] **Step 2: Добавить навигацию по месяцам**

```ts
function prevMonth() {
  if (viewMonth.value === 0) {
    viewMonth.value = 11
    viewYear.value--
  } else {
    viewMonth.value--
  }
}

function nextMonth() {
  if (viewMonth.value === 11) {
    viewMonth.value = 0
    viewYear.value++
  } else {
    viewMonth.value++
  }
}

function selectMonth(monthIndex: number) {
  viewMonth.value = monthIndex
  monthDropdownOpen.value = false
}

function openMonthDropdown(e: Event) {
  e.stopPropagation()
  const trigger = e.currentTarget as HTMLElement
  const rect = trigger.getBoundingClientRect()
  monthDropdownStyle.value = {
    top: `${rect.bottom + 4}px`,
    left: `${rect.left}px`,
  }
  monthDropdownOpen.value = !monthDropdownOpen.value
}
```

- [ ] **Step 3: Добавить кнопки Сегодня/Сброс**

```ts
function onTodayClick() {
  if (props.mode === 'single') {
    const today = todayISO()
    // Проверяем minDate/maxDate
    if (props.minDate && isBeforeDay(today, props.minDate)) return
    if (props.maxDate && isAfterDay(today, props.maxDate)) return
    internalSingle.value = today
    const d = new Date()
    viewYear.value = d.getFullYear()
    viewMonth.value = d.getMonth()
    emit('update:modelValue', today)
    closePopup()
  }
}

function onResetClick() {
  internalRange.value = { from: '', to: '' }
  rangeStep.value = 'from'
  hoverDay.value = ''
  emit('update:modelValue', { from: '', to: '' })
}
```

### Task 6: Шаблон

**Files:**

- Modify: `maribulka-vue/src/components/ui/DatePicker.vue`

- [ ] **Step 1: Написать template**

```vue
<template>
  <div ref="wrapRef" class="dp-wrap">
    <!-- Инпут -->
    <div
      class="dp-input"
      :class="{ 'dp-input--open': isOpen }"
      tabindex="0"
      role="combobox"
      :aria-expanded="isOpen"
      @click="togglePopup"
      @keydown.enter.space.prevent="togglePopup"
      @keydown.escape="closePopup"
    >
      <span v-if="displayValue" class="dp-input-value">{{ displayValue }}</span>
      <span v-else class="dp-input-placeholder dp-input-value">
        {{ placeholder ?? (mode === 'range' ? 'Выберите период' : 'Выберите дату') }}
      </span>
      <svg-icon type="mdi" :path="mdiChevronDown" class="dp-input-arrow" />
    </div>

    <!-- Кнопка Сегодня / Сброс -->
    <button
      v-if="showToday"
      class="btnGlass iconText dp-btn-today"
      @click="mode === 'single' ? onTodayClick() : onResetClick()"
    >
      <span class="inner-glow"></span>
      <span class="top-shine"></span>
      <svg-icon
        type="mdi"
        :path="mode === 'single' ? mdiCalendarToday : mdiClose"
        class="btn-icon"
      />
      <span>{{ mode === 'single' ? 'Сегодня' : 'Сброс' }}</span>
    </button>

    <!-- Попап -->
    <Teleport to="body">
      <!-- Стили позиционирования устанавливаются напрямую через popupRef.value.style.* в positionPopup() -->
      <div
        v-if="isOpen"
        ref="popupRef"
        class="dp-popup"
        @click.stop
      >
        <!-- Подпись range -->
        <div v-if="mode === 'range'" class="dp-range-label">{{ rangeLabel }}</div>

        <!-- Шапка навигации -->
        <div class="dp-header">
          <button class="dp-nav-btn" @click="prevMonth">
            <svg-icon type="mdi" :path="mdiChevronLeft" />
          </button>

          <div class="dp-header-center">
            <div
              class="dp-month-trigger"
              tabindex="0"
              @click="openMonthDropdown"
            >{{ monthNames[viewMonth] }}</div>
            <span class="dp-year">{{ viewYear }}</span>
          </div>

          <button class="dp-nav-btn" @click="nextMonth">
            <svg-icon type="mdi" :path="mdiChevronRight" />
          </button>
        </div>

        <!-- Дни недели -->
        <div class="dp-weekdays">
          <div v-for="wd in weekdayNames" :key="wd" class="dp-weekday">{{ wd }}</div>
        </div>

        <!-- Сетка -->
        <div class="dp-grid">
          <div
            v-for="cell in calendarDays"
            :key="cell.iso"
            class="dp-day"
            :class="{
              'dp-day--other': cell.isOther,
              'dp-day--today': cell.isToday,
              'dp-day--selected': cell.isSelected,
              'dp-day--range': cell.isInRange,
              'dp-day--range-hover': cell.isRangeHover,
              'dp-day--disabled': cell.isDisabled,
            }"
            @click="selectDay(cell)"
            @mouseenter="onDayHover(cell)"
            @mouseleave="onDayLeave"
          >{{ cell.day }}</div>
        </div>
      </div>

      <!-- Дропдаун месяца -->
      <div
        v-if="monthDropdownOpen"
        class="dp-dropdown"
        :style="monthDropdownStyle"
        @click.stop
      >
        <div
          v-for="(name, idx) in monthNames"
          :key="idx"
          class="dp-dropdown-option"
          :class="{ 'dp-dropdown-option--selected': idx === viewMonth }"
          @click="selectMonth(idx)"
        >{{ name }}</div>
      </div>
    </Teleport>
  </div>
</template>
```

- [ ] **Step 2: Добавить keyboard handler на `.dp-month-trigger`**

Найти в шаблоне:

```html
<div
  class="dp-month-trigger"
  tabindex="0"
  @click="openMonthDropdown"
>{{ monthNames[viewMonth] }}</div>
```

Заменить на:

```html
<div
  class="dp-month-trigger"
  tabindex="0"
  @click="openMonthDropdown"
  @keydown.enter.space.prevent="openMonthDropdown"
>{{ monthNames[viewMonth] }}</div>
```

> Примечание по клавиатурной навигации по дням (стрелки ← → ↑ ↓) — **не реализуется в данной версии**. Базовая доступность обеспечена: инпут фокусируется, Enter/Space/Escape работают. Полная стрелочная навигация — задача следующей итерации.

---

## Chunk 3: Подключение, миграция, удаление flatpickr

### Task 7: Подключить CSS в main.ts

**Files:**

- Modify: `maribulka-vue/src/main.ts`

- [ ] **Step 1: Заменить импорт flatpickr.css на datePicker.css**

Найти строку:

```ts
import './assets/flatpickr.css'
```

Заменить на:

```ts
import './components/ui/datePicker.css'
```

- [ ] **Step 2: Проверить что файл сохранён**

### Task 8: Мигрировать BookingFormModal.vue

**Files:**

- Modify: `maribulka-vue/src/components/calendar/BookingFormModal.vue`

- [ ] **Step 1: Удалить flatpickr-импорты и добавить DatePicker**

Найти и удалить три строки:

```ts
import flatpickr from 'flatpickr'
import { Russian } from 'flatpickr/dist/l10n/ru.js'
import 'flatpickr/dist/flatpickr.min.css'
```

Добавить импорт:

```ts
import DatePicker from '../ui/DatePicker.vue'
```

- [ ] **Step 2: Удалить flatpickr-состояние и логику**

Удалить следующие блоки из `<script setup>`:

```ts
// Flatpickr
const dateInputRef = ref<HTMLInputElement | null>(null)
let fpInstance: any = null
```

Удалить весь watch на `props.isVisible` связанный с flatpickr (строки с `fpInstance = flatpickr(...)` и `fpInstance.destroy()`).

Удалить состояние кастомного дропдауна месяца:

```ts
const monthDropdownOpen = ref(false)
const monthDropdownStyle = ref(...)
const monthDropdownCurrentMonth = ref(0)
const monthDropdownCurrentYear = ref(new Date().getFullYear())
let monthTriggerEl: HTMLElement | null = null
const monthNames = [...]
function openMonthDropdown() {...}
function selectMonth(monthIndex: number) {...}
function onMonthDropdownDocClick(_e: MouseEvent) {...}
```

- [ ] **Step 3: Заменить инпут в шаблоне на DatePicker**

Найти:

```html
<div class="date-input-wrap">
  <input ref="dateInputRef" type="text" class="modal-input" placeholder="Выберите дату" />
</div>
```

Заменить на:

```html
<DatePicker
  v-model="shootingDate"
  :minDate="mode === 'add' ? todayStr : undefined"
  :showToday="false"
/>
```

- [ ] **Step 4: Удалить из шаблона кастомный дропдаун месяца**

Найти и удалить блок (в конце Teleport):

```html
<!-- Кастомный дропдаун месяцев для flatpickr -->
<template v-if="monthDropdownOpen">
  <div class="combo-box-dropdown fp-month-dropdown" :style="monthDropdownStyle">
    ...
  </div>
</template>
```

- [ ] **Step 5: Удалить `fpInstance.setDate(...)` из watch на `props.booking`**

Найти в `watch(() => props.booking, ...)`:

```ts
// Если flatpickr уже инициализирован — установить дату через Date-объект
if (fpInstance && shootingDate.value) {
  const parts = shootingDate.value.split('-').map(Number)
  fpInstance.setDate(new Date(parts[0]!, (parts[1]! - 1), parts[2]!), false)
}
```

Удалить эти строки. `shootingDate` по-прежнему устанавливается как `YYYY-MM-DD` — `DatePicker` примет это через `v-model` автоматически.

- [ ] **Step 6: Удалить `fpInstance.setDate(...)` из watch на `props.isVisible`**

Найти в `watch(() => props.isVisible, ...)` блок после инициализации flatpickr:

```ts
// Если режим edit и дата уже выбрана — установить в flatpickr
if (props.mode === 'edit' && shootingDate.value) {
  const parts = shootingDate.value.split('-').map(Number)
  fpInstance.setDate(new Date(parts[0]!, (parts[1]! - 1), parts[2]!), false)
}
```

Удалить. После удаления всего flatpickr-watch этот блок исчезнет вместе с ним.

- [ ] **Step 7: Проверить что `document.addEventListener` не содержит `onMonthDropdownDocClick`**

```bash
grep -n "onMonthDropdownDocClick" maribulka-vue/src/components/calendar/BookingFormModal.vue
```

Ожидаемый результат: пустой вывод.

### Task 9: Мигрировать BookingsTable.vue

**Files:**

- Modify: `maribulka-vue/src/components/calendar/BookingsTable.vue`

- [ ] **Step 1: Удалить flatpickr-импорты**

Найти и удалить:

```ts
import flatpickr from 'flatpickr'
import { Russian } from 'flatpickr/dist/l10n/ru.js'
import 'flatpickr/dist/flatpickr.min.css'
```

Добавить импорт:

```ts
import DatePicker, { type DateRange } from '../ui/DatePicker.vue'
```

- [ ] **Step 2: Проверить что `formatForApi` не используется нигде кроме flatpickr-блока**

```bash
grep -n "formatForApi" maribulka-vue/src/components/calendar/BookingsTable.vue
```

Ожидаемый результат: строки 39 (определение), 70 и 74 (внутри flatpickr-блока и watch) — больше нигде.

- [ ] **Step 3: Заменить состояние периода**

Удалить блок (строки 27–75 текущего файла):

```ts
function getDefaultStart(): Date {
  const d = new Date()
  return new Date(d.getFullYear(), d.getMonth(), 1)
}
function getDefaultEnd(): Date {
  const d = new Date()
  return new Date(d.getFullYear(), d.getMonth() + 1, 0)
}

const periodStart = ref<Date>(getDefaultStart())
const periodEnd = ref<Date>(getDefaultEnd())

function formatForApi(date: Date): string {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

const startInputRef = ref<HTMLInputElement | null>(null)
const endInputRef = ref<HTMLInputElement | null>(null)

onMounted(() => {
  if (startInputRef.value) {
    flatpickr(startInputRef.value, {
      locale: Russian,
      dateFormat: 'd.m.Y',
      defaultDate: periodStart.value,
      onChange: (dates) => {
        if (dates[0]) periodStart.value = dates[0]
      }
    })
  }
  if (endInputRef.value) {
    flatpickr(endInputRef.value, {
      locale: Russian,
      dateFormat: 'd.m.Y',
      defaultDate: periodEnd.value,
      onChange: (dates) => {
        if (dates[0]) periodEnd.value = dates[0]
      }
    })
  }
  bookingsStore.fetchBookings(formatForApi(periodStart.value), formatForApi(periodEnd.value))
})

watch([periodStart, periodEnd], ([start, end]) => {
  bookingsStore.fetchBookings(formatForApi(start), formatForApi(end))
})
```

Заменить на:

```ts
function getDefaultRange(): DateRange {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const lastDay = new Date(y, d.getMonth() + 1, 0).getDate()
  return {
    from: `${y}-${m}-01`,
    to: `${y}-${m}-${String(lastDay).padStart(2, '0')}`
  }
}

const dateRange = ref<DateRange>(getDefaultRange())

onMounted(() => {
  bookingsStore.fetchBookings(dateRange.value.from, dateRange.value.to)
})

watch(dateRange, (range) => {
  bookingsStore.fetchBookings(range.from, range.to)
}, { deep: true })
```

- [ ] **Step 4: Заменить два инпута в шаблоне на DatePicker**

Найти в шаблоне (строки ~236–241):

```html
<div class="bookings-table-filter">
  <span class="bookings-table-filter-label">С:</span>
  <input ref="startInputRef" class="bookings-table-filter-input" placeholder="дд.мм.гггг" readonly />
  <span class="bookings-table-filter-label">По:</span>
  <input ref="endInputRef" class="bookings-table-filter-input" placeholder="дд.мм.гггг" readonly />
```

Заменить весь блок `.bookings-table-filter` на:

```html
<div class="bookings-table-filter">
  <DatePicker mode="range" v-model="dateRange" />
</div>
```

### Task 10: Финальная очистка flatpickr

**Files:**

- Delete: `maribulka-vue/src/assets/flatpickr.css`
- Modify: `maribulka-vue/package.json` (через npm uninstall)

- [ ] **Step 1: Удалить файл flatpickr.css**

```bash
rm maribulka-vue/src/assets/flatpickr.css
```

- [ ] **Step 2: Проверить нет ли других импортов flatpickr в проекте**

```bash
grep -r "flatpickr" maribulka-vue/src --include="*.ts" --include="*.vue" -l
```

Ожидаемый результат: пустой вывод (ноль файлов).

- [ ] **Step 3: Удалить пакет**

```bash
cd maribulka-vue && npm uninstall flatpickr
```

- [ ] **Step 4: Проверить что сборка проходит без ошибок**

```bash
cd maribulka-vue && npm run build
```

Ожидаемый результат: сборка завершена без ошибок TypeScript и Vite.

---

## Chunk 4: Ручное тестирование

### Task 11: Проверить DatePicker в браузере

**Files:** нет

- [ ] **Step 1: Запустить dev-сервер**

```bash
cd maribulka-vue && npm run dev
```

- [ ] **Step 2: Проверить single-режим (BookingFormModal)**

1. Открыть форму добавления записи (LaunchPad → Календарь → кнопка "Добавить")
2. Кликнуть поле "Дата съёмки" → попап открывается
3. Попап масштабируется под ширину инпута
4. Дни недели отображаются по-русски (Пн-Вс)
5. Клик на дату — дата отображается в инпуте в формате `ДД.ММ.ГГ`, попап закрывается
6. Прошедшие даты (в режиме add) недоступны (выглядят приглушённо, не кликабельны)
7. Нажать `Escape` — попап закрывается
8. Клик вне компонента — попап закрывается
9. Кнопки `←` `→` — переключают месяцы
10. Клик на название месяца — открывается дропдаун со стеклянными стилями, выбор работает

- [ ] **Step 3: Проверить range-режим (BookingsTable)**

1. Открыть таблицу записей (LaunchPad → "Записи")
2. DatePicker показывает текущий месяц как диапазон
3. Кликнуть — попап открывается с подписью "Начало периода"
4. Выбрать первую дату → подпись меняется на "Конец периода"
5. Навести курсор на другие даты — hover-preview подсвечивает диапазон
6. Выбрать вторую дату → инпут показывает `ДД.ММ.ГГ — ДД.ММ.ГГ`, попап закрывается
7. Таблица обновляется с новым диапазоном
8. Кнопка "Сброс" → диапазон сбрасывается

- [ ] **Step 4: Проверить z-index**

1. Открыть форму записи → открыть DatePicker → попап виден поверх формы (z-index 1000 > modal-overlay-main 999)
2. Если появляется AlertModal — он отображается поверх DatePicker (z-index 9999 > 1000) ✅

- [ ] **Step 5: Проверить edit-режим (BookingFormModal)**

1. Открыть существующую запись в режиме редактирования
2. Поле даты отображает текущую дату записи в формате `ДД.ММ.ГГ`
3. Все даты доступны для выбора (в edit-режиме `minDate` не передаётся)
4. Изменить дату → значение обновляется корректно

- [ ] **Step 6: Проверить мобильный вид**

1. Открыть DevTools → переключить на мобильное устройство (touch)
2. DatePicker открывается и закрывается корректно
3. Попап не выходит за края экрана
