<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  getFacetedUniqueValues,
  type ColumnDef,
  type SortingState,
  type RowSelectionState,
  type ColumnFiltersState,
  FlexRender
} from '@tanstack/vue-table'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiFilterRemoveOutline, mdiFilterMenuOutline, mdiTextBoxPlusOutline, mdiFolderPlayOutline, mdiCameraOutline, mdiCameraOffOutline, mdiFileEditOutline, mdiCashMultiple, mdiTrashCanOutline, mdiEyeOutline } from '@mdi/js'
import { useBookingsStore } from '../../stores/bookings'
import AddBookingModal from './AddBookingModal.vue'
import EditBookingModal from './EditBookingModal.vue'
import AddPaymentModal from './AddPaymentModal.vue'
import DeleteConfirmModal from './DeleteConfirmModal.vue'
import DeliverBookingModal from './DeliverBookingModal.vue'
import ViewBookingModal from './ViewBookingModal.vue'
import CancelBookingModal from './CancelBookingModal.vue'
import ConfirmModal from '../ConfirmModal.vue'
import { getLocalDateString } from '../../config/timezone'
import '../../assets/tables.css'
import '../../assets/buttons.css'
import '../../assets/layout.css'
import '../../assets/modal.css'
import '../../assets/responsive.css'

// Props от родителя
const props = defineProps<{
  periodStart: Date
  periodEnd: Date
}>()

const bookingsStore = useBookingsStore()

// Форматирование дат для API (YYYY-MM-DD)
function formatDateForAPI(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Watch на изменение периода
watch([() => props.periodStart, () => props.periodEnd], async () => {
  const start = formatDateForAPI(props.periodStart)
  const end = formatDateForAPI(props.periodEnd)
  await bookingsStore.fetchBookings(start, end)
}, { immediate: true })

// Row selection state
const rowSelection = ref<RowSelectionState>({})
const sorting = ref<SortingState>([{ id: 'order_number', desc: true }])
const columnFilters = ref<ColumnFiltersState>([])

// Видимость панели фильтров (по умолчанию скрыты)
const showFilters = ref(false)

// Модальные окна
const showAddModal = ref(false)
const addModalDefaultDate = ref('') // Дата по умолчанию для модалки нового заказа
const showEditModal = ref(false)
const showPaymentModal = ref(false)
const showDeleteModal = ref(false)
const showDeliverModal = ref(false)
const showViewModal = ref(false)
const showCancelModal = ref(false)
const showConfirmCompleted = ref(false)

onMounted(() => {
  // Сброс фильтров при входе на вкладку
  resetFilters()
})

// Форматирование даты в DD.MM.YYYY (убираем время если есть)
function formatDate(dateString: string) {
  if (!dateString) return ''
  // Убираем время если есть (2026-02-01T12:30:00 -> 2026-02-01)
  const datePart = dateString.split('T')[0]?.split(' ')[0]
  if (!datePart) return ''
  const parts = datePart.split('-')
  const year = parts[0] || ''
  const month = parts[1] || ''
  const day = parts[2] || ''
  const shortYear = year.slice(-2)
  return `${day}.${month}.${shortYear}`
}

// Форматирование даты и времени в DD.MM.YY HH:mm
function formatDateTime(dateString: string) {
  if (!dateString) return ''
  const [datePart, timePart] = dateString.split(' ')
  if (!datePart) return ''
  const parts = datePart.split('-')
  const year = parts[0] || ''
  const month = parts[1] || ''
  const day = parts[2] || ''
  const shortYear = year.slice(-2)
  const time = timePart?.substring(0, 5) || ''
  return time ? `${day}.${month}.${shortYear} ${time}` : `${day}.${month}.${shortYear}`
}

// Helper functions
function getStatusText(status: string) {
  switch (status) {
    case 'new': return '🔵 Новый'
    case 'in_progress': return '🟠 В работе'
    case 'completed': return '🟢 Выполнен'
    case 'completed_partially': return '🟡 Выполнен частично'
    case 'not_completed': return '🟤 Не выполнен'
    case 'cancelled_by_client': return '⚪ Отменён клиентом'
    case 'cancelled_by_photographer': return '⚪ Отменён фотографом'
    case 'client_no_show': return '⚪ Клиент не пришёл'
    default: return status
  }
}

function getPaymentStatusText(status: string) {
  switch (status) {
    case 'unpaid': return '🔴 Не оплачено'
    case 'partially_paid': return '🟡 Частично'
    case 'fully_paid': return '🟢 Оплачено'
    default: return status
  }
}

// Column definitions (БЕЗ checkbox и "Действия")
const columns: ColumnDef<any>[] = [
  {
    accessorKey: 'order_number',
    header: 'ID заказа',
    cell: ({ getValue }) => getValue() || '—'
  },
  {
    accessorKey: 'booking_date',
    header: 'Дата создания',
    cell: ({ getValue }) => formatDate(getValue() as string)
  },
  {
    accessorKey: 'shooting_date',
    header: 'Дата съёмки',
    cell: ({ getValue }) => formatDateTime(getValue() as string)
  },
  {
    accessorKey: 'delivery_date',
    header: 'Дата выдачи',
    cell: ({ getValue }) => formatDate(getValue() as string)
  },
  {
    accessorKey: 'client_name',
    header: 'Клиент',
  },
  {
    accessorKey: 'phone',
    header: 'Телефон',
  },
  {
    accessorKey: 'shooting_type_name',
    header: 'Тип съёмки',
  },
  {
    accessorKey: 'quantity',
    header: 'Кол-во',
  },
  {
    accessorKey: 'base_price',
    header: 'Стоимость',
    cell: ({ getValue }) => {
      const value = getValue()
      return value ? `${Math.round(parseFloat(value as string))}` : '—'
    }
  },
  {
    accessorKey: 'promo_discount_percent',
    header: 'Скидка',
    cell: ({ row }) => {
      const promotionId = row.original.promotion_id
      const discountPercent = parseFloat(row.original.promo_discount_percent) || 0
      return promotionId && discountPercent > 0 ? `-${discountPercent}%` : '—'
    }
  },
  {
    accessorKey: 'total_amount',
    header: 'Сумма ₽',
    cell: ({ getValue }) => `${Math.round(parseFloat(getValue() as string))}`
  },
  {
    accessorKey: 'payment_status',
    header: 'Статус оплаты',
    cell: ({ getValue }) => getPaymentStatusText(getValue() as string)
  },
  {
    accessorKey: 'status',
    header: 'Статус записи',
    cell: ({ getValue }) => getStatusText(getValue() as string)
  }
]

// Create table instance
const table = useVueTable({
  get data() {
    return bookingsStore.bookings
  },
  columns,
  state: {
    get rowSelection() {
      return rowSelection.value
    },
    get sorting() {
      return sorting.value
    },
    get columnFilters() {
      return columnFilters.value
    }
  },
  enableRowSelection: true,
  enableMultiRowSelection: false, // Только одна строка
  onRowSelectionChange: updater => {
    rowSelection.value = typeof updater === 'function' ? updater(rowSelection.value) : updater
  },
  onSortingChange: updater => {
    sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater
  },
  onColumnFiltersChange: updater => {
    columnFilters.value = typeof updater === 'function' ? updater(columnFilters.value) : updater
  },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getFacetedUniqueValues: getFacetedUniqueValues()
})

// Computed: есть ли выбранная строка
const hasSelectedRow = computed(() => {
  return Object.keys(rowSelection.value).length > 0
})

// Computed: выбранная запись
const selectedBooking = computed(() => {
  const selectedIds = Object.keys(rowSelection.value)
  if (selectedIds.length === 0 || !selectedIds[0]) return null
  const index = parseInt(selectedIds[0])
  return bookingsStore.bookings[index]
})

// Computed: проверка что заказ заблокирован (is_locked = 1)
const isLocked = computed(() => {
  return selectedBooking.value?.is_locked == 1
})

// Computed: проверка что можно отметить "Съёмка состоялась" (только для 'new' в день съёмки и позже)
const canMarkCompleted = computed(() => {
  if (!selectedBooking.value) return false
  if (isLocked.value) return false
  const status = selectedBooking.value.status
  if (status !== 'new') return false
  const shootingDate = new Date(selectedBooking.value.shooting_date)
  shootingDate.setHours(0, 0, 0, 0)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return shootingDate.getTime() <= today.getTime()
})

// Computed: проверка что можно выдать (статус 'in_progress')
const canDeliver = computed(() => {
  if (!selectedBooking.value) return false
  if (isLocked.value) return false
  return selectedBooking.value.status === 'in_progress'
})

// Computed: проверка что дата-время съёмки ещё не наступило
const isShootingDateNotPassed = computed(() => {
  if (!selectedBooking.value) return true

  // shooting_date уже содержит время (2026-02-23 09:00:00), shooting_time может быть undefined
  const shootingDateTime = selectedBooking.value.shooting_time
    ? new Date(`${selectedBooking.value.shooting_date} ${selectedBooking.value.shooting_time}`)
    : new Date(selectedBooking.value.shooting_date)

  const now = new Date()
  return now < shootingDateTime // Сейчас < момента съёмки
})

// Computed: проверка что можно удалить (ТОЛЬКО 'new' БЕЗ оплаты И дата не наступила)
const canDelete = computed(() => {
  if (!selectedBooking.value) return false
  if (selectedBooking.value.status !== 'new') return false
  if (isLocked.value) return false
  if (!isShootingDateNotPassed.value) return false // Дата-время наступило
  const paidAmount = parseFloat(selectedBooking.value.paid_amount) || 0
  return paidAmount === 0
})

// Computed: проверка что можно редактировать (ТОЛЬКО 'new' И дата не наступила)
const canEdit = computed(() => {
  if (!selectedBooking.value) return false
  return selectedBooking.value.status === 'new' && !isLocked.value && isShootingDateNotPassed.value
})

// Computed: уникальные значения для фильтров
const uniqueClients = computed(() => {
  const column = table.getColumn('client_name')
  if (!column) return []
  const facetedValues = column.getFacetedUniqueValues()
  return Array.from(facetedValues.keys()).sort()
})

const uniqueShootingTypes = computed(() => {
  const column = table.getColumn('shooting_type_name')
  if (!column) return []
  const facetedValues = column.getFacetedUniqueValues()
  return Array.from(facetedValues.keys()).sort()
})

const paymentStatuses = [
  { value: 'unpaid', label: '🔴 Не оплачено' },
  { value: 'partially_paid', label: '🟡 Частично' },
  { value: 'fully_paid', label: '🟢 Оплачено' }
]

const bookingStatuses = [
  { value: 'new', label: '🔵 Новый' },
  { value: 'in_progress', label: '🟠 В работе' },
  { value: 'completed', label: '🟢 Выполнен' },
  { value: 'completed_partially', label: '🟡 Выполнен частично' },
  { value: 'not_completed', label: '🟤 Не выполнен' },
  { value: 'cancelled_by_client', label: '⚪ Отменён клиентом' },
  { value: 'cancelled_by_photographer', label: '⚪ Отменён фотографом' },
  { value: 'client_no_show', label: '⚪ Клиент не пришёл' }
]

// Filters
const clientFilter = ref('')
const shootingTypeFilter = ref('')
const paymentStatusFilter = ref('')
const statusFilter = ref('')

// Применение фильтров к таблице
function applyFilters() {
  const filters: ColumnFiltersState = []  // Сбрасываем все фильтры и добавляем только те, которые выбраны

  if (clientFilter.value) {
    filters.push({ id: 'client_name', value: clientFilter.value })
  }
  if (shootingTypeFilter.value) {
    filters.push({ id: 'shooting_type_name', value: shootingTypeFilter.value })
  }
  if (paymentStatusFilter.value) {
    filters.push({ id: 'payment_status', value: paymentStatusFilter.value })
  }
  if (statusFilter.value) {
    filters.push({ id: 'status', value: statusFilter.value })
  }

  columnFilters.value = filters
}

// Сброс фильтров
function resetFilters() {
  clientFilter.value = ''
  shootingTypeFilter.value = ''
  paymentStatusFilter.value = ''
  statusFilter.value = ''
  columnFilters.value = []
}

// Actions
function handleAddBooking() {
  // Устанавливаем сегодняшнюю дату по умолчанию (UTC+5 из конфига)
  addModalDefaultDate.value = getLocalDateString()
  showAddModal.value = true
}

function handleEdit() {
  if (selectedBooking.value) {
    showEditModal.value = true
  }
}

function handleAddPayment() {
  if (selectedBooking.value) {
    showPaymentModal.value = true
  }
}

function handleDelete() {
  if (selectedBooking.value) {
    showDeleteModal.value = true
  }
}

function handleMarkCompleted() {
  if (selectedBooking.value) {
    showConfirmCompleted.value = true
  }
}

async function confirmMarkCompleted() {
  if (selectedBooking.value) {
    await bookingsStore.confirmSession(selectedBooking.value.id)
    rowSelection.value = {}
    showConfirmCompleted.value = false
  }
}

function handleDeliver() {
  if (selectedBooking.value) {
    showDeliverModal.value = true
  }
}

function handleView() {
  if (selectedBooking.value) {
    showViewModal.value = true
  }
}

function handleCancelBooking() {
  if (selectedBooking.value) {
    showCancelModal.value = true
  }
}

function closeModal() {
  showAddModal.value = false
  showEditModal.value = false
  showPaymentModal.value = false
  showDeleteModal.value = false
  showDeliverModal.value = false
  showViewModal.value = false
  showCancelModal.value = false
  // Сброс выделения после закрытия модального окна
  rowSelection.value = {}
}

function toggleFilters() {
  showFilters.value = !showFilters.value
}
</script>

<template>
  <div class="bookings-calendar table-general">
    <!-- Toolbar с заголовком и кнопками -->
    <div class="table-toolbar">
      <!-- Кнопки действий -->
      <div class="table-actions">
        <button
          class="glass-button"
          @click="handleAddBooking"
          title="Добавить запись"
        >
          <svg-icon type="mdi" :path="mdiTextBoxPlusOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || !canEdit"
          @click="handleEdit"
          title="Редактировать (только для 'new' и до начала съёмки)"
        >
          <svg-icon type="mdi" :path="mdiFileEditOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || isLocked || selectedBooking?.payment_status === 'fully_paid'"
          @click="handleAddPayment"
          title="Добавить оплату"
        >
          <svg-icon type="mdi" :path="mdiCashMultiple"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || !canDelete"
          @click="handleDelete"
          title="Удалить (только для 'new' без оплаты)"
        >
          <svg-icon type="mdi" :path="mdiTrashCanOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || !canMarkCompleted"
          @click="handleMarkCompleted"
          title="Подтвердить съёмку (new → in_progress)"
        >
          <svg-icon type="mdi" :path="mdiCameraOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || isLocked || selectedBooking?.status !== 'new'"
          @click="handleCancelBooking"
          title="Отменить запись (только для 'new')"
        >
          <svg-icon type="mdi" :path="mdiCameraOffOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || !canDeliver"
          @click="handleDeliver"
          title="Выдать съёмку"
        >
          <svg-icon type="mdi" :path="mdiFolderPlayOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow"
          @click="handleView"
          title="Посмотреть заказ"
        >
          <svg-icon type="mdi" :path="mdiEyeOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          @click="toggleFilters"
          title="Показать/скрыть фильтры"
        >
          <svg-icon type="mdi" :path="mdiFilterMenuOutline"></svg-icon>
        </button>
      </div>
    </div>

    <!-- Панель фильтров -->
    <div v-if="showFilters" class="filters-panel">
      <!-- Фильтр по клиенту -->
      <div class="filter-group">
        <label class="filter-label">Клиент:</label>
        <select class="filter-select" v-model="clientFilter" @change="applyFilters">
          <option value="">Все</option>
          <option v-for="client in uniqueClients" :key="client" :value="client">
            {{ client }}
          </option>
        </select>
      </div>

      <!-- Фильтр по типу съёмки -->
      <div class="filter-group">
        <label class="filter-label">Тип съёмки:</label>
        <select class="filter-select" v-model="shootingTypeFilter" @change="applyFilters">
          <option value="">Все</option>
          <option v-for="type in uniqueShootingTypes" :key="type" :value="type">
            {{ type }}
          </option>
        </select>
      </div>

      <!-- Фильтр по статусу оплаты -->
      <div class="filter-group">
        <label class="filter-label">Оплата:</label>
        <select class="filter-select" v-model="paymentStatusFilter" @change="applyFilters">
          <option value="">Все</option>
          <option v-for="status in paymentStatuses" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>
      </div>

      <!-- Фильтр по статусу записи -->
      <div class="filter-group">
        <label class="filter-label">Статус:</label>
        <select class="filter-select" v-model="statusFilter" @change="applyFilters">
          <option value="">Все</option>
          <option v-for="status in bookingStatuses" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>
      </div>

      <!-- Кнопка сброса фильтров -->
      <button class="glass-button" @click="resetFilters" title="Сбросить фильтры">
        <svg-icon type="mdi" :path="mdiFilterRemoveOutline"></svg-icon>
      </button>
    </div>

    <!-- Таблица -->
    <div v-if="bookingsStore.bookings.length > 0" class="table-scroll-container">
      <table class="accounting-table tanstack-table">
        <thead>
          <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <th
              v-for="header in headerGroup.headers"
              :key="header.id"
              @click="header.column.getToggleSortingHandler()?.($event)"
              :class="{ 'cursor-pointer': header.column.getCanSort() }"
            >
              <FlexRender
                v-if="!header.isPlaceholder"
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
              <span v-if="header.column.getIsSorted() === 'asc'"> ↑</span>
              <span v-else-if="header.column.getIsSorted() === 'desc'"> ↓</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in table.getRowModel().rows"
            :key="row.id"
            @click="row.toggleSelected()"
            :class="{
              selected: row.getIsSelected(),
              'cancelled-booking': row.original.status === 'cancelled_by_client' || row.original.status === 'cancelled_by_photographer' || row.original.status === 'client_no_show',
              'alert-booking': row.original.status === 'new' && new Date(row.original.shooting_date) < new Date()
            }"
          >
            <td v-for="cell in row.getVisibleCells()" :key="cell.id">
              <!-- Amount cells with special styling -->
              <template v-if="cell.column.id === 'total_amount'">
                <span class="amount-income">
                  <FlexRender
                    :render="cell.column.columnDef.cell"
                    :props="cell.getContext()"
                  />
                </span>
              </template>

              <!-- Default cell -->
              <template v-else>
                <FlexRender
                  :render="cell.column.columnDef.cell"
                  :props="cell.getContext()"
                />
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty state -->
    <div v-else class="empty-state">
      <p>📭 Нет записей за текущий месяц</p>
    </div>

    <!-- Модальные окна -->
    <AddBookingModal :isVisible="showAddModal" :defaultDate="addModalDefaultDate" @close="closeModal" />
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
