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
import { mdilDelete, mdilMagnify, mdilRefresh, mdilEye } from '@mdi/light-js'
import { useFinanceStore } from '../../stores/finance'
import ConfirmModal from '../ConfirmModal.vue'
import ViewIncomeModal from './ViewIncomeModal.vue'
import '../../assets/tables.css'
import '../../assets/buttons.css'
import '../../assets/layout.css'
import '../../assets/modal.css'
import '../../assets/responsive.css'

const financeStore = useFinanceStore()

// Row selection state
const rowSelection = ref<RowSelectionState>({})
const sorting = ref<SortingState>([{ id: 'date', desc: true }])
const columnFilters = ref<ColumnFiltersState>([])

// Видимость панели фильтров
const showFilters = ref(false)

// Ref для month input
const monthInput = ref<HTMLInputElement | null>(null)

// Модальные окна
const showViewModal = ref(false)
const showDeleteModal = ref(false)

onMounted(() => {
  const currentMonth = new Date().toISOString().slice(0, 7)
  financeStore.setCurrentMonth(currentMonth)
})

// Форматирование даты в DD.MM.YY
function formatDate(dateString: string) {
  if (!dateString) return ''
  const datePart = dateString.split('T')[0]?.split(' ')[0]
  if (!datePart) return ''
  const parts = datePart.split('-')
  const year = parts[0] || ''
  const month = parts[1] || ''
  const day = parts[2] || ''
  const shortYear = year.slice(-2)
  return `${day}.${month}.${shortYear}`
}

// Форматирование суммы (БЕЗ символа ₽)
function formatAmount(amount: string | number) {
  return Math.round(parseFloat(amount.toString())).toString()
}

// Форматирование статуса заказа
function formatBookingStatus(status: string) {
  const statusMap: Record<string, string> = {
    'new': '🔵 Новая',
    'completed': '🟠 Состоялась',
    'delivered': '🟢 Выдано',
    'failed': '🔴 Не состоялась',
    'cancelled': '🔴 Отменена',
    'cancelled_client': '⚪ Отмена-К',
    'cancelled_photographer': '⚪ Отмена-Ф'
  }
  return statusMap[status] || status
}

// Форматирование статуса оплаты
function formatPaymentStatus(status: string) {
  const statusMap: Record<string, string> = {
    'unpaid': 'Не оплачена',
    'partially_paid': 'Частично оплачена',
    'fully_paid': 'Полностью оплачена'
  }
  return statusMap[status] || status
}

// Column definitions (БЕЗ checkbox)
const columns: ColumnDef<any>[] = [
  {
    accessorKey: 'date',
    header: 'Дата',
    cell: ({ getValue }) => formatDate(getValue() as string)
  },
  {
    accessorKey: 'order_number',
    header: 'ID заказа',
    cell: ({ getValue }) => getValue() || '—'
  },
  {
    accessorKey: 'client_name',
    header: 'Клиент'
  },
  {
    accessorKey: 'total_amount',
    header: 'Стоимость ₽',
    cell: ({ getValue }) => formatAmount(getValue() as string | number)
  },
  {
    accessorKey: 'promo_discount_percent',
    header: 'Скидка %',
    cell: ({ getValue }) => {
      const discount = getValue()
      return discount ? `${discount}%` : '—'
    }
  },
  {
    accessorKey: 'amount',
    header: 'Сумма ₽',
    cell: ({ getValue }) => formatAmount(getValue() as string | number)
  },
  {
    accessorKey: 'booking_status',
    header: 'Статус заказа',
    cell: ({ getValue }) => formatBookingStatus(getValue() as string)
  },
  {
    accessorKey: 'payment_status',
    header: 'Статус оплаты',
    cell: ({ getValue }) => formatPaymentStatus(getValue() as string)
  }
]

// Create table instance
const table = useVueTable({
  get data() {
    return financeStore.income
  },
  columns,
  state: {
    get sorting() {
      return sorting.value
    },
    get rowSelection() {
      return rowSelection.value
    },
    get columnFilters() {
      return columnFilters.value
    }
  },
  enableRowSelection: true,
  enableMultiRowSelection: false, // Только одна строка
  onSortingChange: updaterOrValue => {
    sorting.value = typeof updaterOrValue === 'function'
      ? updaterOrValue(sorting.value)
      : updaterOrValue
  },
  onRowSelectionChange: updaterOrValue => {
    rowSelection.value = typeof updaterOrValue === 'function'
      ? updaterOrValue(rowSelection.value)
      : updaterOrValue
  },
  onColumnFiltersChange: updaterOrValue => {
    columnFilters.value = typeof updaterOrValue === 'function'
      ? updaterOrValue(columnFilters.value)
      : updaterOrValue
  },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getFacetedUniqueValues: getFacetedUniqueValues()
})

// Computed: выбранная строка
const selectedIncome = computed(() => {
  const selected = table.getFilteredSelectedRowModel().rows
  return selected.length === 1 && selected[0] ? selected[0].original : null
})

const hasSelectedRow = computed(() => {
  return Object.keys(rowSelection.value).length === 1
})

// Проверка: можно ли удалить выбранный платёж
const canDeleteSelected = computed(() => {
  if (!selectedIncome.value) return false
  const status = selectedIncome.value.booking_status
  // Нельзя удалить если заказ "Состоялась" или "Выдано"
  return status !== 'completed' && status !== 'delivered'
})

// Computed: уникальные значения для фильтров
const uniqueClients = computed(() => {
  const column = table.getColumn('client_name')
  if (!column) return []
  const facetedValues = column.getFacetedUniqueValues()
  return Array.from(facetedValues.keys()).sort()
})

const categories = [
  { value: 'advance', label: 'Аванс' },
  { value: 'balance', label: 'Доплата' },
  { value: 'full', label: 'Полная оплата' }
]

// Filters
const clientFilter = ref('')
const categoryFilter = ref('')

// Применение фильтров к таблице
function applyFilters() {
  const filters: ColumnFiltersState = []

  if (clientFilter.value) {
    filters.push({ id: 'client_name', value: clientFilter.value })
  }
  if (categoryFilter.value) {
    filters.push({ id: 'category', value: categoryFilter.value })
  }

  columnFilters.value = filters
}

// Сброс фильтров
function resetFilters() {
  clientFilter.value = ''
  categoryFilter.value = ''
  columnFilters.value = []
}

// Смена месяца
function handleMonthChange(event: Event) {
  const target = event.target as HTMLInputElement
  financeStore.setCurrentMonth(target.value)
}

// Actions
function handleView() {
  if (!selectedIncome.value) return
  showViewModal.value = true
}

function handleDelete() {
  if (!selectedIncome.value) return
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!selectedIncome.value) return
  await financeStore.deleteIncome(selectedIncome.value.id)
  rowSelection.value = {}
  showDeleteModal.value = false
}

function toggleFilters() {
  showFilters.value = !showFilters.value
}

function openMonthPicker() {
  if (monthInput.value) {
    monthInput.value.showPicker?.()
  }
}

function refreshData() {
  financeStore.fetchIncome(financeStore.currentMonth)
}
</script>

<template>
  <div class="table-general">
    <!-- Toolbar с кнопками -->
    <div class="header-with-action">
      <div class="action-buttons">
        <button
          class="glass-button"
          :disabled="!hasSelectedRow"
          @click="handleView"
          title="Посмотреть платёж"
        >
          <svg-icon type="mdi" :path="mdilEye"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow || !canDeleteSelected"
          @click="handleDelete"
          title="Удалить"
        >
          <svg-icon type="mdi" :path="mdilDelete"></svg-icon>
        </button>
        <button
          class="glass-button"
          @click="toggleFilters"
          title="Показать/скрыть фильтры"
        >
          <svg-icon type="mdi" :path="mdilMagnify"></svg-icon>
        </button>
        <button
          class="glass-button"
          @click="refreshData"
          title="Обновить данные"
        >
          <svg-icon type="mdi" :path="mdilRefresh"></svg-icon>
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
          :value="financeStore.currentMonth"
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

      <!-- Фильтр по категории -->
      <div class="filter-group">
        <label class="filter-label">Категория:</label>
        <select class="filter-select" v-model="categoryFilter" @change="applyFilters">
          <option value="">Все</option>
          <option v-for="cat in categories" :key="cat.value" :value="cat.value">
            {{ cat.label }}
          </option>
        </select>
      </div>

      <!-- Кнопка сброса фильтров -->
      <button class="glass-button" @click="resetFilters" title="Сбросить фильтры">
        <svg-icon type="mdi" :path="mdilRefresh"></svg-icon>
      </button>
    </div>

    <!-- Таблица -->
    <div v-if="financeStore.income.length > 0" class="table-scroll-container">
      <table class="accounting-table">
        <thead>
          <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <th
              v-for="header in headerGroup.headers"
              :key="header.id"
              @click="header.column.getToggleSortingHandler()?.($event)"
              :class="{ sortable: header.column.getCanSort() }"
            >
              <FlexRender
                v-if="!header.isPlaceholder"
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
              <span v-if="header.column.getIsSorted()">
                {{ header.column.getIsSorted() === 'asc' ? ' ↑' : ' ↓' }}
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in table.getRowModel().rows"
            :key="row.id"
            :class="{ selected: row.getIsSelected() }"
            @click="row.toggleSelected()"
          >
            <td
              v-for="cell in row.getVisibleCells()"
              :key="cell.id"
              v-html="typeof cell.column.columnDef.cell === 'function'
                ? (cell.column.columnDef.cell as any)(cell.getContext())
                : cell.getValue()"
            />
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="empty-state">
      <p>📭 Нет поступлений за выбранный месяц</p>
    </div>

    <!-- Всего за месяц (ПОСЛЕ таблицы) -->
    <div class="table-total">
      <span>Всего за месяц: <strong>{{ financeStore.totalIncome.toFixed(2) }} ₽</strong></span>
    </div>

    <!-- Модалки -->
    <ViewIncomeModal
      :is-visible="showViewModal"
      :income="selectedIncome"
      @close="showViewModal = false; rowSelection = {}"
    />

    <ConfirmModal
      :is-visible="showDeleteModal"
      :message="`Вы уверены, что хотите удалить платёж?`"
      title="Подтверждение удаления"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>
