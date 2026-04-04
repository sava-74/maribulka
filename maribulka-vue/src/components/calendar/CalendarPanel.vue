<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import multiMonthPlugin from '@fullcalendar/multimonth'
import interactionPlugin from '@fullcalendar/interaction'
import ruLocale from '@fullcalendar/core/locales/ru'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiPlus } from '@mdi/js'
import { useReferencesStore } from '../../stores/references'
import BookingFormModal from './BookingFormModal.vue'
import AddPaymentModal from './AddPaymentModal.vue'
import DeleteConfirmModal from './DeleteConfirmModal.vue'
import DeliverBookingModal from './DeliverBookingModal.vue'
import ViewBookingModal from './ViewBookingModal.vue'
import CancelBookingModal from './CancelBookingModal.vue'
import ConfirmSessionModal from './ConfirmSessionModal.vue'
import BookingActionsModal from './BookingActionsModal.vue'

const referencesStore = useReferencesStore()

const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null)

// Локальное состояние записей
const bookings = ref<any[]>([])

// View state
const selectedDate = ref<string>('')

// Modal state
const showAddModal = ref(false)
const showEditModal = ref(false)
const showPaymentModal = ref(false)
const showDeleteModal = ref(false)
const showDeliverModal = ref(false)
const showViewModal = ref(false)
const showCancelModal = ref(false)
const showConfirmSessionModal = ref(false)
const showActionsModal = ref(false)
const selectedBooking = ref<any>(null)

// Status colors
const STATUS_COLORS: Record<string, string> = {
  new: '#4682B4',
  in_progress: '#FFA500',
  completed: '#2E8B57',
  delivered: '#2E8B57',
  cancelled: '#DC2626',
  cancelled_client: '#DC2626',
  cancelled_photographer: '#DC2626',
  failed: '#DC2626',
}

const INTERVAL_MINUTES = 30

// Красные статусы (отмена/провал)
const RED_STATUSES = new Set(['cancelled', 'cancelled_client', 'cancelled_photographer', 'failed'])

// Индекс шариков по дате
const ballsByDate = computed<Record<string, 'red' | 'default'>>(() => {
  const result: Record<string, 'red' | 'default'> = {}
  for (const booking of bookings.value) {
    const d = new Date(booking.shooting_date)
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
    if (!result[key] || result[key] === 'default') {
      result[key] = RED_STATUSES.has(booking.status) ? 'red' : 'default'
    }
  }
  return result
})

function padDate(d: Date): string {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function extractDate(val: any): string {
  if (val instanceof Date) return padDate(val)
  if (typeof val === 'string') return val.slice(0, 10)
  return ''
}

function isDateInPast(dateStr: string): boolean {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const target = new Date(dateStr + 'T00:00:00')
  return target < today
}

// Прямая загрузка записей из API — БЕЗ store, БЕЗ кеша
async function loadBookingsDirect(start: string, end: string): Promise<any[]> {
  const url = `/api/bookings.php?start=${start}&end=${end}`
  const response = await fetch(url, { credentials: 'include' })
  if (!response.ok) throw new Error(`HTTP ${response.status}`)
  return await response.json()
}

// Преобразование записей в формат событий FullCalendar
function bookingsToEvents(items: any[]) {
  const types = Array.isArray(referencesStore.shootingTypes) ? referencesStore.shootingTypes : []
  return items.map(booking => {
    const shootingType = types.find((t: any) => t.id === booking.shooting_type_id)
    const durationMinutes = shootingType?.duration_minutes || 30
    const start = new Date(booking.shooting_date)
    const end = new Date(start.getTime() + (durationMinutes + INTERVAL_MINUTES) * 60 * 1000)
    const color = STATUS_COLORS[booking.status] || '#4682B4'
    return {
      id: String(booking.id),
      title: `${booking.client_name} — ${booking.shooting_type_name}`,
      start: start.toISOString(),
      end: end.toISOString(),
      backgroundColor: color,
      borderColor: color,
      textColor: '#fff',
      extendedProps: { booking },
    }
  })
}

// Функция-источник событий — FullCalendar вызывает её когда нужны события
async function fetchEvents(fetchInfo: any, successCallback: (events: any[]) => void, failureCallback: (error: any) => void) {
  try {
    const start = padDate(fetchInfo.start)
    const end = padDate(fetchInfo.end)
    const data = await loadBookingsDirect(start, end)
    bookings.value = data
    await nextTick()
    renderBalls()
    successCallback(bookingsToEvents(data))
  } catch (err) {
    console.error('[Calendar] fetchEvents error:', err)
    failureCallback(err)
  }
}

// Вставка шариков в DOM (только для daygrid — месяц/год)
function renderBalls() {
  document.querySelectorAll('.fc-daygrid-day-frame .status-ball').forEach(el => el.remove())
  for (const [dateKey, ballType] of Object.entries(ballsByDate.value)) {
    const cell = document.querySelector(`[data-date="${dateKey}"] .fc-daygrid-day-frame`)
    if (!cell) continue
    const span = document.createElement('span')
    span.className = 'status-ball' + (ballType === 'red' ? ' status-ball--red' : '')
    cell.appendChild(span)
  }
}

function handleDateClick(info: any) {
  const calendarApi = calendarRef.value?.getApi()
  if (!calendarApi) return

  // Извлекаем чистую дату без времени
  const dateStr = extractDate(info.date)

  // В режиме месяц/год/неделя — клик по дате переключает на режим дня
  if (info.view.type === 'dayGridMonth' || info.view.type === 'multiMonthYear' || info.view.type === 'timeGridWeek') {
    calendarApi.changeView('timeGridDay', dateStr)
    return
  }

  // В режиме день — клик по пустому месту открывает модалку создания
  if (info.view.type === 'timeGridDay') {
    // Запрещаем создание записи на прошедшую дату
    if (isDateInPast(dateStr)) return
    selectedDate.value = dateStr
    showAddModal.value = true
  }
}

function handleEventClick(info: any) {
  const booking = info.event.extendedProps.booking
  if (!booking) return

  const calendarApi = calendarRef.value?.getApi()
  // В режиме месяц/год — клик по событию переключает на день
  if (calendarApi && (info.view.type === 'dayGridMonth' || info.view.type === 'multiMonthYear')) {
    const dateStr = extractDate(info.event.start)
    calendarApi.changeView('timeGridDay', dateStr)
    return
  }

  // В режиме неделя/день — открываем модалку действий
  selectedBooking.value = booking
  showActionsModal.value = true
}

function handleAddBooking() {
  const calendarApi = calendarRef.value?.getApi()
  if (calendarApi) {
    const d = calendarApi.getDate()
    const dateStr = extractDate(d)
    // Запрещаем создание записи на прошедшую дату
    if (isDateInPast(dateStr)) return
    selectedDate.value = dateStr
  }
  showAddModal.value = true
}

// Actions modal handlers
function handleView() { showActionsModal.value = false; showViewModal.value = true }
function handleEdit() { showActionsModal.value = false; showEditModal.value = true }
function handlePayment() { showActionsModal.value = false; showPaymentModal.value = true }
function handleDelete() { showActionsModal.value = false; showDeleteModal.value = true }
function handleConfirmSession() { showActionsModal.value = false; showConfirmSessionModal.value = true }
function handleCancel() { showActionsModal.value = false; showCancelModal.value = true }
function handleDeliver() { showActionsModal.value = false; showDeliverModal.value = true }
function handleRefund() { showActionsModal.value = false; showPaymentModal.value = true }

function closeModal() {
  showAddModal.value = false
  showEditModal.value = false
  showPaymentModal.value = false
  showDeleteModal.value = false
  showDeliverModal.value = false
  showConfirmSessionModal.value = false
  showViewModal.value = false
  showCancelModal.value = false
  showActionsModal.value = false
  selectedBooking.value = null
  // Перезагрузить события после закрытия модалки
  const api = calendarRef.value?.getApi()
  if (api) api.refetchEvents()
}

// Статические options — НЕ computed, НЕ меняется
const calendarOptions = {
  plugins: [dayGridPlugin, timeGridPlugin, multiMonthPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: ruLocale,
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'multiMonthYear,dayGridMonth,timeGridWeek,timeGridDay',
  },
  slotMinTime: '08:00:00',
  slotMaxTime: '23:00:00',
  slotDuration: '00:30:00',
  allDaySlot: false,
  dayMaxEvents: 2,
  moreLinkText: 'ещё',
  height: '100%',
  // events как ФУНКЦИЯ — FullCalendar вызывает при каждом рендере событий
  events: fetchEvents,
  views: {
    dayGridMonth: { eventDisplay: 'none' },
    multiMonthYear: { eventDisplay: 'none' },
    timeGridWeek: { eventDisplay: 'block' },
    timeGridDay: { eventDisplay: 'block' },
  },
  dateClick: handleDateClick,
  eventClick: handleEventClick,
  handleWindowResize: true,
  dayCellDidMount: (arg: any) => {
    const d = arg.date
    const key = padDate(d)
    const ball = ballsByDate.value[key]
    if (!ball) return
    const span = document.createElement('span')
    span.className = 'status-ball' + (ball === 'red' ? ' status-ball--red' : '')
    const inner = arg.el.querySelector('.fc-daygrid-day-frame')
    if (inner) inner.appendChild(span)
  },
}

onMounted(async () => {
  await referencesStore.fetchShootingTypes()
})
</script>

<template>
  <div class="padGlass padGlass-work calendar-panel">

    <FullCalendar ref="calendarRef" :options="calendarOptions" />

    <!-- Add button -->
    <div class="calendar-add-btn-wrap">
      <button class="btnGlass iconText" @click="handleAddBooking">
        <span class="inner-glow"></span>
        <span class="top-shine"></span>
        <SvgIcon type="mdi" :path="mdiPlus" class="btn-icon" />
        <span>Добавить запись</span>
      </button>
    </div>

    <!-- Actions modal -->
    <BookingActionsModal
      v-if="showActionsModal"
      :booking="selectedBooking"
      @close="closeModal"
      @view="handleView"
      @edit="handleEdit"
      @payment="handlePayment"
      @delete="handleDelete"
      @confirmSession="handleConfirmSession"
      @cancel="handleCancel"
      @deliver="handleDeliver"
      @refund="handleRefund"
    />

    <!-- Modals -->
    <BookingFormModal mode="add" :isVisible="showAddModal" :defaultDate="selectedDate" @close="closeModal" />
    <BookingFormModal mode="edit" :isVisible="showEditModal" :booking="selectedBooking" @close="closeModal" />
    <AddPaymentModal :isVisible="showPaymentModal" :booking="selectedBooking" @close="closeModal" />
    <DeleteConfirmModal :isVisible="showDeleteModal" :booking="selectedBooking" @close="closeModal" />
    <ConfirmSessionModal :isVisible="showConfirmSessionModal" :booking="selectedBooking" @close="closeModal" @openPayment="handlePayment" />
    <DeliverBookingModal :isVisible="showDeliverModal" :booking="selectedBooking" @close="closeModal" @openPayment="handlePayment" />
    <ViewBookingModal :isVisible="showViewModal" :booking="selectedBooking" @close="closeModal" />
    <CancelBookingModal :isVisible="showCancelModal" :booking="selectedBooking" @close="closeModal" />
  </div>
</template>
