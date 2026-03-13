<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import multiMonthPlugin from '@fullcalendar/multimonth'
import interactionPlugin from '@fullcalendar/interaction'
import ruLocale from '@fullcalendar/core/locales/ru'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiPlus } from '@mdi/js'
import { useBookingsStore } from '../../stores/bookings'
import { useReferencesStore } from '../../stores/references'
import AddBookingModal from './AddBookingModal.vue'
import EditBookingModal from './EditBookingModal.vue'
import AddPaymentModal from './AddPaymentModal.vue'
import DeleteConfirmModal from './DeleteConfirmModal.vue'
import DeliverBookingModal from './DeliverBookingModal.vue'
import ViewBookingModal from './ViewBookingModal.vue'
import CancelBookingModal from './CancelBookingModal.vue'
import ConfirmSessionModal from './ConfirmSessionModal.vue'
import BookingActionsModal from './BookingActionsModal.vue'

const emit = defineEmits<{
  sidebarUpdate: [payload: { date: string; bookings: any[]; isDayView: boolean }]
}>()

const bookingsStore = useBookingsStore()
const referencesStore = useReferencesStore()

const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null)

// View state
const isDayView = ref(false)
const selectedDate = ref<string>('')
const currentRangeStart = ref<Date | null>(null)
const currentRangeEnd = ref<Date | null>(null)

// Sidebar state
const sidebarDate = ref<string>('')

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

// Bookings filtered for the sidebar selected date
const sidebarBookings = computed(() => {
  if (!sidebarDate.value) return []
  return bookingsStore.bookings.filter(b => {
    const d = new Date(b.shooting_date)
    const dateStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
    return dateStr === sidebarDate.value
  })
})

// Calendar events
const calendarEvents = computed(() => {
  return bookingsStore.bookings.map(booking => {
    const shootingType = referencesStore.shootingTypes.find((t: any) => t.id === booking.shooting_type_id)
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
})

function formatDateForAPI(date: Date): string {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

async function loadBookings(start: Date, end: Date) {
  await bookingsStore.fetchBookings(formatDateForAPI(start), formatDateForAPI(end))
}

function emitSidebar() {
  emit('sidebarUpdate', {
    date: sidebarDate.value,
    bookings: sidebarBookings.value,
    isDayView: isDayView.value,
  })
}

function handleDatesSet(info: any) {
  isDayView.value = info.view.type === 'timeGridDay'
  currentRangeStart.value = info.start
  currentRangeEnd.value = info.end

  if (isDayView.value) {
    sidebarDate.value = formatDateForAPI(info.start)
  }

  loadBookings(info.start, info.end)
}

watch([sidebarDate, sidebarBookings, isDayView], emitSidebar)

function handleDateClick(info: any) {
  const calendarApi = calendarRef.value?.getApi()
  if (!calendarApi) return
  if (info.view.type !== 'timeGridDay') {
    sidebarDate.value = info.dateStr
  }
}

function handleEventClick(info: any) {
  const calendarApi = calendarRef.value?.getApi()
  if (!calendarApi) return

  if (info.view.type !== 'timeGridDay') {
    // In month/week/year view — click sets sidebar date and selects booking
    const d = info.event.start!
    sidebarDate.value = formatDateForAPI(d)
    return
  }

  // In day view — open actions modal
  const booking = info.event.extendedProps.booking
  if (booking) {
    selectedBooking.value = booking
    showActionsModal.value = true
  }
}

function handleSidebarSelect(booking: any) {
  selectedBooking.value = booking
  showActionsModal.value = true
}

function handleSidebarAdd() {
  selectedDate.value = sidebarDate.value
  showAddModal.value = true
}

function handleAddFromDayView() {
  const calendarApi = calendarRef.value?.getApi()
  if (calendarApi) {
    const d = calendarApi.getDate()
    selectedDate.value = formatDateForAPI(d)
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
}

const calendarOptions = computed(() => ({
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
  events: calendarEvents.value,
  dateClick: handleDateClick,
  eventClick: handleEventClick,
  datesSet: handleDatesSet,
  handleWindowResize: true,
}))

onMounted(async () => {
  await referencesStore.fetchShootingTypes()
})

defineExpose({ handleSidebarAdd, handleSidebarSelect })
</script>

<template>
  <div class="padGlass padGlass-work calendar-panel">

    <FullCalendar ref="calendarRef" :options="calendarOptions" />

    <!-- Add button in day view -->
    <div v-if="isDayView" class="calendar-add-btn-wrap">
      <button class="btnGlass iconText" @click="handleAddFromDayView">
        <span class="inner-glow"></span>
        <span class="top-shine"></span>
        <svg-icon type="mdi" :path="mdiPlus" class="btn-icon" />
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
    <AddBookingModal :isVisible="showAddModal" :defaultDate="selectedDate" @close="closeModal" />
    <EditBookingModal :isVisible="showEditModal" :booking="selectedBooking" @close="closeModal" />
    <AddPaymentModal :isVisible="showPaymentModal" :booking="selectedBooking" @close="closeModal" />
    <DeleteConfirmModal :isVisible="showDeleteModal" :booking="selectedBooking" @close="closeModal" />
    <ConfirmSessionModal :isVisible="showConfirmSessionModal" :booking="selectedBooking" @close="closeModal" @openPayment="handlePayment" />
    <DeliverBookingModal :isVisible="showDeliverModal" :booking="selectedBooking" @close="closeModal" @openPayment="handlePayment" />
    <ViewBookingModal :isVisible="showViewModal" :booking="selectedBooking" @close="closeModal" />
    <CancelBookingModal :isVisible="showCancelModal" :booking="selectedBooking" @close="closeModal" />
  </div>
</template>
