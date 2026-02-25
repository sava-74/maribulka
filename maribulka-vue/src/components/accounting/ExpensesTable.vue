<script setup lang="ts">
import { ref, computed, watch } from 'vue'
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
import { mdiEyeOutline, mdiFilterRemoveOutline, mdiFilterMenuOutline, mdiFileEditOutline, mdiTrashCanOutline, mdiTextBoxPlusOutline } from '@mdi/js'
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

// Props от родителя
const props = defineProps<{
  periodStart: Date
  periodEnd: Date
}>()

const financeStore = useFinanceStore()

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
  await financeStore.fetchExpenses(start, end)
}, { immediate: true })

// Row selection state
const rowSelection = ref<RowSelectionState>({})
const sorting = ref<SortingState>([{ id: 'date', desc: true }])
const columnFilters = ref<ColumnFiltersState>([])

// Видимость панели фильтров
const showFilters = ref(false)

// Модальные окна
const showViewModal = ref(false)
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

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
  const start = formatDateForAPI(props.periodStart)
  const end = formatDateForAPI(props.periodEnd)
  financeStore.fetchExpenses(start, end)
  rowSelection.value = {}
}

function toggleFilters() {
  showFilters.value = !showFilters.value
}

function refreshData() {
  const start = formatDateForAPI(props.periodStart)
  const end = formatDateForAPI(props.periodEnd)
  financeStore.fetchExpenses(start, end)
}
</script>

<template>
  <div class="table-general">
    <!-- Toolbar с кнопками -->
    <div class="table-toolbar">
      <div class="table-actions">
        <button
          class="glass-button"
          @click="handleAdd"
          title="Добавить расход"
        >
          <svg-icon type="mdi" :path="mdiTextBoxPlusOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow"
          @click="handleEdit"
          title="Редактировать расход"
        >
          <svg-icon type="mdi" :path="mdiFileEditOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow"
          @click="handleView"
          title="Посмотреть расход"
        >
          <svg-icon type="mdi" :path="mdiEyeOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSelectedRow"
          @click="handleDelete"
          title="Удалить"
        >
          <svg-icon type="mdi" :path="mdiTrashCanOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          @click="toggleFilters"
          title="Показать/скрыть фильтры"
        >
          <svg-icon type="mdi" :path="mdiFilterMenuOutline"></svg-icon>
        </button>
        <button
          class="glass-button"
          @click="refreshData"
          title="Обновить данные"
        >
          <svg-icon type="mdi" :path="mdiFilterRemoveOutline"></svg-icon>
        </button>
      </div>
    </div>

    <!-- Панель фильтров -->
    <div v-if="showFilters" class="filters-panel">
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
        <svg-icon type="mdi" :path="mdiFilterRemoveOutline"></svg-icon>
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
      <p>📭 Нет расходов за выбранный период</p>
    </div>

    <!-- Всего за период (ПОСЛЕ таблицы) -->
    <div class="table-total">
      <span>Всего за период: <strong>{{ financeStore.totalExpenses.toFixed(2) }} ₽</strong></span>
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
