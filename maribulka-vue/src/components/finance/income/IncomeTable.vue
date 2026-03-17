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
import DatePicker, { type DateRange } from '../../ui/datePicker/DatePicker.vue'
import SearchTable from '../../ui/searchTable/SearchTable.vue'
import { useFinanceStore } from '../../../stores/finance'
import ViewIncomeModal from './ViewIncomeModal.vue'
import IncomeActionsModal from './IncomeActionsModal.vue'
import AddIncomeModal from './AddIncomeModal.vue'
import ConfirmModal from '../../ConfirmModal.vue'

const financeStore = useFinanceStore()

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
  financeStore.fetchIncome(dateRange.value.from, dateRange.value.to)
})

watch(dateRange, (range) => {
  financeStore.fetchIncome(range.from, range.to)
}, { deep: true })

const selectedIndex = ref<number | null>(null)
const isEmpty = ref(false)

const selectedIncome = computed(() => {
  if (selectedIndex.value === null) return null
  return table.getRowModel().rows[selectedIndex.value]?.original ?? null
})

function selectRow(index: number) {
  selectedIndex.value = selectedIndex.value === index ? null : index
}

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

function getBookingStatusText(status: string): string {
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

function incomeGlobalFilterFn(row: any, columnId: string, filterValue: string): boolean {
  const search = filterValue.toLowerCase()
  let value = row.getValue(columnId)
  if (columnId === 'booking_status') value = getBookingStatusText(value as string)
  else if (columnId === 'payment_status') value = getPaymentStatusText(value as string)
  if (value === null || value === undefined) return false
  return String(value).toLowerCase().includes(search)
}

const columns: ColumnDef<any>[] = [
  { accessorKey: 'id', header: 'ID платежа', cell: ({ getValue }) => getValue() || '—' },
  { accessorKey: 'date', header: 'Дата', cell: ({ getValue }) => formatDate(getValue() as string) },
  { accessorKey: 'order_number', header: 'ID заказа', cell: ({ getValue }) => getValue() || '—' },
  { accessorKey: 'client_name', header: 'Клиент' },
  {
    accessorKey: 'total_amount',
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
  { accessorKey: 'amount', header: 'Сумма ₽', cell: ({ getValue }) => `${Math.round(parseFloat(getValue() as string))}` },
  { accessorKey: 'booking_status', header: 'Статус заказа', cell: ({ getValue }) => getBookingStatusText(getValue() as string) },
  { accessorKey: 'payment_status', header: 'Статус оплаты', cell: ({ getValue }) => getPaymentStatusText(getValue() as string) },
]

const sorting = ref<SortingState>([{ id: 'date', desc: true }])
const searchQuery = ref('')

const table = useVueTable({
  get data() { return financeStore.income },
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
  globalFilterFn: incomeGlobalFilterFn,
})

const totalAmount = computed(() => {
  return table.getFilteredRowModel().rows.reduce((sum, row) => {
    return sum + (parseFloat(row.original.amount) || 0)
  }, 0)
})

const showActionsModal = ref(false)
const showAddModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)

function openActions() {
  isEmpty.value = false
  if (selectedIncome.value) showActionsModal.value = true
}

function openEmptyActions() {
  isEmpty.value = true
  showActionsModal.value = true
}

function closeModal() {
  showActionsModal.value = false
  showAddModal.value = false
  showViewModal.value = false
  showDeleteModal.value = false
  selectedIndex.value = null
}

function handleAdd() {
  showActionsModal.value = false
  showAddModal.value = true
}

function handleView() {
  showActionsModal.value = false
  showViewModal.value = true
}

function handleDelete() {
  showActionsModal.value = false
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!selectedIncome.value) return
  await financeStore.deleteIncome(selectedIncome.value.id)
  closeModal()
}
</script>

<template>
  <div class="padGlass padGlass-work data-table-panel">
    <div class="pad-title">Приход</div>

    <!-- Filter bar -->
    <div class="data-table-filter">
      <DatePicker mode="range" v-model="dateRange" :showPresets="true" />
      <SearchTable
        v-model="searchQuery"
        :count="table.getFilteredRowModel().rows.length"
        placeholder="Поиск по поступлениям..."
      />
    </div>

    <!-- Table -->
    <div class="data-table-scroll">
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
            v-if="financeStore.income.length === 0"
            class="row-empty"
            @click="openEmptyActions"
          >
            <td :colspan="columns.length" class="cell-empty">+ Добавить приход</td>
          </tr>
          <tr
            v-else
            v-for="(row, rowIndex) in table.getRowModel().rows"
            :key="row.id"
            :class="{ 'row-selected': selectedIndex === rowIndex }"
            @click="selectRow(rowIndex); openActions()"
          >
            <td
              v-for="cell in row.getVisibleCells()"
              :key="cell.id"
              :class="{ 'cell-amount': cell.column.id === 'amount' }"
            >
              <FlexRender
                :render="cell.column.columnDef.cell"
                :props="cell.getContext()"
              />
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="6"></td>
            <td class="cell-amount">{{ Math.round(totalAmount) }} ₽</td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Modals -->
    <IncomeActionsModal
      v-if="showActionsModal"
      :income="selectedIncome"
      :isEmpty="isEmpty"
      @close="closeModal"
      @add="handleAdd"
      @view="handleView"
      @delete="handleDelete"
    />

    <AddIncomeModal
      :isVisible="showAddModal"
      :dateFrom="dateRange.from"
      :dateTo="dateRange.to"
      @close="closeModal"
    />

    <ViewIncomeModal
      v-if="showViewModal"
      :isVisible="showViewModal"
      :income="selectedIncome"
      :dateFrom="dateRange.from"
      :dateTo="dateRange.to"
      @close="closeModal"
    />

    <ConfirmModal
      :isVisible="showDeleteModal"
      message="Удалить этот платёж?"
      title="Подтверждение"
      @confirm="confirmDelete"
      @cancel="closeModal"
    />
  </div>
</template>
