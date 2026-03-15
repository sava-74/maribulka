<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {
  mdiChevronDown,
  mdiChevronLeft,
  mdiChevronRight,
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
  showPresets?: boolean   // кнопки 1 мес / 3 мес / 6 мес (только range)
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'single',
  showToday: true,
  showPresets: false,
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
const dropdownRef = ref<HTMLElement | null>(null)
const yearDropdownRef = ref<HTMLElement | null>(null)

// range: шаг выбора ('from' | 'to')
const rangeStep = ref<'from' | 'to'>('from')

// hover-день для preview диапазона (YYYY-MM-DD)
const hoverDay = ref<string>('')

// Дропдаун месяца
const monthDropdownOpen = ref(false)
const monthDropdownStyle = ref({ top: '0px', left: '0px' })

const yearDropdownOpen = ref(false)
const yearDropdownStyle = ref({ top: '0px', left: '0px' })

const yearList = computed(() => {
  const current = new Date().getFullYear()
  const years = []
  for (let y = current - 5; y <= current + 5; y++) years.push(y)
  return years
})

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

// ── Computed ──────────────────────────────────────────────────────

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
  return rangeStep.value === 'from' ? 'Выберите начало периода' : 'Выберите конец периода'
})

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

// ── Открытие/закрытие/позиционирование ────────────────────────────

function openPopup() {
  if (!wrapRef.value) return
  isOpen.value = true
}

// Вызывается Transition перед началом анимации входа — попап уже в DOM, можно мерить размеры
function onPopupBeforeEnter() {
  nextTick(() => positionPopup())
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

  const inputRect = input.getBoundingClientRect()
  const popupW = popupRef.value.offsetWidth || 280
  const popupH = popupRef.value.offsetHeight || 300
  const vw = window.innerWidth
  const vh = window.innerHeight

  // Масштабирование под ширину инпута — временно отключено (TODO: вернуть после настройки размеров)
  // let scale = 1
  // if (popupW > inputRect.width) {
  //   scale = inputRect.width / popupW
  // }

  // Вертикаль: под инпутом или над
  let top = inputRect.bottom + 4
  let openAbove = false
  if (top + popupH > vh) {
    top = inputRect.top - popupH - 4
    openAbove = true
  }

  // Горизонталь: выравниваем попап относительно инпута по той же стороне
  const inputCenter = inputRect.left + inputRect.width / 2
  const third = vw / 3
  let left: number

  if (inputCenter < third) {
    left = inputRect.left
  } else if (inputCenter > third * 2) {
    left = inputRect.right - popupW
  } else {
    left = inputRect.left + inputRect.width / 2 - popupW / 2
  }

  // Не выходим за края viewport
  if (left + popupW > vw - 8) left = vw - popupW - 8
  if (left < 8) left = 8

  // transformOrigin = центр инпута в координатах попапа
  const inputCenterX = inputRect.left + inputRect.width / 2 - left
  const originY = openAbove ? '100%' : '0%'

  popupRef.value.style.top = `${top}px`
  popupRef.value.style.left = `${left}px`
  popupRef.value.style.transformOrigin = `${inputCenterX}px ${originY}`
}

// ── Click-outside ─────────────────────────────────────────────────

function onDocClick(e: MouseEvent) {
  if (!wrapRef.value) return
  if (!wrapRef.value.contains(e.target as Node) &&
      !popupRef.value?.contains(e.target as Node) &&
      !dropdownRef.value?.contains(e.target as Node) &&
      !yearDropdownRef.value?.contains(e.target as Node)) {
    closePopup()
  }
}

function onDocKeydown(e: KeyboardEvent) {
  if (!isOpen.value) return
  if (e.key === 'Escape') {
    closePopup()
  }
}

function onWindowResize() {
  if (isOpen.value) positionPopup()
}

onMounted(() => {
  document.addEventListener('click', onDocClick, true)
  document.addEventListener('keydown', onDocKeydown)
  window.addEventListener('resize', onWindowResize)
})

onUnmounted(() => {
  document.removeEventListener('click', onDocClick, true)
  document.removeEventListener('keydown', onDocKeydown)
  window.removeEventListener('resize', onWindowResize)
})

// ── Выбор дней ────────────────────────────────────────────────────

function selectDay(cell: DayCell) {
  if (cell.isDisabled || cell.isOther) return

  if (props.mode === 'single') {
    internalSingle.value = cell.iso
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

// ── Навигация по месяцам ──────────────────────────────────────────

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
  yearDropdownOpen.value = false
  monthDropdownOpen.value = !monthDropdownOpen.value
}

function openYearDropdown(e: Event) {
  e.stopPropagation()
  const trigger = e.currentTarget as HTMLElement
  const rect = trigger.getBoundingClientRect()
  yearDropdownStyle.value = {
    top: `${rect.bottom + 4}px`,
    left: `${rect.left}px`,
  }
  monthDropdownOpen.value = false
  yearDropdownOpen.value = !yearDropdownOpen.value
}

function selectYear(year: number) {
  viewYear.value = year
  yearDropdownOpen.value = false
}

// ── Кнопки Сегодня/Сброс ──────────────────────────────────────────

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
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const lastDay = new Date(y, d.getMonth() + 1, 0).getDate()
  const defaultRange: DateRange = {
    from: `${y}-${m}-01`,
    to: `${y}-${m}-${String(lastDay).padStart(2, '0')}`
  }
  internalRange.value = defaultRange
  rangeStep.value = 'from'
  hoverDay.value = ''
  viewYear.value = y
  viewMonth.value = d.getMonth()
  emit('update:modelValue', defaultRange)
}

// months = 1 → предыдущий месяц; months = 3/6 → последние N месяцев включая текущий
function setPreset(months: 1 | 3 | 6) {
  const now = new Date()
  let from: Date
  let to: Date

  if (months === 1) {
    // Предыдущий месяц: с 1-го по последний день прошлого месяца
    from = new Date(now.getFullYear(), now.getMonth() - 1, 1)
    to = new Date(now.getFullYear(), now.getMonth(), 0)
  } else {
    // Последние N месяцев включая текущий: с 1-го дня (N-1) месяцев назад по сегодня
    from = new Date(now.getFullYear(), now.getMonth() - (months - 1), 1)
    to = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  }

  const range: DateRange = { from: toISO(from), to: toISO(to) }
  internalRange.value = range
  rangeStep.value = 'from'
  hoverDay.value = ''
  viewYear.value = from.getFullYear()
  viewMonth.value = from.getMonth()
  emit('update:modelValue', range)
}
</script>

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

    <!-- Кнопка Сегодня (single) -->
    <button
      v-if="showToday && mode === 'single'"
      class="btnGlass iconText dp-btn-today"
      @click="onTodayClick"
    >
      <span class="inner-glow"></span>
      <span class="top-shine"></span>
      <svg-icon type="mdi" :path="mdiCalendarToday" class="btn-icon" />
      <span>Сегодня</span>
    </button>

    <!-- Кнопка Сегодня (range) — без иконки -->
    <button
      v-if="showToday && mode === 'range'"
      class="btnGlass iconText dp-btn-today"
      @click="onResetClick"
    >
      <span class="inner-glow"></span>
      <span class="top-shine"></span>
      <span>Сегодня</span>
    </button>

    <!-- Кнопки пресетов (только range) -->
    <template v-if="mode === 'range' && showPresets">
      <button class="btnGlass iconText dp-btn-today" @click="setPreset(1)">
        <span class="inner-glow"></span>
        <span class="top-shine"></span>
        <span>1 мес</span>
      </button>
      <button class="btnGlass iconText dp-btn-today" @click="setPreset(3)">
        <span class="inner-glow"></span>
        <span class="top-shine"></span>
        <span>3 мес</span>
      </button>
      <button class="btnGlass iconText dp-btn-today" @click="setPreset(6)">
        <span class="inner-glow"></span>
        <span class="top-shine"></span>
        <span>6 мес</span>
      </button>
    </template>

    <!-- Попап -->
    <Teleport to="body">
      <Transition name="dp-popup" @before-enter="onPopupBeforeEnter">
      <!-- Стили позиционирования устанавливаются напрямую через popupRef.value.style.* в positionPopup() -->
      <div
        v-if="isOpen"
        ref="popupRef"
        class="dp-popup"
        @click.stop
      >
        <!-- Подпись range -->
        <div v-if="mode === 'range'" :key="rangeStep" class="dp-range-label">{{ rangeLabel }}</div>

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
              @keydown.enter.space.prevent="openMonthDropdown"
            >{{ monthNames[viewMonth] }} ▼</div>
            <div
              class="dp-month-trigger"
              tabindex="0"
              @click="openYearDropdown"
              @keydown.enter.space.prevent="openYearDropdown"
            >{{ viewYear }} ▼</div>
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
      </Transition>

      <!-- Дропдаун месяца -->
      <div
        v-if="monthDropdownOpen"
        ref="dropdownRef"
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

      <!-- Дропдаун года -->
      <div
        v-if="yearDropdownOpen"
        ref="yearDropdownRef"
        class="dp-dropdown"
        :style="yearDropdownStyle"
        @click.stop
      >
        <div
          v-for="year in yearList"
          :key="year"
          class="dp-dropdown-option"
          :class="{ 'dp-dropdown-option--selected': year === viewYear }"
          @click="selectYear(year)"
        >{{ year }}</div>
      </div>
    </Teleport>
  </div>
</template>
