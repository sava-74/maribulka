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
import UserActionsModal from './UserActionsModal.vue'
import UserFormModal from './UserFormModal.vue'
import FireUserModal from './FireUserModal.vue'
import UserPermissionsModal from './UserPermissionsModal.vue'
import ViewUserModal from './ViewUserModal.vue'
import PadLoader from '../ui/padLoader/PadLoader.vue'

const auth = useAuthStore()

interface User {
  id: number
  full_name: string | null
  login: string
  role: number
  permission_name: string | null
  id_profession: number | null
  profession_title: string | null
  is_photographer: boolean
  is_hairdresser: boolean
  is_admin_role: boolean
  id_salary_type: number | null
  salary_type_title: string | null
  hired_at: string | null
  fired_at: string | null
  notes: string | null
  region: string | null
  city: string | null
  street: string | null
  house_building: string | null
  flat: number | null
  phone_user: string | null
  email_user: string | null
  date_of_birth: string | null
}

const users = ref<User[]>([])
const selectedUser = ref<User | null>(null)
const showActions = ref(false)
const showView = ref(false)
const showForm = ref(false)
const showFire = ref(false)
const showPermissions = ref(false)
const isCreating = ref(false)
const isEmpty = ref(false)

const isLoading = ref(true)
const loadProgress = ref(0)

async function loadUsers() {
  const res = await fetch('/api/users.php?action=list', { credentials: 'include' })
  const data = await res.json()
  if (data.success) users.value = data.data
}

onMounted(async () => {
  await loadUsers()
  loadProgress.value = 100
  await new Promise(resolve => setTimeout(resolve, 250))
  isLoading.value = false
})

function formatDate(dateString: string | null): string {
  if (!dateString) return ''
  const datePart = dateString.split('T')[0]?.split(' ')[0]
  if (!datePart) return ''
  const parts = datePart.split('-')
  return `${parts[2] || ''}.${parts[1] || ''}.${(parts[0] || '').slice(-2)}`
}

const columns: ColumnDef<User>[] = [
  { accessorKey: 'full_name',         header: 'ФИО',          cell: ({ getValue }) => (getValue() as string | null) ?? '—' },
  { accessorKey: 'login',             header: 'Логин' },
  { accessorKey: 'permission_name',   header: 'Права',        cell: ({ row }) => row.original.permission_name ?? row.original.role },
  { accessorKey: 'is_admin_role',     header: 'Адм',          cell: ({ getValue }) => (getValue() as boolean) ? '✓' : '' },
  { accessorKey: 'is_photographer',   header: 'Фотограф',     cell: ({ getValue }) => (getValue() as boolean) ? '✓' : '' },
  { accessorKey: 'is_hairdresser',    header: 'Парикмахер',   cell: ({ getValue }) => (getValue() as boolean) ? '✓' : '' },
  { accessorKey: 'salary_type_title', header: 'Тип зарплаты', cell: ({ getValue }) => (getValue() as string | null) ?? '' },
  { accessorKey: 'hired_at',          header: 'Принят',       cell: ({ getValue }) => formatDate(getValue() as string | null) },
  { accessorKey: 'fired_at',          header: 'Уволен',       cell: ({ getValue }) => formatDate(getValue() as string | null) },
  { accessorKey: 'notes',             header: 'Примечания',   cell: ({ getValue }) => (getValue() as string | null) ?? '' },
]

const sorting = ref<SortingState>([{ id: 'full_name', desc: false }])
const searchQuery = ref('')

function usersGlobalFilterFn(row: any, _columnId: string, filterValue: string): boolean {
  const search = filterValue.toLowerCase()
  const u: User = row.original
  return [
    u.full_name ?? '',
    u.login,
    u.permission_name ?? '',
    u.salary_type_title ?? '',
    u.notes ?? '',
    formatDate(u.hired_at),
    formatDate(u.fired_at),
  ].some(v => String(v).toLowerCase().includes(search))
}
usersGlobalFilterFn.autoRemove = (val: any) => !val

const table = useVueTable({
  get data() { return users.value },
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
  globalFilterFn: usersGlobalFilterFn,
})

function onRowClick(user: User) {
  isEmpty.value = false
  selectedUser.value = user
  showActions.value = true
}

function onOpenEmptyActions() {
  isEmpty.value = true
  selectedUser.value = null
  showActions.value = true
}

function onAdd() {
  showActions.value = false
  selectedUser.value = null
  isCreating.value = true
  showForm.value = true
}

function onView() { showActions.value = false; showView.value = true }
function onEdit() { showActions.value = false; isCreating.value = false; showForm.value = true }
function onFire() { showActions.value = false; showFire.value = true }
function onPermissions() { showActions.value = false; showPermissions.value = true }

async function onFormSave() {
  showForm.value = false
  await loadUsers()
}

async function onFireConfirm() {
  if (!selectedUser.value) return
  await fetch('/api/users.php?action=fire', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: selectedUser.value.id }),
  })
  showFire.value = false
  await loadUsers()
}
</script>

<template>
  <PadLoader v-if="isLoading" :progress="loadProgress" />
  <div class="padGlass padGlass-work data-table-panel">
    <div class="pad-title">Пользователи</div>

    <div class="data-table-filter">
      <SearchTable
        v-model="searchQuery"
        :count="table.getFilteredRowModel().rows.length"
        placeholder="Поиск по пользователям..."
      />
    </div>

    <div v-if="users.length > 0" class="data-table-scroll">
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
            v-for="row in table.getRowModel().rows"
            :key="row.id"
            :class="{ 'row-cancelled': row.original.fired_at }"
            @click="onRowClick(row.original)"
          >
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
          <tr>
            <th>ФИО</th><th>Логин</th><th>Тип</th><th>Адм</th>
            <th>Фотограф</th><th>Парикмахер</th><th>Тип зарплаты</th>
            <th>Принят</th><th>Уволен</th><th>Примечания</th>
          </tr>
        </thead>
        <tbody>
          <tr class="row-empty" @click="onOpenEmptyActions">
            <td colspan="10" class="cell-empty">+ Добавить пользователя</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <UserActionsModal
    v-if="showActions"
    :user="selectedUser ?? { id: 0, full_name: null, login: '', role: 0 }"
    :current-role="auth.userRole ?? 5"
    :is-empty="isEmpty"
    @close="showActions = false"
    @add="onAdd"
    @view="onView"
    @edit="onEdit"
    @fire="onFire"
    @permissions="onPermissions"
  />
  <ViewUserModal v-if="showView && selectedUser" :user="selectedUser" @close="showView = false" />
  <UserFormModal
    v-if="showForm"
    :user-id="isCreating ? null : selectedUser?.id ?? null"
    @close="showForm = false"
    @save="onFormSave"
  />
  <FireUserModal
    v-if="showFire && selectedUser"
    :user="selectedUser"
    @close="showFire = false"
    @confirm="onFireConfirm"
  />
  <UserPermissionsModal
    v-if="showPermissions && selectedUser"
    :user="selectedUser"
    @close="showPermissions = false"
  />
</template>
