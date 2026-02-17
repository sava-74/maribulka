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
import { mdilDelete, mdilMagnify, mdilRefresh, mdilEye, mdilPlus, mdilPencil } from '@mdi/light-js'
import { useFinanceStore } from '../../stores/finance'
import ConfirmModal from '../ConfirmModal.vue'
import ViewExpenseModal from './ViewExpenseModal.vue'
import AddExpenseModal from './AddExpenseModal.vue'
import EditExpenseModal from './EditExpenseModal.vue'
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
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

onMounted(() => {
  const currentMonth = new Date().toISOString().slice(0, 7)
  financeStore.setCurrentExpenseMonth(currentMonth)
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

// Column definitions (БЕЗ checkbox)
const columns: ColumnDef<any>[] = [
  {
    accessorKey: 'date',
    header: 'Дата',
    cell: ({ getValue }) => formatDate(getValue() as string)
  },
  {
    accessorKey: 'amount',
    header: 'Сумма ₽',
    cell: ({ getValue }) => formatAmount(getValue() as string | number)
  },
  {
    accessorKey: 'category_name',
    header: 'Категория'
  },
  {
    accessorKey: 'description',
    header: 'Описание',
    cell: ({ getValue }) => getValue() || '—'
  }
]

// Create table instance
const table = useVueTable({
  get data() {
    return financeStore.expenses
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
const selectedExpense = computed(() => {
  const selected = table.getFilteredSelectedRowModel().rows
  return selected.length === 1 && selected[0] ? selected[0].original : null
})

const hasSelectedRow = computed(() => {
  return Object.keys(rowSelection.value).length === 1
})

// Computed: уникальные значения для фильтров
const uniqueCategories = computed(() => {
  const column = table.getColumn('category_name')
  if (!column) return []
  const facetedValues = column.getFacetedUniqueValues()
  return Array.from(facetedValues.keys()).sort()
})

// Filters
const categoryFilter = ref('')

// Применение фильтров к таблице
function applyFilters() {
  const filters: ColumnFiltersState = []

  if (categoryFilter.value) {
    filters.push({ id: 'category_name', value: categoryFilter.value })
  }

  columnFilters.value = filters
}

// Сброс фильтров
function resetFilters() {
  categoryFilter.value = ''
  columnFilters.value = []
}

// Смена месяца
function handleMonthChange(event: Event) {
  const target = event.target as HTMLInputElement
  financeStore.setCurrentExpenseMonth(target.value)
}

// Actions
function handleAdd() {
  showAddModal.value = true
}

function handleEdit() {
  if (!selectedExpense.value) return
  showEditModal.value = true
}

function handleView() {
  if (!selectedExpense.value) return
  showViewModal.value = true
}

function handleDelete() {
  if (!selectedExpense.value) return
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!selectedExpense.value) return
  await financeStore.deleteExpense(selectedExpense.value.id)
  rowSelection.value = {}
  showDeleteModal.value = false
}

function handleSuccess() {
  financeStore.fetchExpenses(financeStore.currentExpenseMonth)
  rowSelection.value = {}
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
  financeStore.fetchExpenses(financeStore.currentExpenseMonth)
}
</script>

<template>
  <div class="table-general">
    <!-- Toolbar с кнопками -->
    <div class="header-with-action">
      <div class="action-buttons">
        <button
          class="glass-button"
          @click="handleAdd"
          title="Добавить расход"
        >
          <svg-icon type="mdi" :path="mdilPlus"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow"
          @click="handleEdit"
          title="Редактировать расход"
        >
          <svg-icon type="mdi" :path="mdilPencil"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow"
          @click="handleView"
          title="Посмотреть расход"
        >
          <svg-icon type="mdi" :path="mdilEye"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow"
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
          :value="financeStore.currentExpenseMonth"
          @change="handleMonthChange"
          @click="openMonthPicker"
        />
      </div>

      <!-- Фильтр по категории -->
      <div class="filter-group">
        <label class="filter-label">Категория:</label>
        <select class="filter-select" v-model="categoryFilter" @change="applyFilters">
          <option value="">Все</option>
          <option v-for="category in uniqueCategories" :key="category" :value="category">
            {{ category }}
          </option>
        </select>
      </div>

      <!-- Кнопка сброса фильтров -->
      <button class="glass-button" @click="resetFilters" title="Сбросить фильтры">
        <svg-icon type="mdi" :path="mdilRefresh"></svg-icon>
      </button>
    </div>

    <!-- Таблица -->
    <div v-if="financeStore.expenses.length > 0" class="table-scroll-container">
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
      <p>📭 Нет расходов за выбранный месяц</p>
    </div>

    <!-- Всего за месяц (ПОСЛЕ таблицы) -->
    <div class="table-total">
      <span>Всего за месяц: <strong>{{ financeStore.totalExpenses.toFixed(2) }} ₽</strong></span>
    </div>

    <!-- Модалки -->
    <ViewExpenseModal
      :is-visible="showViewModal"
      :expense="selectedExpense"
      @close="showViewModal = false; rowSelection = {}"
    />

    <AddExpenseModal
      :is-visible="showAddModal"
      @close="showAddModal = false"
      @success="handleSuccess; showAddModal = false"
    />

    <EditExpenseModal
      :is-visible="showEditModal"
      :expense="selectedExpense"
      @close="showEditModal = false; rowSelection = {}"
      @success="handleSuccess; showEditModal = false"
    />

    <ConfirmModal
      :is-visible="showDeleteModal"
      :message="`Вы уверены, что хотите удалить расход?`"
      title="Подтверждение удаления"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>
