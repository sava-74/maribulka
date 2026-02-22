<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import ruLocale from '@fullcalendar/core/locales/ru'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilPlus } from '@mdi/light-js'
import { mdiFileTableBoxOutline } from '@mdi/js'
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
import '../../assets/buttons.css'
import '../../assets/layout.css'
import '../../assets/modal.css'
import '../../assets/calendar.css'

const props = defineProps<{ showTable: boolean }>()
const emit = defineEmits(['toggle-table'])

const bookingsStore = useBookingsStore()
const referencesStore = useReferencesStore()

// Ref для доступа к API календаря
const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null)

// Отслеживание ширины экрана для мини-календаря
const windowWidth = ref(window.innerWidth)

// Модальные окна
const showAddModal = ref(false)
const showEditModal = ref(false)
const showPaymentModal = ref(false)
const showDeleteModal = ref(false)
const showDeliverModal = ref(false)
const showViewModal = ref(false)
const showCancelModal = ref(false)
const showConfirmSessionModal = ref(false) // НОВЫЙ: Подтвердить съёмку (new → in_progress)
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

// Обработчик resize для обновления ширины
function handleResize() {
  windowWidth.value = window.innerWidth
}

onMounted(async () => {
  const currentMonth = new Date().toISOString().slice(0, 7)
  bookingsStore.setCurrentMonth(currentMonth)
  await referencesStore.fetchShootingTypes()

  // Добавляем слушатель resize
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  // Удаляем слушатель при размонтировании
  window.removeEventListener('resize', handleResize)
})

// Преобразование записей в формат событий FullCalendar
const calendarEvents = computed(() => {
  const events: any[] = []
  const datePriorityMap = new Map<string, boolean>() // карта приоритета красного цвета по дате

  // Сначала определяем, есть ли в каждой дате отмененные/не состоявшиеся записи
  for (const booking of bookingsStore.bookings) {
    if (booking.status === 'cancelled_client' ||
        booking.status === 'cancelled_photographer' ||
        booking.status === 'cancelled' ||
        booking.status === 'failed') {
      const d = new Date(booking.shooting_date)
      const date = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
      datePriorityMap.set(date, true)
    }
  }

  for (const booking of bookingsStore.bookings) {
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
    let backgroundColor = '#4682B4' // new - темно-голубой (steel blue)
    let textColor = '#FFF'
    
    if (booking.status === 'completed') {
      backgroundColor = '#FFA500' // оранжевый
      textColor = '#000'
    }
    if (booking.status === 'delivered') {
      backgroundColor = '#2E8B57' // темно-зелёный (sea green)
      textColor = '#FFF'
    }
    if (booking.status === 'cancelled_client' ||
        booking.status === 'cancelled_photographer' ||
        booking.status === 'cancelled' ||
        booking.status === 'failed') {
      backgroundColor = '#DC2626' // красный для отмененных/не состоявшихся
      textColor = '#FFF'
    }

    // Проверяем приоритет красного цвета для даты
    const d = shootingDateTime
    const eventDate = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

    if (windowWidth.value <= 768 && !isDayView.value) {
      // МОБИЛЬНАЯ ВЕРСИЯ (только режим месяца): серый или красный алерт
      const hasAlert = eventDate && datePriorityMap.has(eventDate)
      backgroundColor = hasAlert ? 'var(--text-colorAlert)' : 'var(--statusCancelledColor)'
      textColor = 'transparent'
    }
    // В режиме дня (мобилка и десктоп) каждая запись окрашена по своему статусу - НЕ применяем приоритет красного

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
  const phoneLink = phone ? `<a href="tel:${phone}" class="event-phone-link event-phone-${booking.status}" onclick="event.stopPropagation()">${phone}</a>` : ''
  const total = booking.total_amount ? `${Number(booking.total_amount).toLocaleString()} ₽` : ''
  const payText = getPaymentStatusText(booking.payment_status)
  const typeName = booking.shooting_type_name || ''
  const promo = booking.promotion_name ? `, ${booking.promotion_name} -${booking.discount_percent || booking.promo_discount_percent || 0}%` : ''
  const notes = booking.notes || ''

  // Финансы: показываем оплачено/долг в зависимости от статуса
  const totalAmount = Number(booking.total_amount) || 0
  const paidAmount = Number(booking.paid_amount) || 0
  const remaining = totalAmount - paidAmount
  let financeDetails = ''

  if (booking.payment_status === 'unpaid') {
    // Не оплачено - показываем только долг
    financeDetails = `, долг ${Math.round(remaining)} ₽`
  } else if (booking.payment_status === 'partially_paid') {
    // Частично - показываем оплачено и долг
    financeDetails = `, оплачено ${Math.round(paidAmount)} ₽, долг ${Math.round(remaining)} ₽`
  }
  // Если fully_paid - ничего не добавляем

  let html = `<div class="event-day-detail">`
  html += `<div class="event-day-row"><b>${booking.client_name || ''}</b>, ${phoneLink}</div>`
  html += `<div class="event-day-row">${typeName}, ${total}, ${payText}${financeDetails}${promo}</div>`
  if (notes) html += `<div class="event-day-row event-day-notes">${notes}</div>`
  html += `</div>`

  return { html }
}

// Опции календаря
const calendarOptions = computed(() => {
  // Определяем ширину экрана для адаптации (используем reactive переменную)
  const isMiniCalendar = windowWidth.value <= 768

  const options: any = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: ruLocale,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridDay'
    },
    buttonText: {
      today: windowWidth.value <= 768 ? 'С' : 'Сегодня',
      month: windowWidth.value <= 768 ? 'М' : 'Месяц',
      day: windowWidth.value <= 768 ? 'Д' : 'День'
    },
    slotMinTime: '08:00:00',
    slotMaxTime: '23:00:00',
    slotDuration: '00:30:00',
    allDaySlot: false,
    dayMaxEvents: isMiniCalendar ? false : 2,
    moreLinkText: 'ещё',
    events: calendarEvents.value,
    eventContent: renderEventContent,
    dateClick: handleDateClick,
    eventClick: handleEventClick,
    datesSet: handleDatesSet,
    // Мобильная адаптация
    handleWindowResize: true,
    windowResizeDelay: 100
  }

  // Высота календаря: зависит от режима и ширины экрана
  if (isMiniCalendar) {
    options.height = 'auto'  // CSS управляет max-height и overflow
  } else {
    options.height = 'auto'  // Auto для больших экранов
  }

  return options
})

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

// НОВЫЙ БИЗНЕС-ПРОЦЕСС: Подтвердить съёмку (new → in_progress)
function handleConfirmSession() {
  showActionsModal.value = false
  showConfirmSessionModal.value = true
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
  showConfirmSessionModal.value = false
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
      <!-- Кнопка таблицы -->
      <button
        class="glass-button calendar-table-button"
        :class="{ active: showTable }"
        @click="emit('toggle-table')"
        title="Показать/скрыть таблицу"
      >
        <svg-icon type="mdi" :path="mdiFileTableBoxOutline"></svg-icon>
      </button>
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
      @confirmSession="handleConfirmSession"
      @cancel="handleCancel"
      @deliver="handleDeliver"
    />

    <!-- Модальные окна -->
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
