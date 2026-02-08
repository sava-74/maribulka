<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
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
import { mdilPlus, mdilDelete, mdilMagnify, mdilRefresh, mdilEye } from '@mdi/light-js'
import { mdiFolderPlayOutline, mdiCameraOutline, mdiCameraOffOutline, mdiFileEditOutline, mdiCashMultiple } from '@mdi/js'
import { useBookingsStore } from '../../stores/bookings'
import AddBookingModal from './AddBookingModal.vue'
import EditBookingModal from './EditBookingModal.vue'
import AddPaymentModal from './AddPaymentModal.vue'
import DeleteConfirmModal from './DeleteConfirmModal.vue'
import DeliverBookingModal from './DeliverBookingModal.vue'
import ViewBookingModal from './ViewBookingModal.vue'
import CancelBookingModal from './CancelBookingModal.vue'
import ConfirmModal from '../ConfirmModal.vue'
import '../../assets/tables.css'
import '../../assets/buttons.css'
import '../../assets/layout.css'
import '../../assets/modal.css'
import '../../assets/responsive.css'

const bookingsStore = useBookingsStore()

// Row selection state
const rowSelection = ref<RowSelectionState>({})
const sorting = ref<SortingState>([{ id: 'id', desc: true }])
const columnFilters = ref<ColumnFiltersState>([])

// Видимость панели фильтров (по умолчанию скрыты)
const showFilters = ref(false)

// Ref для month input
const monthInput = ref<HTMLInputElement | null>(null)

// Модальные окна
const showAddModal = ref(false)
const showEditModal = ref(false)
const showPaymentModal = ref(false)
const showDeleteModal = ref(false)
const showDeliverModal = ref(false)
const showViewModal = ref(false)
const showCancelModal = ref(false)
const showConfirmCompleted = ref(false)

onMounted(() => {
  // Сброс фильтров и установка текущего месяца при входе на вкладку
  resetFilters()
  const currentMonth = new Date().toISOString().slice(0, 7)
  bookingsStore.setCurrentMonth(currentMonth)
})

// Форматирование даты в DD.MM.YYYY (убираем время если есть)
function formatDate(dateString: string) {
  if (!dateString) return ''
  // Убираем время если есть (2026-02-01T12:30:00 -> 2026-02-01)
  const datePart = dateString.split('T')[0]?.split(' ')[0]
  if (!datePart) return ''
  const [year, month, day] = datePart.split('-')
  return `${day}.${month}.${year}`
}

// Форматирование даты и времени в DD.MM.YYYY HH:mm
function formatDateTime(dateString: string) {
  if (!dateString) return ''
  const [datePart, timePart] = dateString.split(' ')
  if (!datePart) return ''
  const [year, month, day] = datePart.split('-')
  const time = timePart?.substring(0, 5) || ''
  return time ? `${day}.${month}.${year} ${time}` : `${day}.${month}.${year}`
}

// Helper functions
function getStatusText(status: string) {
  switch (status) {
    case 'new': return '🟡 Новая'
    case 'completed': return '🟠 Состоялась'
    case 'delivered': return '🟢 Проведено'
    case 'cancelled': return '🔴 Отменена'
    case 'cancelled_client': return '⚪ Отмена-К'
    case 'cancelled_photographer': return '⚪ Отмена-Ф'
    case 'failed': return '🔴 Не состоялась'
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
    accessorKey: 'id',
    header: 'ID заказа',
    cell: ({ row }) => {
      const id = row.original.id
      const createdAt = row.original.created_at || ''
      const year = createdAt.slice(0, 4)
      return `МБ-${id}.${year}`
    }
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
      return value ? `${Math.round(parseFloat(value as string))} ₽` : '—'
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
    header: 'Сумма',
    cell: ({ getValue }) => `${Math.round(parseFloat(getValue() as string))} ₽`
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

// Computed: проверка что заказ проведён или отменён (нельзя редактировать)
const isDelivered = computed(() => {
  const status = selectedBooking.value?.status
  return status === 'delivered' || status === 'cancelled_client' || status === 'cancelled_photographer'
})

// Computed: проверка что можно отметить "Съёмка состоялась" (в день съёмки и позже, если не отменена)
const canMarkCompleted = computed(() => {
  if (!selectedBooking.value) return false
  const status = selectedBooking.value.status
  if (status === 'cancelled') return false
  if (status !== 'new' && status !== 'failed') return false
  const shootingDate = new Date(selectedBooking.value.shooting_date)
  shootingDate.setHours(0, 0, 0, 0)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return shootingDate.getTime() <= today.getTime()
})

// Computed: проверка что можно выдать (статус "состоялась" и не проведено)
const canDeliver = computed(() => {
  if (!selectedBooking.value) return false
  if (isDelivered.value) return false
  return selectedBooking.value.status === 'completed'
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
  { value: 'new', label: '🟡 Новая' },
  { value: 'completed', label: '🟠 Завершена' },
  { value: 'delivered', label: '🟢 Проведено' },
  { value: 'cancelled_client', label: '⚪ Отменил клиент' },
  { value: 'cancelled_photographer', label: '⚪ Отменил фотограф' },
  { value: 'failed', label: '🔴 Не состоялась' }
]

// Filters
const clientFilter = ref('')
const shootingTypeFilter = ref('')
const paymentStatusFilter = ref('')
const statusFilter = ref('')

// Применение фильтров к таблице
function applyFilters() {
  const filters: ColumnFiltersState = []

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

// Смена месяца
function handleMonthChange(event: Event) {
  const target = event.target as HTMLSelectElement
  bookingsStore.setCurrentMonth(target.value)
}

// Actions
function handleAddBooking() {
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
    await bookingsStore.markAsCompleted(selectedBooking.value.id)
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

// Открытие календаря при клике на весь input
function openMonthPicker() {
  if (monthInput.value) {
    monthInput.value.showPicker?.()
  }
}
</script>

<template>
  <div class="bookings-calendar">
    <!-- Toolbar с заголовком и кнопками -->
    <div class="header-with-action">
      <div>
        <h2 class="section-header">Записи на съёмку</h2>
      </div>

      <!-- Кнопки действий -->
      <div class="action-buttons">
        <button
          class="glass-button"
          @click="handleAddBooking"
          title="Добавить запись"
        >
          <svg-icon type="mdi" :path="mdilPlus"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || isDelivered || selectedBooking?.status === 'completed'"
          @click="handleEdit"
          title="Редактировать"
        >
          <svg-icon type="mdi" :path="mdiFileEditOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || isDelivered || selectedBooking?.payment_status === 'fully_paid'"
          @click="handleAddPayment"
          title="Добавить оплату"
        >
          <svg-icon type="mdi" :path="mdiCashMultiple"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || isDelivered || selectedBooking?.status === 'completed'"
          @click="handleDelete"
          title="Удалить"
        >
          <svg-icon type="mdi" :path="mdilDelete"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || !canMarkCompleted"
          @click="handleMarkCompleted"
          title="Съёмка состоялась"
        >
          <svg-icon type="mdi" :path="mdiCameraOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || isDelivered || selectedBooking?.status === 'completed'"
          @click="handleCancelBooking"
          title="Отменить запись"
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
          <svg-icon type="mdi" :path="mdilEye"></svg-icon>
        </button>
        <button
          class="glass-button"
          @click="toggleFilters"
          title="Показать/скрыть фильтры"
        >
          <svg-icon type="mdi" :path="mdilMagnify"></svg-icon>
        </button>
      </div>
    </div>

    <!-- Панель фильтров -->
    <div v-if="showFilters" class="filters-panel">
      <!-- Селектор месяца -->
      <div class="filter-group">
        <label class="filter-label" style="user-select: none;">Месяц:</label>
        <input
          ref="monthInput"
          type="month"
          class="filter-select"
          :value="bookingsStore.currentMonth"
          @change="handleMonthChange"
          @click="openMonthPicker"
        />
      </div>

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
        <svg-icon type="mdi" :path="mdilRefresh"></svg-icon>
      </button>
    </div>

    <!-- Таблица -->
    <div v-if="bookingsStore.bookings.length > 0" class="table-container">
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
              'failed-booking': row.original.status === 'failed',
              'cancelled-booking': row.original.status === 'cancelled_client' || row.original.status === 'cancelled_photographer'
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
    <AddBookingModal :isVisible="showAddModal" @close="closeModal" />
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
