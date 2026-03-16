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
import { useReferencesStore } from '../../../stores/references'
import ExpensesActionsModal from './ExpensesActionsModal.vue'
import AddExpenseModal from './AddExpenseModal.vue'
import ViewExpenseModal from './ViewExpenseModal.vue'
import RefundExpenseModal from './RefundExpenseModal.vue'
import ConfirmModal from '../../ConfirmModal.vue'

const financeStore = useFinanceStore()
const refsStore = useReferencesStore()

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

onMounted(async () => {
  if (refsStore.expenseCategories.length === 0) {
    await refsStore.fetchExpenseCategories()
  }
  financeStore.fetchExpenses(dateRange.value.from, dateRange.value.to)
})

watch(dateRange, (range) => {
  financeStore.fetchExpenses(range.from, range.to)
}, { deep: true })

const selectedIndex = ref<number | null>(null)

const selectedExpense = computed(() => {
  if (selectedIndex.value === null) return null
  return table.getRowModel().rows[selectedIndex.value]?.original ?? null
})

function selectRow(index: number) {
  selectedIndex.value = selectedIndex.value === index ? null : index
}

function formatDate(dateString: string): string {
  if (!dateString) return '—'
  const datePart = dateString.split('T')[0]?.split(' ')[0]
  if (!datePart) return '—'
  const parts = datePart.split('-')
  return `${parts[2]}.${parts[1]}.${(parts[0] || '').slice(-2)}`
}

function expensesGlobalFilterFn(row: any, _columnId: string, filterValue: string): boolean {
  const search = filterValue.toLowerCase()
  const { id, date, category_name, description, amount } = row.original
  return [
    String(id),
    formatDate(date),
    category_name || '',
    description || '',
    Math.round(parseFloat(amount)).toString()
  ].some(v => v.toLowerCase().includes(search))
}
expensesGlobalFilterFn.autoRemove = (val: any) => !val

const columns: ColumnDef<any>[] = [
  { accessorKey: 'id', header: 'ID', cell: ({ getValue }) => getValue() },
  { accessorKey: 'date', header: 'Дата', cell: ({ getValue }) => formatDate(getValue() as string) },
  { accessorKey: 'category_name', header: 'Категория', cell: ({ getValue }) => getValue() || '—' },
  { accessorKey: 'description', header: 'Описание', cell: ({ getValue }) => getValue() || '—' },
  {
    accessorKey: 'amount',
    header: 'Сумма ₽',
    cell: ({ getValue }) => Math.round(parseFloat(getValue() as string)).toString()
  }
]

const sorting = ref<SortingState>([{ id: 'date', desc: true }])
const searchQuery = ref('')

const table = useVueTable({
  get data() { return financeStore.expenses },
  columns,
  state: {
    get sorting() { return sorting.value },
    get globalFilter() { return searchQuery.value }
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
  globalFilterFn: expensesGlobalFilterFn
})

const totalAmount = computed(() => {
  return table.getFilteredRowModel().rows.reduce((sum, row) => {
    return sum + (parseFloat(row.original.amount) || 0)
  }, 0)
})

const showActionsModal = ref(false)
const showAddModal = ref(false)
const showViewModal = ref(false)
const showRefundModal = ref(false)
const showDeleteModal = ref(false)

function openActions() {
  if (selectedExpense.value) showActionsModal.value = true
}

function closeModal() {
  showActionsModal.value = false
  showAddModal.value = false
  showViewModal.value = false
  showRefundModal.value = false
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

function handleRefund() {
  showActionsModal.value = false
  showRefundModal.value = true
}

function handleDelete() {
  showActionsModal.value = false
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!selectedExpense.value) return
  await financeStore.deleteExpense(selectedExpense.value.id)
  await financeStore.fetchExpenses(dateRange.value.from, dateRange.value.to)
  closeModal()
}
</script>

<template>
  <div class="padGlass padGlass-work data-table-panel">
    <div class="pad-title">Расход</div>

    <div class="data-table-filter">
      <DatePicker mode="range" v-model="dateRange" :showPresets="true" />
      <SearchTable
        v-model="searchQuery"
        :count="table.getFilteredRowModel().rows.length"
        placeholder="Поиск по расходам..."
      />
    </div>

    <div v-if="financeStore.expenses.length > 0" class="data-table-scroll">
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
            <td colspan="4"></td>
            <td class="cell-amount">{{ Math.round(totalAmount) }} ₽</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div v-else class="data-table-empty">
      📭 Нет расходов за выбранный период
    </div>

    <ExpensesActionsModal
      v-if="showActionsModal"
      :expense="selectedExpense"
      @close="closeModal"
      @add="handleAdd"
      @view="handleView"
      @refund="handleRefund"
      @delete="handleDelete"
    />

    <AddExpenseModal
      :isVisible="showAddModal"
      :dateFrom="dateRange.from"
      :dateTo="dateRange.to"
      @close="closeModal"
    />

    <ViewExpenseModal
      v-if="showViewModal"
      :isVisible="showViewModal"
      :expense="selectedExpense"
      @close="closeModal"
    />

    <RefundExpenseModal
      :isVisible="showRefundModal"
      :dateFrom="dateRange.from"
      :dateTo="dateRange.to"
      @close="closeModal"
    />

    <ConfirmModal
      :isVisible="showDeleteModal"
      message="Удалить этот расход?"
      title="Подтверждение"
      @confirm="confirmDelete"
      @cancel="closeModal"
    />
  </div>
</template>
