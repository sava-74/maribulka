<script setup lang="ts">
import { ref, onMounted } from 'vue'
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
import SearchTable from '../ui/searchTable/SearchTable.vue'
import ClientActionsModal from './ClientActionsModal.vue'
import ClientFormModal from './ClientFormModal.vue'
import ViewClientModal from './ViewClientModal.vue'
import PadLoader from '../ui/padLoader/PadLoader.vue'

const auth = useAuthStore()

interface Client {
  id: number
  name: string
  phone: string
  total_bookings: number
  notes: string | null
  created_at: string | null
}

const clients = ref<Client[]>([])
const selectedClient = ref<Client | null>(null)
const showActions = ref(false)
const showView = ref(false)
const showForm = ref(false)
const isCreating = ref(false)
const isEmpty = ref(false)

const isLoading = ref(true)
const loadProgress = ref(0)

async function loadClients() {
  const res = await fetch('/api/clients.php?action=list', { credentials: 'include' })
  const data = await res.json()
  if (data.success) clients.value = data.data
}

onMounted(async () => {
  await loadClients()
  loadProgress.value = 100
  await new Promise(resolve => setTimeout(resolve, 250))
  isLoading.value = false
})

const columns: ColumnDef<Client>[] = [
  { accessorKey: 'name',           header: 'ФИО',       cell: ({ getValue }) => (getValue() as string) ?? '—' },
  { accessorKey: 'phone',          header: 'Телефон',   cell: ({ getValue }) => (getValue() as string) ?? '—' },
  { accessorKey: 'total_bookings', header: 'Записей',   cell: ({ getValue }) => (getValue() as number) ?? 0 },
  { accessorKey: 'notes',          header: 'Примечания', cell: ({ getValue }) => (getValue() as string | null) ?? '' },
]

const sorting = ref<SortingState>([{ id: 'name', desc: false }])
const searchQuery = ref('')

function clientsGlobalFilterFn(row: any, _columnId: string, filterValue: string): boolean {
  const search = filterValue.toLowerCase()
  const c: Client = row.original
  return [c.name, c.phone, c.notes ?? ''].some(v => String(v).toLowerCase().includes(search))
}
clientsGlobalFilterFn.autoRemove = (val: any) => !val

const table = useVueTable({
  get data() { return clients.value },
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
  globalFilterFn: clientsGlobalFilterFn,
})

function onRowClick(client: Client) {
  isEmpty.value = false
  selectedClient.value = client
  showActions.value = true
}

function onOpenEmptyActions() {
  isEmpty.value = true
  selectedClient.value = null
  showActions.value = true
}

function onAdd() {
  showActions.value = false
  selectedClient.value = null
  isCreating.value = true
  showForm.value = true
}

function onView() { showActions.value = false; showView.value = true }
function onEdit() { showActions.value = false; isCreating.value = false; showForm.value = true }

async function onDelete() {
  showActions.value = false
  await loadClients()
}

async function onFormSave() {
  showForm.value = false
  await loadClients()
}
</script>

<template>
  <PadLoader v-if="isLoading" :progress="loadProgress" />
  <div class="padGlass padGlass-work data-table-panel">
    <div class="pad-title">Клиенты</div>

    <div class="data-table-filter">
      <SearchTable
        v-model="searchQuery"
        :count="table.getFilteredRowModel().rows.length"
        placeholder="Поиск по клиентам..."
      />
    </div>

    <div v-if="clients.length > 0" class="data-table-scroll">
      <table class="data-table">
        <thead>
          <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <th
              v-for="header in headerGroup.headers"
              :key="header.id"
              :class="{ sortable: header.column.getCanSort() }"
              @click="header.column.getToggleSortingHandler()?.($event)"
            >
              <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
              <span v-if="header.column.getIsSorted() === 'asc'"> ↑</span>
              <span v-else-if="header.column.getIsSorted() === 'desc'"> ↓</span>
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
        <thead>
          <tr><th>ФИО</th><th>Телефон</th><th>Записей</th><th>Примечания</th></tr>
        </thead>
        <tbody>
          <tr class="row-empty" @click="onOpenEmptyActions">
            <td colspan="4" class="cell-empty">+ Добавить клиента</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <ClientActionsModal
    v-if="showActions"
    :client="selectedClient ?? { id: 0, name: '', phone: '', total_bookings: 0, notes: null, created_at: null }"
    :current-role="auth.userRole ?? 5"
    :is-empty="isEmpty"
    @close="showActions = false"
    @add="onAdd"
    @view="onView"
    @edit="onEdit"
    @delete="onDelete"
  />
  <ViewClientModal v-if="showView && selectedClient" :client="selectedClient" @close="showView = false" />
  <ClientFormModal
    v-if="showForm"
    :client-id="isCreating ? null : selectedClient?.id ?? null"
    @close="showForm = false"
    @save="onFormSave"
  />
</template>
