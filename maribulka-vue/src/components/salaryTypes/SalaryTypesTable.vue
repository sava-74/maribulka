<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  type ColumnDef,
  type SortingState,
  FlexRender
} from '@tanstack/vue-table'
import { useAuthStore } from '../../stores/auth'
import { useNavigationStore } from '../../stores/navigation'
import SearchTable from '../ui/searchTable/SearchTable.vue'
import SalaryTypeActionsModal from './SalaryTypeActionsModal.vue'
import SalaryTypeFormModal from './SalaryTypeFormModal.vue'
import ViewSalaryTypeModal from './ViewSalaryTypeModal.vue'
import DeleteSalaryTypeModal from './DeleteSalaryTypeModal.vue'
import PadLoader from '../ui/padLoader/PadLoader.vue'

const auth = useAuthStore()
const navStore = useNavigationStore()

interface SalaryType {
  id: number
  title: string
  monthly_salary: number
  salary_value: number
  percentage_of_the_order: number
  the_percentage_value: number
  interest_dividends: number
  value_dividend_percentages: number
  fixed_order: number
  fixed_value_order: number
}

const salaryTypes = ref<SalaryType[]>([])
const selectedSalaryType = ref<SalaryType | null>(null)
const showActions = ref(false)
const showView = ref(false)
const showForm = ref(false)
const showDelete = ref(false)
const isCreating = ref(false)

const isLoading = ref(true)
const loadProgress = ref(0)

async function loadSalaryTypes() {
  const res = await fetch('/api/salary-types.php?action=list', { credentials: 'include' })
  const data = await res.json()
  if (data.success) {
    salaryTypes.value = data.data
  }
}

onMounted(async () => {
  await loadSalaryTypes()
  loadProgress.value = 100
  await new Promise(resolve => setTimeout(resolve, 250))
  isLoading.value = false
})

const columns: ColumnDef<SalaryType>[] = [
  { accessorKey: 'title', header: 'Название', cell: ({ getValue }) => (getValue() as string) ?? '—' },
  {
    accessorKey: 'monthly_salary',
    header: 'Оклад',
    cell: ({ getValue, row }) => {
      const val = getValue() as number
      return val ? `${row.original.salary_value} руб` : '—'
    }
  },
  {
    accessorKey: 'percentage_of_the_order',
    header: '% от заказа',
    cell: ({ getValue, row }) => {
      const val = getValue() as number
      return val ? `${row.original.the_percentage_value}%` : '—'
    }
  },
  {
    accessorKey: 'interest_dividends',
    header: 'Дивиденды',
    cell: ({ getValue, row }) => {
      const val = getValue() as number
      return val ? `${row.original.value_dividend_percentages}%` : '—'
    }
  },
  {
    accessorKey: 'fixed_order',
    header: 'Фиксированное',
    cell: ({ getValue, row }) => {
      const val = getValue() as number
      return val ? `${row.original.fixed_value_order} руб` : '—'
    }
  },
]

const sorting = ref<SortingState>([{ id: 'title', desc: false }])
const searchQuery = ref('')

function salaryTypesGlobalFilterFn(row: any, _columnId: string, filterValue: string): boolean {
  const search = filterValue.toLowerCase()
  const original = row.original as SalaryType
  return original.title.toLowerCase().includes(search)
}

const table = useVueTable({
  data: salaryTypes,
  columns,
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  state: {
    get sorting() { return sorting.value },
    get globalFilter() { return searchQuery.value },
  },
  onSortingChange: updaterOrValue => {
    sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue
  },
  onGlobalFilterChange: updaterOrValue => {
    searchQuery.value = typeof updaterOrValue === 'function' ? updaterOrValue(searchQuery.value) : updaterOrValue
  },
  globalFilterFn: salaryTypesGlobalFilterFn,
})

const rowSelection = ref<Record<number, boolean>>({})

const hasSelectedRow = computed(() => Object.keys(rowSelection.value).length > 0)
const hasSingleSelection = computed(() => Object.keys(rowSelection.value).length === 1)

const selectedSalaryTypeId = computed(() => {
  const keys = Object.keys(rowSelection.value)
  if (keys.length !== 1) return null
  const id = parseInt(keys[0])
  return salaryTypes.value.find(s => s.id === id) || null
})

function handleAdd() {
  isCreating.value = true
  selectedSalaryType.value = null
  showForm.value = true
}

function handleActions() {
  if (!hasSingleSelection.value) return
  selectedSalaryType.value = selectedSalaryTypeId.value
  showActions.value = true
}

function handleView() {
  if (!hasSingleSelection.value) return
  selectedSalaryType.value = selectedSalaryTypeId.value
  showView.value = true
}

function handleEdit() {
  if (!hasSingleSelection.value) return
  selectedSalaryType.value = selectedSalaryTypeId.value
  isCreating.value = false
  showForm.value = true
}

function handleDelete() {
  if (!hasSingleSelection.value) return
  selectedSalaryType.value = selectedSalaryTypeId.value
  showDelete.value = true
}

function handleSuccess() {
  loadSalaryTypes()
  rowSelection.value = {}
}

function handleClose() {
  navStore.navigateTo('home')
}
</script>

<template>
  <div class="app-container">
    <div class="top-bar-work">
      <div class="top-bar-left">
        <div class="back-button" @click="handleClose">
          <svg-icon type="mdi" :path="'M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z'" class="back-icon" />
        </div>
        <h1 class="top-bar-title">Типы зарплат</h1>
      </div>
    </div>

    <SearchTable
      v-model:search-query="searchQuery"
      :placeholder="'Поиск по названию...'"
      :has-selected-row="hasSelectedRow"
      @add="handleAdd"
      @actions="handleActions"
      @view="handleView"
      @edit="handleEdit"
      @delete="handleDelete"
      :can-add="auth.can('salary_types', 'create')"
      :can-actions="hasSingleSelection && auth.can('salary_types', 'edit')"
      :can-view="hasSingleSelection && auth.can('salary_types', 'view')"
      :can-edit="hasSingleSelection && auth.can('salary_types', 'edit')"
      :can-delete="hasSingleSelection && auth.can('salary_types', 'delete')"
    />

    <div v-if="isLoading" class="progress-container">
      <div class="progress-bar"></div>
      <PadLoader />
    </div>

    <div v-else class="padGlass padGlass-work data-table-panel">
      <table class="data-table">
        <thead>
          <tr>
            <th class="row-number">#</th>
            <template v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
              <template v-for="header in headerGroup.headers" :key="header.id">
                <th
                  v-if="!header.isPlaceholder"
                  :colspan="header.colSpan"
                  :style="{ width: header.getSize() + 'px' }"
                  @click="header.column.toggleSorting()"
                  class="sortable"
                >
                  <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                  <span class="sort-indicator" v-if="header.column.getIsSorted()">
                    {{ header.column.getIsSorted() === 'desc' ? '↓' : '↑' }}
                  </span>
                </th>
              </template>
            </template>
          </tr>
        </thead>
        <tbody>
          <template v-if="table.getRowModel().rows.length">
            <tr
              v-for="row in table.getRowModel().rows"
              :key="row.id"
              :class="{ selected: row.getIsSelected() }"
              @click="row.toggleSelected()"
            >
              <td class="row-number">{{ row.index + 1 }}</td>
              <template v-for="cell in row.getVisibleCells()" :key="cell.id">
                <td>
                  <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                </td>
              </template>
            </tr>
          </template>
          <template v-else>
            <tr>
              <td :colspan="columns.length + 1" class="empty-message">Нет записей</td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <SalaryTypeActionsModal
      :is-visible="showActions"
      :salary-type="selectedSalaryType"
      @close="showActions = false"
      @view="() => { showActions = false; showView = true; }"
      @edit="() => { showActions = false; isCreating = false; showForm = true; }"
      @delete="() => { showActions = false; showDelete = true; }"
    />

    <SalaryTypeFormModal
      :is-visible="showForm"
      :is-creating="isCreating"
      :salary-type="selectedSalaryType"
      @close="showForm = false"
      @success="handleSuccess"
    />

    <ViewSalaryTypeModal
      :is-visible="showView"
      :salary-type="selectedSalaryType"
      @close="showView = false"
      @edit="() => { showView = false; isCreating = false; showForm = true; }"
    />

    <DeleteSalaryTypeModal
      :is-visible="showDelete"
      :salary-type="selectedSalaryType"
      @close="showDelete = false"
      @success="handleSuccess"
    />
  </div>
</template>

<style scoped>
.app-container {
  min-height: 100vh;
  padding: 20px;
}

.top-bar-work {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.top-bar-left {
  display: flex;
  align-items: center;
  gap: 15px;
}

.back-button {
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  transition: background 0.2s;
}

.back-button:hover {
  background: rgba(255, 255, 255, 0.1);
}

.back-icon {
  width: 24px;
  height: 24px;
}

.top-bar-title {
  font-size: 24px;
  font-weight: 600;
  margin: 0;
}

.progress-container {
  position: relative;
  height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.progress-bar {
  position: absolute;
  top: 0;
  left: 0;
  height: 3px;
  background: linear-gradient(90deg, #00d9ff, #00ff88);
  transition: width 0.3s;
}

.empty-message {
  text-align: center;
  padding: 40px;
  color: rgba(255, 255, 255, 0.5);
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sort-indicator {
  margin-left: 5px;
  opacity: 0.7;
}

tr.selected {
  background: rgba(0, 217, 255, 0.1);
}
</style>
