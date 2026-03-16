<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  type ColumnDef,
  type SortingState,
  FlexRender
} from '@tanstack/vue-table'
import DatePicker, { type DateRange } from '../ui/datePicker/DatePicker.vue'
import SearchTable from '../ui/searchTable/SearchTable.vue'
import { useBookingsStore } from '../../stores/bookings'
import BookingActionsModal from './BookingActionsModal.vue'
import BookingFormModal from './BookingFormModal.vue'
import AddPaymentModal from './AddPaymentModal.vue'
import DeleteConfirmModal from './DeleteConfirmModal.vue'
import DeliverBookingModal from './DeliverBookingModal.vue'
import ViewBookingModal from './ViewBookingModal.vue'
import CancelBookingModal from './CancelBookingModal.vue'
import ConfirmSessionModal from './ConfirmSessionModal.vue'
import RefundModal from './RefundModal.vue'

const bookingsStore = useBookingsStore()

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

function formatDate(dateString: string): string {
  if (!dateString) return ''
  const datePart = dateString.split('T')[0]?.split(' ')[0]
  if (!datePart) return ''
  const parts = datePart.split('-')
  const year = parts[0] || ''
  const month = parts[1] || ''
  const day = parts[2] || ''
  return `${day}.${month}.${year.slice(-2)}`
}

function formatDateTime(dateString: string): string {
  if (!dateString) return ''
  const [datePart, timePart] = dateString.split(' ')
  if (!datePart) return ''
  const parts = datePart.split('-')
  const year = parts[0] || ''
  const month = parts[1] || ''
  const day = parts[2] || ''
  const time = timePart?.substring(0, 5) || ''
  return time ? `${day}.${month}.${year.slice(-2)} ${time}` : `${day}.${month}.${year.slice(-2)}`
}

function getStatusText(status: string): string {
  const map: Record<string, string> = {
    new: '🔵 Новый',
    in_progress: '🟠 В работе',
    completed: '🟢 Выполнен',
    completed_partially: '🟡 Выполнен частично',
    not_completed: '🟤 Не выполнен',
    cancelled_by_client: '⚪ Отменён клиентом',
    cancelled_by_photographer: '⚪ Отменён фотографом',
    client_no_show: '⚪ Клиент не пришёл',
  }
  return map[status] ?? status
}

function getPaymentStatusText(status: string): string {
  const map: Record<string, string> = {
    unpaid: '🔴 Не оплачено',
    partially_paid: '🟡 Частично',
    fully_paid: '🟢 Оплачено',
  }
  return map[status] ?? status
}

function bookingGlobalFilterFn(row: any, columnId: string, filterValue: string): boolean {
  const search = filterValue.toLowerCase()
  let value = row.getValue(columnId)

  if (columnId === 'status') {
    value = getStatusText(value as string)
  } else if (columnId === 'payment_status') {
    value = getPaymentStatusText(value as string)
  }

  if (value === null || value === undefined) return false
  return String(value).toLowerCase().includes(search)
}

const columns: ColumnDef<any>[] = [
  { accessorKey: 'order_number', header: 'ID заказа', cell: ({ getValue }) => getValue() || '—' },
  { accessorKey: 'booking_date', header: 'Дата создания', cell: ({ getValue }) => formatDate(getValue() as string) },
  { accessorKey: 'shooting_date', header: 'Дата съёмки', cell: ({ getValue }) => formatDateTime(getValue() as string) },
  { accessorKey: 'delivery_date', header: 'Дата выдачи', cell: ({ getValue }) => formatDate(getValue() as string) },
  { accessorKey: 'client_name', header: 'Клиент' },
  { accessorKey: 'phone', header: 'Телефон' },
  { accessorKey: 'shooting_type_name', header: 'Тип съёмки' },
  { accessorKey: 'quantity', header: 'Кол-во' },
  {
    accessorKey: 'base_price',
    header: 'Стоимость',
    cell: ({ getValue }) => {
      const v = getValue()
      return v ? `${Math.round(parseFloat(v as string))}` : '—'
    },
  },
  {
    accessorKey: 'promo_discount_percent',
    header: 'Скидка',
    cell: ({ row }) => {
      const promotionId = row.original.promotion_id
      const discountPercent = parseFloat(row.original.promo_discount_percent) || 0
      return promotionId && discountPercent > 0 ? `-${discountPercent}%` : '—'
    },
  },
  { accessorKey: 'total_amount', header: 'Сумма ₽', cell: ({ getValue }) => `${Math.round(parseFloat(getValue() as string))}` },
  { accessorKey: 'payment_status', header: 'Статус оплаты', cell: ({ getValue }) => getPaymentStatusText(getValue() as string) },
  { accessorKey: 'status', header: 'Статус записи', cell: ({ getValue }) => getStatusText(getValue() as string) },
]

const sorting = ref<SortingState>([{ id: 'order_number', desc: true }])
const searchQuery = ref('')
const selectedIndex = ref<number | null>(null)

const table = useVueTable({
  get data() { return bookingsStore.bookings },
  columns,
  state: {
    get sorting() { return sorting.value },
    get globalFilter() { return searchQuery.value },
  },
  onSortingChange: updater => {
    sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater
  },
  onGlobalFilterChange: updater => {
    searchQuery.value = typeof updater === 'function' ? updater(searchQuery.value) : updater
  },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  globalFilterFn: bookingGlobalFilterFn,
})

const selectedBooking = computed(() => {
  if (selectedIndex.value === null) return null
  return table.getRowModel().rows[selectedIndex.value]?.original ?? null
})

function selectRow(index: number) {
  selectedIndex.value = selectedIndex.value === index ? null : index
}

const showActionsModal = ref(false)
const showAddModal = ref(false)
const showEditModal = ref(false)
const showPaymentModal = ref(false)
const showDeleteModal = ref(false)
const showDeliverModal = ref(false)
const showViewModal = ref(false)
const showCancelModal = ref(false)
const showConfirmSessionModal = ref(false)
const showRefundModal = ref(false)

function openActions() {
  if (selectedBooking.value) showActionsModal.value = true
}

function handleView() { showActionsModal.value = false; showViewModal.value = true }
function handleEdit() { showActionsModal.value = false; showEditModal.value = true }
function handlePayment() { showActionsModal.value = false; showPaymentModal.value = true }
function handleDelete() { showActionsModal.value = false; showDeleteModal.value = true }
function handleConfirmSession() { showActionsModal.value = false; showConfirmSessionModal.value = true }
function handleCancel() { showActionsModal.value = false; showCancelModal.value = true }
function handleDeliver() { showActionsModal.value = false; showDeliverModal.value = true }
function handleRefund() { showActionsModal.value = false; showRefundModal.value = true }

function closeModal() {
  showActionsModal.value = false
  showAddModal.value = false
  showEditModal.value = false
  showPaymentModal.value = false
  showDeleteModal.value = false
  showDeliverModal.value = false
  showViewModal.value = false
  showCancelModal.value = false
  showConfirmSessionModal.value = false
  showRefundModal.value = false
  selectedIndex.value = null
}

const CANCELLED_STATUSES = new Set([
  'cancelled_by_client',
  'cancelled_by_photographer',
  'client_no_show',
])

function getRowClass(booking: any, rowIndex: number): Record<string, boolean> {
  return {
    'row-selected': selectedIndex.value === rowIndex,
    'row-cancelled': CANCELLED_STATUSES.has(booking.status),
    'row-overdue': booking.status === 'new' && new Date(booking.shooting_date) < new Date(),
  }
}
</script>

<template>
  <div class="padGlass padGlass-work data-table-panel">
    <div class="pad-title">Записи</div>

    <!-- Фильтр диапазона дат -->
    <div class="data-table-filter">
      <DatePicker mode="range" v-model="dateRange" :showPresets="true" />
      <SearchTable
        v-model="searchQuery"
        :count="table.getFilteredRowModel().rows.length"
        placeholder="Поиск по записям..."
      />
    </div>

    <!-- Таблица -->
    <div v-if="bookingsStore.bookings.length > 0" class="data-table-scroll">
      <table class="data-table">
        <thead>
          <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <th
              v-for="header in headerGroup.headers"
              :key="header.id"
              :class="{ sortable: header.column.getCanSort() }"
              @click="header.column.getToggleSortingHandler()?.($event)"
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
            v-for="(row, rowIndex) in table.getRowModel().rows"
            :key="row.id"
            :class="getRowClass(row.original, rowIndex)"
            @click="selectRow(rowIndex); openActions()"
          >
            <td
              v-for="cell in row.getVisibleCells()"
              :key="cell.id"
              :class="{ 'cell-amount': cell.column.id === 'total_amount' }"
            >
              <FlexRender
                :render="cell.column.columnDef.cell"
                :props="cell.getContext()"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Пустое состояние -->
    <div v-else class="data-table-empty">
      📭 Нет записей за выбранный период
    </div>

    <!-- Модальные окна -->
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
    <BookingFormModal mode="add" :isVisible="showAddModal" @close="closeModal" />
    <BookingFormModal mode="edit" :isVisible="showEditModal" :booking="selectedBooking" @close="closeModal" />
    <AddPaymentModal :isVisible="showPaymentModal" :booking="selectedBooking" @close="closeModal" />
    <DeleteConfirmModal :isVisible="showDeleteModal" :booking="selectedBooking" @close="closeModal" />
    <ConfirmSessionModal :isVisible="showConfirmSessionModal" :booking="selectedBooking" @close="closeModal" @openPayment="handlePayment" />
    <DeliverBookingModal :isVisible="showDeliverModal" :booking="selectedBooking" @close="closeModal" @openPayment="handlePayment" />
    <ViewBookingModal :isVisible="showViewModal" :booking="selectedBooking" @close="closeModal" />
    <CancelBookingModal :isVisible="showCancelModal" :booking="selectedBooking" @close="closeModal" />
    <RefundModal :isVisible="showRefundModal" :booking="selectedBooking" @close="closeModal" />
  </div>
</template>
