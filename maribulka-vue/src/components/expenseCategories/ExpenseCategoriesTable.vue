<script setup lang="ts">
import { ref, onMounted } from 'vue'
import {
  useVueTable, getCoreRowModel, getSortedRowModel, getFilteredRowModel,
  type ColumnDef, type SortingState, FlexRender
} from '@tanstack/vue-table'
import { useAuthStore } from '../../stores/auth'
import SearchTable from '../ui/searchTable/SearchTable.vue'
import ExpenseCategoryActionsModal from './ExpenseCategoryActionsModal.vue'
import ExpenseCategoryFormModal from './ExpenseCategoryFormModal.vue'
import ViewExpenseCategoryModal from './ViewExpenseCategoryModal.vue'
import PadLoader from '../ui/padLoader/PadLoader.vue'

const auth = useAuthStore()

interface ExpenseCategory {
  id: number
  name: string
  is_active: number
}

const items = ref<ExpenseCategory[]>([])
const selected = ref<ExpenseCategory | null>(null)
const showActions = ref(false)
const showView = ref(false)
const showForm = ref(false)
const isCreating = ref(false)
const isEmpty = ref(false)
const isLoading = ref(true)
const loadProgress = ref(0)

async function loadItems() {
  const res = await fetch('/api/expense-categories.php?action=list', { credentials: 'include' })
  const data = await res.json()
  if (data.success) items.value = data.data
}

onMounted(async () => {
  await loadItems()
  loadProgress.value = 100
  await new Promise(resolve => setTimeout(resolve, 250))
  isLoading.value = false
})

const columns: ColumnDef<ExpenseCategory>[] = [
  { accessorKey: 'name',      header: 'Название', cell: ({ getValue }) => getValue() as string },
  { accessorKey: 'is_active', header: 'Активна',  cell: ({ getValue }) => (getValue() as number) ? 'Да' : 'Нет' },
]

const sorting = ref<SortingState>([{ id: 'name', desc: false }])
const searchQuery = ref('')

function filterFn(row: any, _: string, filterValue: string): boolean {
  return (row.original as ExpenseCategory).name.toLowerCase().includes(filterValue.toLowerCase())
}
filterFn.autoRemove = (val: any) => !val

const table = useVueTable({
  get data() { return items.value },
  columns,
  state: { get sorting() { return sorting.value }, get globalFilter() { return searchQuery.value } },
  onSortingChange: u => { sorting.value = typeof u === 'function' ? u(sorting.value) : u },
  onGlobalFilterChange: u => { searchQuery.value = typeof u === 'function' ? u(searchQuery.value) : u },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  globalFilterFn: filterFn,
})

function onRowClick(item: ExpenseCategory) { isEmpty.value = false; selected.value = item; showActions.value = true }
function onOpenEmptyActions() { isEmpty.value = true; selected.value = null; showActions.value = true }
function onAdd() { showActions.value = false; selected.value = null; isCreating.value = true; showForm.value = true }
function onView() { showActions.value = false; showView.value = true }
function onEdit() { showActions.value = false; isCreating.value = false; showForm.value = true }
async function onDelete() { showActions.value = false; await loadItems() }
async function onFormSave() { showForm.value = false; await loadItems() }
</script>

<template>
  <PadLoader v-if="isLoading" :progress="loadProgress" />
  <div class="padGlass padGlass-work data-table-panel">
    <div class="pad-title">Категории расходов</div>
    <div class="data-table-filter">
      <SearchTable v-model="searchQuery" :count="table.getFilteredRowModel().rows.length" placeholder="Поиск по категориям..." />
    </div>
    <div v-if="items.length > 0" class="data-table-scroll">
      <table class="data-table">
        <thead>
          <tr v-for="hg in table.getHeaderGroups()" :key="hg.id">
            <th v-for="h in hg.headers" :key="h.id" :class="{ sortable: h.column.getCanSort() }" @click="h.column.getToggleSortingHandler()?.($event)">
              <FlexRender v-if="!h.isPlaceholder" :render="h.column.columnDef.header" :props="h.getContext()" />
              <span v-if="h.column.getIsSorted() === 'asc'"> ↑</span>
              <span v-else-if="h.column.getIsSorted() === 'desc'"> ↓</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in table.getRowModel().rows" :key="row.id" @click="onRowClick(row.original)">
            <td v-for="cell in row.getVisibleCells()" :key="cell.id">
              <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else class="data-table-scroll">
      <table class="data-table">
        <thead><tr><th>Название</th><th>Активна</th></tr></thead>
        <tbody>
          <tr class="row-empty" @click="onOpenEmptyActions">
            <td colspan="2" class="cell-empty">+ Добавить категорию</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <ExpenseCategoryActionsModal
    v-if="showActions"
    :category="selected ?? { id: 0, name: '', is_active: 1 }"
    :current-role="auth.userRole ?? 5"
    :is-empty="isEmpty"
    @close="showActions = false"
    @add="onAdd"
    @view="onView"
    @edit="onEdit"
    @delete="onDelete"
  />
  <ViewExpenseCategoryModal v-if="showView && selected" :category="selected" @close="showView = false" />
  <ExpenseCategoryFormModal
    v-if="showForm"
    :category-id="isCreating ? null : selected?.id ?? null"
    @close="showForm = false"
    @save="onFormSave"
  />
</template>
