# Calendar Panel Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Создать модуль Календарь — стеклянная рабочая панель с FullCalendar, запускаемая из LaunchPad, с гибридным режимом (месяц/неделя/год = сайдбар, день = полный таймлайн).

**Architecture:** Новая папка `src/components/calendar/` по образцу `home/`. Главный компонент `CalendarPanel.vue` содержит FullCalendar + `CalendarSidebar.vue` (скрывается в режиме дня). Все booking-модалки переезжают из `accounting/` в `calendar/`, при этом `BookingActionsModal.vue` переписывается под новую систему кнопок `.btnGlass`. Стили — отдельный `calendar.css`.

**Tech Stack:** Vue 3 + TypeScript, FullCalendar 6.x (`@fullcalendar/vue3`, `daygrid`, `timegrid`, `multimonth`, `interaction`), Pinia (`bookings` store), `@mdi/js`, `@jamescoyle/vue-icon`

---

## Chunk 1: Инфраструктура + базовый календарь

### Task 1: Расширить PageType и подготовить App.vue

**Files:**
- Modify: `maribulka-vue/src/stores/navigation.ts`
- Modify: `maribulka-vue/src/App.vue`

- [ ] **Step 1: Добавить `'calendar'` в тип PageType**

В `maribulka-vue/src/stores/navigation.ts` строка 4 — заменить:
```typescript
export type PageType = 'home' | 'portfolio' | 'accounting' | 'settings' | 'references' | 'sandbox'
```
На:
```typescript
export type PageType = 'home' | 'portfolio' | 'accounting' | 'settings' | 'references' | 'sandbox' | 'calendar'
```

- [ ] **Step 2: Добавить импорт CalendarPanel в App.vue**

В `maribulka-vue/src/App.vue` — добавить импорт после строки `import Home from './components/home/Home.vue'`:
```typescript
import CalendarPanel from './components/calendar/CalendarPanel.vue'
```

- [ ] **Step 3: Добавить CalendarPanel в template App.vue**

В `maribulka-vue/src/App.vue`, внутри `<div class="worck-table">` — добавить строку **после** `<Home v-if="navStore.currentPage === 'home'" />`, до закрывающего `</div>`:
```html
<CalendarPanel v-if="navStore.currentPage === 'calendar'" />
```

Результат секции `worck-table`:
```html
<div class="worck-table">
  <LaunchPad ref="launchpadRef" :isVisible="showLaunchpad" :origin="launchpadOrigin" @close="showLaunchpad = false" />
  <Home v-if="navStore.currentPage === 'home'" />
  <CalendarPanel v-if="navStore.currentPage === 'calendar'" />
</div>
```

---

### Task 2: Создать calendar.css и зарегистрировать в main.ts

**Files:**
- Create: `maribulka-vue/src/assets/calendar.css`
- Modify: `maribulka-vue/src/main.ts`

- [ ] **Step 1: Создать `maribulka-vue/src/assets/calendar.css`**

```css
/* =============================================
   Calendar Panel Styles
   Использует только переменные из style.css
   ============================================= */

/* --- Основная панель --- */
.calendar-panel {
  display: flex;
  height: 100%;
  gap: 0;
}

/* FullCalendar занимает всё доступное пространство */
.calendar-panel .fc {
  flex: 1;
  min-width: 0;
}

/* --- Сайдбар --- */
.calendar-sidebar {
  width: 220px;
  flex-shrink: 0;
  border-left: 1px solid var(--glass-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.calendar-sidebar__header {
  padding: 12px 16px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
  border-bottom: 1px solid var(--glass-border);
  flex-shrink: 0;
}

.calendar-sidebar__list {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.calendar-sidebar__item {
  padding: 8px 10px;
  border-radius: 8px;
  background: var(--glass-bg-hover);
  border: 1px solid var(--glass-border);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 2px;
  transition: background 0.15s;
}

.calendar-sidebar__item:hover {
  background: var(--glass-bg-active);
}

.calendar-sidebar__item--cancelled {
  opacity: 0.5;
}

.calendar-sidebar__time {
  font-size: 11px;
  font-weight: 700;
  color: var(--accent-primary);
}

.calendar-sidebar__name {
  font-size: 12px;
  color: var(--text-primary);
}

.calendar-sidebar__type {
  font-size: 11px;
  color: var(--text-secondary);
}

.calendar-sidebar__debt {
  font-size: 11px;
  color: var(--color-warning, #f90);
}

.calendar-sidebar__paid {
  font-size: 11px;
  color: var(--color-success, #6c6);
}

.calendar-sidebar__empty {
  text-align: center;
  color: var(--text-secondary);
  font-size: 12px;
  padding: 20px 0;
}

/* --- FullCalendar переопределения --- */
.fc {
  --fc-border-color: var(--glass-border);
  --fc-today-bg-color: rgba(100, 220, 100, 0.08);
  --fc-page-bg-color: transparent;
  --fc-neutral-bg-color: transparent;
  --fc-list-event-hover-bg-color: var(--glass-bg-hover);
  --fc-event-border-color: transparent;
  --fc-highlight-color: rgba(100, 220, 100, 0.12);
}

.fc .fc-toolbar-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
}

.fc .fc-col-header-cell-cushion,
.fc .fc-daygrid-day-number,
.fc .fc-timegrid-slot-label-cushion {
  color: var(--text-secondary);
  text-decoration: none;
}

.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
  color: var(--accent-primary);
  font-weight: 700;
}

/* Кнопки FullCalendar — под наш glass-стиль */
.fc .fc-button {
  background: var(--glass-bg);
  border: 1px solid var(--glass-border);
  color: var(--text-primary);
  border-radius: 7px;
  font-size: 12px;
  padding: 4px 10px;
  transition: background 0.15s, border-color 0.15s;
  box-shadow: none;
  text-transform: none;
  font-weight: 500;
}

.fc .fc-button:hover {
  background: var(--glass-bg-hover);
  border-color: var(--accent-primary);
  color: var(--accent-primary);
  box-shadow: none;
}

.fc .fc-button:focus {
  box-shadow: none;
  outline: none;
}

.fc .fc-button-active,
.fc .fc-button:not(:disabled):active,
.fc .fc-button-primary:not(:disabled).fc-button-active {
  background: var(--glass-bg-active);
  border-color: var(--accent-primary);
  color: var(--accent-primary);
  box-shadow: none;
}

.fc .fc-button-group .fc-button {
  border-radius: 0;
}

.fc .fc-button-group .fc-button:first-child {
  border-radius: 7px 0 0 7px;
}

.fc .fc-button-group .fc-button:last-child {
  border-radius: 0 7px 7px 0;
}

/* Событие в календаре */
.fc .fc-event {
  border-radius: 5px;
  border: none;
  font-size: 11px;
  padding: 2px 5px;
  cursor: pointer;
}

.fc .fc-event:hover {
  filter: brightness(1.15);
}

/* Убираем белый фон у ячеек */
.fc .fc-daygrid-day,
.fc .fc-timegrid-col {
  background: transparent !important;
}

/* Линии тайм-грида */
.fc .fc-timegrid-slot {
  border-color: var(--glass-border);
}
```

- [ ] **Step 2: Добавить импорт в `maribulka-vue/src/main.ts`**

После строки `import './assets/home.css'` добавить:
```typescript
import './assets/calendar.css'
```

---

### Task 3: Создать CalendarSidebar.vue

**Files:**
- Create: `maribulka-vue/src/components/calendar/CalendarSidebar.vue`

- [ ] **Step 1: Создать компонент**

```vue
<script setup lang="ts">
import { computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiPlus } from '@mdi/js'

interface Booking {
  id: number
  client_name: string
  shooting_date: string
  shooting_type_name: string
  total_price: number
  paid_amount: number
  payment_status: string
  status: string
}

const props = defineProps<{
  date: string
  bookings: Booking[]
}>()

const emit = defineEmits<{
  add: []
  select: [booking: Booking]
}>()

const formattedDate = computed(() => {
  if (!props.date) return ''
  const d = new Date(props.date + 'T00:00:00')
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })
})

function getTime(booking: Booking): string {
  const d = new Date(booking.shooting_date)
  const h = d.getHours().toString().padStart(2, '0')
  const m = d.getMinutes().toString().padStart(2, '0')
  return `${h}:${m}`
}

function isCancelled(booking: Booking): boolean {
  return ['cancelled', 'cancelled_client', 'cancelled_photographer', 'failed'].includes(booking.status)
}

function getDebt(booking: Booking): number {
  return Math.max(0, booking.total_price - booking.paid_amount)
}
</script>

<template>
  <div class="calendar-sidebar">
    <div class="calendar-sidebar__header">
      {{ formattedDate || 'Выберите день' }}
    </div>

    <div class="calendar-sidebar__list">
      <template v-if="bookings.length">
        <div
          v-for="b in bookings"
          :key="b.id"
          class="calendar-sidebar__item"
          :class="{ 'calendar-sidebar__item--cancelled': isCancelled(b) }"
          @click="$emit('select', b)"
        >
          <span class="calendar-sidebar__time">{{ getTime(b) }}</span>
          <span class="calendar-sidebar__name">{{ b.client_name }}</span>
          <span class="calendar-sidebar__type">{{ b.shooting_type_name }}</span>
          <span v-if="b.payment_status === 'unpaid'" class="calendar-sidebar__debt">
            долг {{ getDebt(b).toLocaleString('ru-RU') }} ₽
          </span>
          <span v-else-if="b.payment_status === 'partially_paid'" class="calendar-sidebar__debt">
            оплачено {{ b.paid_amount.toLocaleString('ru-RU') }} ₽
          </span>
          <span v-else-if="b.payment_status === 'fully_paid'" class="calendar-sidebar__paid">
            оплачено
          </span>
        </div>
      </template>
      <div v-else class="calendar-sidebar__empty">Нет записей</div>
    </div>

    <div class="ButtonFooter PosCenter" style="padding: 10px; border-top: 1px solid var(--glass-border);">
      <button class="btnGlass iconText" @click="$emit('add')">
        <span class="inner-glow"></span>
        <span class="top-shine"></span>
        <svg-icon type="mdi" :path="mdiPlus" class="btn-icon" />
        <span>Добавить</span>
      </button>
    </div>
  </div>
</template>
```

---

### Task 4: Перенести booking-модалки из accounting/ в calendar/

**Files:**
- Copy + удалить оригиналы: 10 файлов из `accounting/` → `calendar/`
- Modify: `maribulka-vue/src/components/accounting/BookingsCalendar.vue` (обновить импорты)

- [ ] **Step 1: Создать папку calendar/ и скопировать все booking-модалки**

```bash
cd maribulka-vue/src/components && mkdir -p calendar && cp accounting/{AddBookingModal,EditBookingModal,ViewBookingModal,DeleteConfirmModal,CancelBookingModal,BookingActionsModal,AddPaymentModal,ConfirmSessionModal,DeliverBookingModal,RefundModal}.vue calendar/
```

- [ ] **Step 2: Проверить пути импортов — они не изменились**

`accounting/` и `calendar/` находятся на одном уровне (`src/components/`), поэтому пути вида `../../stores/bookings`, `../ConfirmModal.vue`, `../AlertModal.vue` — **идентичны** и менять их не нужно.

**Исключение — `BookingActionsModal.vue`:** содержит старые импорты CSS из `accounting/`:
```typescript
import '../../assets/modal.css'
import '../../assets/buttons.css'  // ← УДАЛИТЬ, buttons.css — старый файл
```
В скопированном `calendar/BookingActionsModal.vue` удалить обе строки импорта CSS. Стили приходят глобально через `main.ts`.

- [ ] **Step 3: Обновить импорты в `accounting/BookingsCalendar.vue`**

В `maribulka-vue/src/components/accounting/BookingsCalendar.vue` найти все импорты перенесённых модалок и изменить путь с `'./'` на `'../calendar/'`. Например:
```typescript
// Было:
import AddBookingModal from './AddBookingModal.vue'
import EditBookingModal from './EditBookingModal.vue'
// ... и остальные 8

// Стало:
import AddBookingModal from '../calendar/AddBookingModal.vue'
import EditBookingModal from '../calendar/EditBookingModal.vue'
// ... и остальные 8
```

- [ ] **Step 4: Проверить сборку**

```bash
cd maribulka-vue && npm run build 2>&1 | tail -20
```
Ожидание: успешная сборка без ошибок. Если есть ошибки — исправить перед продолжением.

- [ ] **Step 5: Удалить оригиналы из accounting/ только после успешной сборки**

```bash
cd maribulka-vue/src/components/accounting && rm AddBookingModal.vue EditBookingModal.vue ViewBookingModal.vue DeleteConfirmModal.vue CancelBookingModal.vue BookingActionsModal.vue AddPaymentModal.vue ConfirmSessionModal.vue DeliverBookingModal.vue RefundModal.vue
```

- [ ] **Step 6: Финальная проверка сборки**

```bash
cd maribulka-vue && npm run build 2>&1 | tail -20
```
Ожидание: успешная сборка без ошибок. При любой ошибке — не продолжать.

---

### Task 5: Переписать BookingActionsModal под новую систему кнопок

**Files:**
- Modify: `maribulka-vue/src/components/calendar/BookingActionsModal.vue`

Текущий `BookingActionsModal.vue` использует старую систему кнопок `.buttonGL`, имеет `isVisible` и `position` пропсы, и собственный `<Teleport>`. При переносе в `CalendarPanel` он будет управляться через `v-if` снаружи, поэтому нужно убрать `isVisible`/`position` и обновить кнопки на `.btnGlass`.

- [ ] **Step 1: Перезаписать `calendar/BookingActionsModal.vue`**

```vue
<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import {
  mdiFolderPlayOutline, mdiCameraOutline, mdiCameraOffOutline,
  mdiFileEditOutline, mdiCashMultiple, mdiEyeOutline, mdiTrashCanOutline,
  mdiCashRefund
} from '@mdi/js'
import { computed } from 'vue'

const props = defineProps<{
  booking: any
}>()

const emit = defineEmits(['close', 'view', 'edit', 'payment', 'delete', 'confirmSession', 'cancel', 'deliver', 'refund'])

const canRefund = computed(() => {
  if (!props.booking || isLocked.value) return false
  const paid = parseFloat(props.booking.paid_amount) || 0
  return paid > 0 && ['cancelled', 'cancelled_client', 'cancelled_photographer'].includes(props.booking.status)
})

const isLocked = computed(() => props.booking?.is_locked == 1)

const canEdit = computed(() =>
  props.booking?.status === 'new' && !isLocked.value
)
const canPayment = computed(() => {
  if (!props.booking || isLocked.value) return false
  if (props.booking.payment_status === 'fully_paid') return false
  return props.booking.status === 'new' || props.booking.status === 'in_progress'
})
const canConfirmSession = computed(() =>
  props.booking?.status === 'new' && !isLocked.value
)
const canDeliver = computed(() =>
  props.booking?.status === 'in_progress' && !isLocked.value
)
const canCancel = computed(() =>
  props.booking?.status === 'new' && !isLocked.value
)
const canDelete = computed(() => {
  if (!props.booking || props.booking.status !== 'new' || isLocked.value) return false
  return (parseFloat(props.booking.paid_amount) || 0) === 0
})
</script>

<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="padGlass modal-sm">
      <div class="modal-glassTitle">{{ booking?.client_name }} · {{ booking?.shooting_type_name }}</div>

      <div class="ButtonFooter PosCenter" style="flex-wrap: wrap; gap: 8px;">
        <button class="btnGlass iconText" @click="$emit('view')">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiEyeOutline" class="btn-icon" />
          <span>Просмотр</span>
        </button>

        <button class="btnGlass iconText" :disabled="!canEdit" @click="$emit('edit')">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiFileEditOutline" class="btn-icon" />
          <span>Редактировать</span>
        </button>

        <button class="btnGlass iconText" :disabled="!canPayment" @click="$emit('payment')">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiCashMultiple" class="btn-icon" />
          <span>Оплата</span>
        </button>

        <button class="btnGlass iconText" :disabled="!canConfirmSession" @click="$emit('confirmSession')">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiCameraOutline" class="btn-icon" />
          <span>Подтвердить</span>
        </button>

        <button class="btnGlass iconText" :disabled="!canDeliver" @click="$emit('deliver')">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiFolderPlayOutline" class="btn-icon" />
          <span>Выдать</span>
        </button>

        <button class="btnGlass iconText" :disabled="!canCancel" @click="$emit('cancel')">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiCameraOffOutline" class="btn-icon" />
          <span>Отменить</span>
        </button>

        <button class="btnGlass iconText" :disabled="!canDelete" @click="$emit('delete')">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiTrashCanOutline" class="btn-icon" />
          <span>Удалить</span>
        </button>

        <button class="btnGlass iconText" :disabled="!canRefund" @click="$emit('refund')">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiCashRefund" class="btn-icon" />
          <span>Возврат</span>
        </button>

        <button class="btnGlass iconText" @click="$emit('close')">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <span>Закрыть</span>
        </button>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Проверить сборку**

```bash
cd maribulka-vue && npm run build 2>&1 | tail -10
```
Ожидание: успешная сборка без ошибок.

---

### Task 6: Создать CalendarPanel.vue

**Files:**
- Create: `maribulka-vue/src/components/calendar/CalendarPanel.vue`

- [ ] **Step 1: Создать компонент**

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import multiMonthPlugin from '@fullcalendar/multimonth'
import interactionPlugin from '@fullcalendar/interaction'
import type { CalendarOptions, EventInput, DatesSetArg, DateClickArg, EventClickArg } from '@fullcalendar/core'
import { useBookingsStore } from '../../stores/bookings'
import CalendarSidebar from './CalendarSidebar.vue'
import BookingActionsModal from './BookingActionsModal.vue'
import AddBookingModal from './AddBookingModal.vue'
import EditBookingModal from './EditBookingModal.vue'
import ViewBookingModal from './ViewBookingModal.vue'
import DeleteConfirmModal from './DeleteConfirmModal.vue'
import CancelBookingModal from './CancelBookingModal.vue'
import AddPaymentModal from './AddPaymentModal.vue'
import ConfirmSessionModal from './ConfirmSessionModal.vue'
import DeliverBookingModal from './DeliverBookingModal.vue'
import RefundModal from './RefundModal.vue'

const bookingsStore = useBookingsStore()

// --- Состояние ---
const isDayView = ref(false)
const selectedDate = ref<string>('')
const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null)
const selectedBooking = ref<any>(null)

// Флаги модалок
const showActionsModal = ref(false)
const showAddModal = ref(false)
const showEditModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)
const showCancelModal = ref(false)
const showPaymentModal = ref(false)
const showConfirmSessionModal = ref(false)
const showDeliverModal = ref(false)
const showRefundModal = ref(false)

// --- Данные для сайдбара ---
const bookingsForSelectedDate = computed(() => {
  if (!selectedDate.value) return []
  return bookingsStore.bookings.filter(b => {
    const d = new Date(b.shooting_date)
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}` === selectedDate.value
  })
})

// --- Цвета по статусу ---
const STATUS_COLORS: Record<string, string> = {
  new: '#4682B4',
  in_progress: '#FFA500',
  completed: '#FFA500',
  delivered: '#2E8B57',
  cancelled: '#DC2626',
  cancelled_client: '#DC2626',
  cancelled_photographer: '#DC2626',
  failed: '#DC2626',
}

// --- События для FullCalendar ---
const calendarEvents = computed<EventInput[]>(() =>
  bookingsStore.bookings.map(b => {
    const color = STATUS_COLORS[b.status] ?? '#4682B4'
    const start = new Date(b.shooting_date)
    const durationMin = (b.duration_minutes ?? 60) + 30
    const end = new Date(start.getTime() + durationMin * 60 * 1000)
    return {
      id: String(b.id),
      title: b.client_name,
      start,
      end,
      backgroundColor: color,
      borderColor: color,
      textColor: b.status === 'completed' ? '#000' : '#fff',
      extendedProps: { booking: b },
    }
  })
)

// --- Вспомогательная: обновить данные за текущий видимый диапазон ---
function refreshCalendar() {
  const api = calendarRef.value?.getApi()
  if (!api) return
  const view = api.view
  const start = view.activeStart.toISOString().slice(0, 10)
  const end = view.activeEnd.toISOString().slice(0, 10)
  bookingsStore.fetchBookings(start, end)
}

// --- Callbacks FullCalendar ---
function handleDatesSet(info: DatesSetArg) {
  isDayView.value = info.view.type === 'timeGridDay'
  if (isDayView.value) {
    const d = info.view.currentStart
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    selectedDate.value = `${y}-${m}-${day}`
  }
  bookingsStore.fetchBookings(info.startStr.slice(0, 10), info.endStr.slice(0, 10))
}

function handleDateClick(info: DateClickArg) {
  selectedDate.value = info.dateStr.slice(0, 10)
}

function handleEventClick(info: EventClickArg) {
  selectedBooking.value = info.event.extendedProps.booking
  showActionsModal.value = true
}

// --- Обработчик действий из BookingActionsModal ---
function handleAction(action: string) {
  showActionsModal.value = false
  switch (action) {
    case 'edit':           showEditModal.value = true; break
    case 'view':           showViewModal.value = true; break
    case 'delete':         showDeleteModal.value = true; break
    case 'cancel':         showCancelModal.value = true; break
    case 'payment':        showPaymentModal.value = true; break
    case 'confirmSession': showConfirmSessionModal.value = true; break
    case 'deliver':        showDeliverModal.value = true; break
    case 'refund':         showRefundModal.value = true; break
  }
}

// --- Кнопка "+" (добавить запись) ---
function openAddModal() {
  showAddModal.value = true
}

// --- FullCalendar опции ---
const baseToolbarRight = 'multiMonthYear,dayGridMonth,timeGridWeek,timeGridDay'

const calendarOptions = computed<CalendarOptions>(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, multiMonthPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: 'ru',
  firstDay: 1,
  headerToolbar: {
    left: 'prev,next title',
    right: isDayView.value
      ? `addBooking ${baseToolbarRight}`
      : baseToolbarRight,
  },
  views: {
    multiMonthYear: { type: 'multiMonth', duration: { years: 1 } },
  },
  customButtons: {
    addBooking: {
      text: '+',
      hint: 'Добавить запись',
      click: openAddModal,
    },
  },
  slotMinTime: '09:00:00',
  slotMaxTime: '23:00:00',
  allDaySlot: false,
  height: 'auto',
  events: calendarEvents.value,
  dateClick: handleDateClick,
  eventClick: handleEventClick,
  datesSet: handleDatesSet,
}))

onMounted(() => {
  const now = new Date()
  const y = now.getFullYear()
  const m = String(now.getMonth() + 1).padStart(2, '0')
  const d = String(now.getDate()).padStart(2, '0')
  selectedDate.value = `${y}-${m}-${d}`
})
</script>

<template>
  <div class="padGlass padGlass-work calendar-panel">
    <FullCalendar ref="calendarRef" :options="calendarOptions" />

    <CalendarSidebar
      v-if="!isDayView"
      :date="selectedDate"
      :bookings="bookingsForSelectedDate"
      @add="openAddModal"
      @select="booking => { selectedBooking = booking; showActionsModal = true }"
    />
  </div>

  <Teleport to="body">
    <BookingActionsModal
      v-if="showActionsModal"
      :booking="selectedBooking"
      @close="showActionsModal = false"
      @view="handleAction('view')"
      @edit="handleAction('edit')"
      @payment="handleAction('payment')"
      @confirmSession="handleAction('confirmSession')"
      @deliver="handleAction('deliver')"
      @cancel="handleAction('cancel')"
      @delete="handleAction('delete')"
    />
    <AddBookingModal
      v-if="showAddModal"
      :initial-date="selectedDate"
      @close="showAddModal = false"
      @saved="showAddModal = false; refreshCalendar()"
    />
    <EditBookingModal
      v-if="showEditModal"
      :booking="selectedBooking"
      @close="showEditModal = false"
      @saved="showEditModal = false; refreshCalendar()"
    />
    <ViewBookingModal
      v-if="showViewModal"
      :booking="selectedBooking"
      @close="showViewModal = false"
    />
    <DeleteConfirmModal
      v-if="showDeleteModal"
      :booking="selectedBooking"
      @close="showDeleteModal = false"
      @deleted="showDeleteModal = false; refreshCalendar()"
    />
    <CancelBookingModal
      v-if="showCancelModal"
      :booking="selectedBooking"
      @close="showCancelModal = false"
      @cancelled="showCancelModal = false; refreshCalendar()"
    />
    <AddPaymentModal
      v-if="showPaymentModal"
      :booking="selectedBooking"
      @close="showPaymentModal = false"
      @saved="showPaymentModal = false; refreshCalendar()"
    />
    <ConfirmSessionModal
      v-if="showConfirmSessionModal"
      :booking="selectedBooking"
      @close="showConfirmSessionModal = false"
      @confirmed="showConfirmSessionModal = false; refreshCalendar()"
    />
    <DeliverBookingModal
      v-if="showDeliverModal"
      :booking="selectedBooking"
      @close="showDeliverModal = false"
      @delivered="showDeliverModal = false; refreshCalendar()"
    />
    <RefundModal
      v-if="showRefundModal"
      :booking="selectedBooking"
      @close="showRefundModal = false"
      @saved="showRefundModal = false; refreshCalendar()"
    />
  </Teleport>
</template>
```

- [ ] **Step 2: Проверить сборку**

```bash
cd maribulka-vue && npm run build 2>&1 | tail -20
```
Ожидание: успешная сборка. При любой ошибке — исправить перед продолжением.

---

### Task 7: Подключить LaunchPad → навигация в календарь

**Files:**
- Modify: `maribulka-vue/src/components/launchpad/LaunchPad.vue`

- [ ] **Step 1: Добавить импорт navigationStore**

В `<script setup>` добавить после `import { useAuthStore } from '../../stores/auth'`:
```typescript
import { useNavigationStore } from '../../stores/navigation'
```

- [ ] **Step 2: Добавить navStore и функцию openCalendar**

После `const auth = useAuthStore()` добавить:
```typescript
const navStore = useNavigationStore()

function openCalendar() {
  navStore.navigateTo('calendar')
  close()
}
```

- [ ] **Step 3: Привязать к кнопке Календарь**

Найти кнопку "Календарь" (та что с `mdiCalendarMonth`). Заменить `@click="onRipple($event)"` на:
```html
<button class="btnGlass bigIcon" @click="onRipple($event); openCalendar()">
```

> **Важно:** `onRipple` вызывается **первым**, чтобы ripple-анимация успела стартовать до того как компонент размонтируется при закрытии LaunchPad.

---

## Chunk 2: Удаление старых файлов и финальная проверка

### Task 8: Убрать BookingsFullCalendar из Accounting.vue и удалить файл

**Files:**
- Modify: `maribulka-vue/src/components/accounting/Accounting.vue`
- Delete: `maribulka-vue/src/components/accounting/BookingsFullCalendar.vue`

> **Примечание по spec:** Spec (раздел 12 "Что НЕ меняем") говорит "Accounting.vue — пока без изменений". Однако Accounting.vue импортирует `BookingsFullCalendar`, который мы удаляем. Поэтому убираем только этот импорт и его использование — всё остальное в Accounting.vue не трогаем. `BookingsCalendar.vue` (таблица записей) остаётся в `accounting/` и продолжает работать как раньше.

- [ ] **Step 1: Убрать BookingsFullCalendar из Accounting.vue**

В `maribulka-vue/src/components/accounting/Accounting.vue`:

1. Удалить строку импорта:
```typescript
import BookingsFullCalendar from './BookingsFullCalendar.vue'
```

2. Удалить переменную (если есть) и использование компонента в template. Найти блок:
```html
<template v-if="activeTab === 'bookings'">
  <BookingsFullCalendar
    :showTable="showTable"
    :period-start="periodStart"
    :period-end="periodEnd"
    @toggle-table="showTable = !showTable"
  />
  <div v-if="showTable" style="margin-top: 20px;">
    <BookingsCalendar ... />
  </div>
</template>
```

Заменить на (оставляем только таблицу, всегда видимую на вкладке "Записи"):
```html
<template v-if="activeTab === 'bookings'">
  <BookingsCalendar
    :period-start="periodStart"
    :period-end="periodEnd"
  />
</template>
```

Также удалить переменную `showTable` из script (она больше не нужна) и её использование.

- [ ] **Step 2: Проверить сборку**

```bash
cd maribulka-vue && npm run build 2>&1 | tail -10
```
Ожидание: успешная сборка. При ошибке — исправить.

- [ ] **Step 3: Удалить файл**

```bash
rm maribulka-vue/src/components/accounting/BookingsFullCalendar.vue
```

- [ ] **Step 4: Финальная проверка сборки**

```bash
cd maribulka-vue && npm run build 2>&1 | tail -10
```
Ожидание: `✓ built in X.XXs` без ошибок.

---

### Task 9: Финальный тест в браузере

- [ ] **Step 1: Запустить dev-сервер**

```bash
cd maribulka-vue && npm run dev
```

- [ ] **Step 2: Тест — открытие календаря**
  - Войти как admin (login: `admin`, pass: `123`)
  - Нажать ☰ → LaunchPad открылся
  - Нажать "Календарь"
  - Ожидание: LaunchPad **закрылся**, появилась панель с FullCalendar и сайдбаром справа

- [ ] **Step 3: Тест — переключение видов**
  - Нажать "месяц" → сайдбар виден справа
  - Нажать "неделя" → сайдбар виден справа
  - Нажать "год" → сайдбар виден справа
  - Нажать "день" → сайдбар **скрыт**, таймлайн на весь экран, кнопка "+" в тулбаре

- [ ] **Step 4: Тест — сайдбар**
  - В режиме месяца кликнуть по дню с записями
  - Ожидание: сайдбар обновился, показывает записи этого дня с временем и статусом оплаты

- [ ] **Step 5: Тест — модалки**
  - В режиме дня кликнуть на событие
  - Ожидание: открывается `BookingActionsModal` (стеклянный стиль, кнопки `.btnGlass`)
  - Нажать "Редактировать" → открывается `EditBookingModal`
  - Закрыть → вернуться к календарю

- [ ] **Step 6: Тест — кнопка "+" в сайдбаре**
  - В режиме месяца кликнуть по любому дню, нажать "Добавить" в сайдбаре
  - Ожидание: открывается `AddBookingModal`

- [ ] **Step 7: Финальная сборка**

```bash
cd maribulka-vue && npm run build 2>&1 | tail -5
```
Ожидание: `✓ built in X.XXs`
