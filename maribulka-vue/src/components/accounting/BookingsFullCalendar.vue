<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import ruLocale from '@fullcalendar/core/locales/ru'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilPlus } from '@mdi/light-js'
import { useBookingsStore } from '../../stores/bookings'
import { useReferencesStore } from '../../stores/references'
import AddBookingModal from './AddBookingModal.vue'
import EditBookingModal from './EditBookingModal.vue'
import AddPaymentModal from './AddPaymentModal.vue'
import DeleteConfirmModal from './DeleteConfirmModal.vue'
import DeliverBookingModal from './DeliverBookingModal.vue'
import ViewBookingModal from './ViewBookingModal.vue'
import CancelBookingModal from './CancelBookingModal.vue'
import ConfirmModal from '../ConfirmModal.vue'
import BookingActionsModal from './BookingActionsModal.vue'
import '../../assets/buttons.css'
import '../../assets/layout.css'
import '../../assets/modal.css'
import '../../assets/calendar.css'

const bookingsStore = useBookingsStore()
const referencesStore = useReferencesStore()

// Ref для доступа к API календаря
const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null)

// Модальные окна
const showAddModal = ref(false)
const showEditModal = ref(false)
const showPaymentModal = ref(false)
const showDeleteModal = ref(false)
const showDeliverModal = ref(false)
const showViewModal = ref(false)
const showCancelModal = ref(false)
const showConfirmCompleted = ref(false)
const showActionsModal = ref(false)
const actionsModalPosition = ref({ x: 0, y: 0 })

// Выбранная запись и дата
const selectedBooking = ref<any>(null)
const selectedDate = ref<string>('')

// Текущий режим просмотра
const isDayView = ref(false)
const currentCalendarDate = ref('')

// Текущий день в календаре — не в прошлом?
const canAddBooking = computed(() => {
  if (!isDayView.value || !currentCalendarDate.value) return false
  const today = new Date()
  const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`
  return currentCalendarDate.value >= todayStr
})

// Интервал между съёмками (30 мин)
const INTERVAL_MINUTES = 30

// Хелпер статуса оплаты
function getPaymentStatusText(status: string) {
  switch (status) {
    case 'unpaid': return 'Не оплачено'
    case 'partially_paid': return 'Частично'
    case 'fully_paid': return 'Оплачено'
    default: return status
  }
}

onMounted(async () => {
  const currentMonth = new Date().toISOString().slice(0, 7)
  bookingsStore.setCurrentMonth(currentMonth)
  await referencesStore.fetchShootingTypes()
})

// Преобразование записей в формат событий FullCalendar
const calendarEvents = computed(() => {
  const events: any[] = []

  for (const booking of bookingsStore.bookings) {
    // Пропускаем отменённые записи
    if (booking.status === 'cancelled_client' ||
        booking.status === 'cancelled_photographer' ||
        booking.status === 'cancelled' ||
        booking.status === 'failed') {
      continue
    }

    // Находим тип съёмки для получения duration_minutes
    const shootingType = referencesStore.shootingTypes.find(
      (t: any) => t.id === booking.shooting_type_id
    )
    const durationMinutes = shootingType?.duration_minutes || 30

    // Парсим дату и время съёмки
    const shootingDateTime = new Date(booking.shooting_date)

    // Вычисляем время окончания (duration + интервал)
    const endDateTime = new Date(shootingDateTime.getTime() + (durationMinutes + INTERVAL_MINUTES) * 60 * 1000)

    // Определяем цвет по статусу
    let backgroundColor = '#FFD700' // new - жёлтый
    let textColor = '#000'
    if (booking.status === 'completed') {
      backgroundColor = '#FFA500' // оранжевый
      textColor = '#000'
    }
    if (booking.status === 'delivered') {
      backgroundColor = '#39FF14' // зелёный
      textColor = '#000'
    }

    events.push({
      id: String(booking.id),
      title: `${booking.client_name} - ${booking.shooting_type_name}`,
      start: shootingDateTime.toISOString(),
      end: endDateTime.toISOString(),
      backgroundColor,
      borderColor: backgroundColor,
      textColor,
      extendedProps: {
        booking: booking
      }
    })
  }

  return events
})

// Кастомный рендер содержимого событий (только для режима дня)
function renderEventContent(arg: any) {
  // В режиме месяца - не меняем стандартный рендер
  if (arg.view.type === 'dayGridMonth') {
    return true
  }

  // В режиме дня - расширенная информация
  const booking = arg.event.extendedProps.booking
  const phone = booking.phone || ''
  const phoneLink = phone ? `<a href="tel:${phone}" class="event-phone-link" onclick="event.stopPropagation()">${phone}</a>` : ''
  const total = booking.total_amount ? `${Number(booking.total_amount).toLocaleString()} ₽` : ''
  const payText = getPaymentStatusText(booking.payment_status)
  const typeName = booking.shooting_type_name || ''
  const promo = booking.promotion_name ? `, ${booking.promotion_name} -${booking.discount_percent || booking.promo_discount_percent || 0}%` : ''
  const notes = booking.notes || ''

  let html = `<div class="event-day-detail">`
  html += `<div class="event-day-row"><b>${booking.client_name || ''}</b>, ${phoneLink}</div>`
  html += `<div class="event-day-row">${typeName}, ${total}, ${payText}${promo}</div>`
  if (notes) html += `<div class="event-day-row event-day-notes">${notes}</div>`
  html += `</div>`

  return { html }
}

// Опции календаря
const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: ruLocale,
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridDay'
  },
  buttonText: {
    today: 'Сегодня',
    month: 'Месяц',
    day: 'День'
  },
  slotMinTime: '08:00:00',
  slotMaxTime: '23:00:00',
  slotDuration: '00:30:00',
  allDaySlot: false,
  height: 'auto',
  dayMaxEvents: 2,
  moreLinkText: 'ещё',
  events: calendarEvents.value,
  eventContent: renderEventContent,
  dateClick: handleDateClick,
  eventClick: handleEventClick,
  datesSet: handleDatesSet,
  // Мобильная адаптация
  handleWindowResize: true,
  windowResizeDelay: 100
}))

// Клик по дате - переход в режим дня
function handleDateClick(info: any) {
  const calendarApi = calendarRef.value?.getApi()
  if (calendarApi) {
    if (info.view.type !== 'timeGridDay') {
      // Из месяца переключаемся в режим дня
      calendarApi.changeView('timeGridDay', info.dateStr)
    }
    // В режиме дня клик по пустому месту — ничего не делаем
  }
}

// Клик по событию
function handleEventClick(info: any) {
  const calendarApi = calendarRef.value?.getApi()

  // В режиме месяца - переходим в день (записи информационные)
  if (calendarApi && info.view.type === 'dayGridMonth') {
    const eventDate = info.event.start
    calendarApi.changeView('timeGridDay', eventDate)
    return
  }

  // В режиме дня - открываем меню действий
  const booking = info.event.extendedProps.booking
  if (booking) {
    selectedBooking.value = booking
    const rect = info.el.getBoundingClientRect()
    actionsModalPosition.value = {
      x: Math.min(rect.left, window.innerWidth - 200),
      y: Math.min(rect.bottom + 5, window.innerHeight - 350)
    }
    showActionsModal.value = true
  }
}

// Смена периода - обновляем данные
function handleDatesSet(info: any) {
  // Обновляем режим просмотра
  isDayView.value = info.view.type === 'timeGridDay'

  // Сохраняем текущую дату календаря (для проверки canAddBooking)
  if (isDayView.value) {
    const d = info.start
    currentCalendarDate.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  } else {
    currentCalendarDate.value = ''
  }

  // Берём середину видимого диапазона, чтобы корректно определить месяц
  const start = info.start.getTime()
  const end = info.end.getTime()
  const middle = new Date(start + (end - start) / 2)
  const newMonth = middle.toISOString().slice(0, 7)
  if (newMonth !== bookingsStore.currentMonth) {
    bookingsStore.setCurrentMonth(newMonth)
  }
}

// Действия из модалки
function handleView() {
  showActionsModal.value = false
  showViewModal.value = true
}

function handleEdit() {
  showActionsModal.value = false
  showEditModal.value = true
}

function handlePayment() {
  showActionsModal.value = false
  showPaymentModal.value = true
}

function handleDelete() {
  showActionsModal.value = false
  showDeleteModal.value = true
}

function handleComplete() {
  showActionsModal.value = false
  showConfirmCompleted.value = true
}

async function confirmMarkCompleted() {
  if (selectedBooking.value) {
    await bookingsStore.markAsCompleted(selectedBooking.value.id)
    showConfirmCompleted.value = false
    selectedBooking.value = null
  }
}

function handleCancel() {
  showActionsModal.value = false
  showCancelModal.value = true
}

function handleDeliver() {
  showActionsModal.value = false
  showDeliverModal.value = true
}

function handleAddBooking() {
  // Берём дату текущего дня из календаря
  const calendarApi = calendarRef.value?.getApi()
  if (calendarApi && calendarApi.view.type === 'timeGridDay') {
    const d = calendarApi.getDate()
    selectedDate.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  }
  showAddModal.value = true
}

function closeModal() {
  showAddModal.value = false
  showEditModal.value = false
  showPaymentModal.value = false
  showDeleteModal.value = false
  showDeliverModal.value = false
  showViewModal.value = false
  showCancelModal.value = false
  showActionsModal.value = false
  selectedBooking.value = null
}

function closeActionsModal() {
  showActionsModal.value = false
}
</script>

<template>
  <div class="bookings-fullcalendar">
    <!-- FullCalendar -->
    <div class="calendar-container">
      <FullCalendar ref="calendarRef" :options="calendarOptions" />
      <!-- Кнопка добавления - только в режиме дня -->
      <button
        v-if="canAddBooking"
        class="glass-button calendar-add-button"
        @click="handleAddBooking"
        title="Добавить запись"
      >
        <svg-icon type="mdi" :path="mdilPlus"></svg-icon>
      </button>
    </div>

    <!-- Модалка действий при клике на запись -->
    <BookingActionsModal
      :isVisible="showActionsModal"
      :booking="selectedBooking"
      :position="actionsModalPosition"
      @close="closeActionsModal"
      @view="handleView"
      @edit="handleEdit"
      @payment="handlePayment"
      @delete="handleDelete"
      @complete="handleComplete"
      @cancel="handleCancel"
      @deliver="handleDeliver"
    />

    <!-- Модальные окна -->
    <AddBookingModal :isVisible="showAddModal" :defaultDate="selectedDate" @close="closeModal" />
    <EditBookingModal :isVisible="showEditModal" :booking="selectedBooking" @close="closeModal" />
    <AddPaymentModal :isVisible="showPaymentModal" :booking="selectedBooking" @close="closeModal" />
    <DeleteConfirmModal :isVisible="showDeleteModal" :booking="selectedBooking" @close="closeModal" />
    <DeliverBookingModal :isVisible="showDeliverModal" :booking="selectedBooking" @close="closeModal" />
    <ViewBookingModal :isVisible="showViewModal" :booking="selectedBooking" @close="closeModal" />
    <CancelBookingModal :isVisible="showCancelModal" :booking="selectedBooking" @close="closeModal" />
    <ConfirmModal
      :isVisible="showConfirmCompleted"
      message="Съёмка состоялась?"
      @confirm="confirmMarkCompleted"
      @cancel="showConfirmCompleted = false"
    />
  </div>
</template>
