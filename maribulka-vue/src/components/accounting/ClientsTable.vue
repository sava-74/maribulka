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
import { mdilPlus, mdilDelete, mdilMagnify, mdilRefresh, mdilEye } from '@mdi/light-js'
import { mdiFileEditOutline } from '@mdi/js'
import { useReferencesStore } from '../../stores/references'
import ViewClientModal from './ViewClientModal.vue'
import AddClientModal from './AddClientModal.vue'
import EditClientModal from './EditClientModal.vue'
import ConfirmModal from '../ConfirmModal.vue'
import AlertModal from '../AlertModal.vue'
import '../../assets/tables.css'
import '../../assets/buttons.css'
import '../../assets/layout.css'
import '../../assets/modal.css'
import '../../assets/responsive.css'

const referencesStore = useReferencesStore()

// Row selection state
const rowSelection = ref<RowSelectionState>({})
const sorting = ref<SortingState>([{ id: 'id', desc: false }])
const columnFilters = ref<ColumnFiltersState>([])

// Видимость панели фильтров
const showFilters = ref(false)

// Модальные окна
const showAddModal = ref(false)
const showEditModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

onMounted(() => {
  referencesStore.fetchClients()
})

// Column definitions (БЕЗ checkbox)
const columns: ColumnDef<any>[] = [
  {
    accessorKey: 'id',
    header: 'ID'
  },
  {
    accessorKey: 'name',
    header: 'ФИО'
  },
  {
    accessorKey: 'phone',
    header: 'Телефон',
    cell: ({ getValue }) => {
      const phone = getValue() as string
      return `<a href="tel:${phone}">${phone}</a>`
    }
  },
  {
    accessorKey: 'notes',
    header: 'Примечание',
    cell: ({ getValue }) => getValue() || '—'
  }
]

// Create table instance
const table = useVueTable({
  get data() {
    return referencesStore.clients
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

// Computed: выбранные строки
const selectedClients = computed(() => {
  const selected = table.getFilteredSelectedRowModel().rows
  return selected.map(row => row.original)
})

const selectedClient = computed(() => {
  return selectedClients.value.length === 1 ? selectedClients.value[0] : null
})

const hasSelectedRow = computed(() => {
  return Object.keys(rowSelection.value).length > 0
})

const hasSingleSelection = computed(() => {
  return Object.keys(rowSelection.value).length === 1
})

// Filters
const nameFilter = ref('')
const phoneFilter = ref('')

// Применение фильтров к таблице
function applyFilters() {
  const filters: ColumnFiltersState = []

  if (nameFilter.value) {
    filters.push({ id: 'name', value: nameFilter.value })
  }
  if (phoneFilter.value) {
    filters.push({ id: 'phone', value: phoneFilter.value })
  }

  columnFilters.value = filters
}

// Сброс фильтров
function resetFilters() {
  nameFilter.value = ''
  phoneFilter.value = ''
  columnFilters.value = []
}

// Actions
function handleAdd() {
  showAddModal.value = true
}

function handleView() {
  if (!hasSingleSelection.value) return
  showViewModal.value = true
}

function handleEdit() {
  if (!hasSingleSelection.value) return
  showEditModal.value = true
}

async function handleDelete() {
  if (!hasSelectedRow.value || !selectedClient.value) return

  const client = selectedClient.value
  const hasRelations = await referencesStore.checkClientRelations(client.id)

  console.log('ID клиента:', client.id, 'Ответ API:', hasRelations, 'Тип:', typeof hasRelations)

  if (hasRelations) {
    alertTitle.value = 'Удаление невозможно'
    alertMessage.value = `Клиента "${client.name}" удалить нельзя. С этим клиентом связаны другие документы.`
    showAlert.value = true
  } else {
    showDeleteModal.value = true
  }
}

async function confirmDelete() {
  if (!hasSelectedRow.value || !selectedClient.value) return

  const result = await referencesStore.deleteClient(selectedClient.value.id)

  if (result.success) {
    rowSelection.value = {}
    showDeleteModal.value = false
  }
}

function toggleFilters() {
  showFilters.value = !showFilters.value
}

function refreshData() {
  referencesStore.fetchClients()
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
          title="Добавить клиента"
        >
          <svg-icon type="mdi" :path="mdilPlus"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSingleSelection"
          @click="handleView"
          title="Посмотреть"
        >
          <svg-icon type="mdi" :path="mdilEye"></svg-icon>
        </button>
        <button
          class="glass-button"
          :disabled="!hasSingleSelection"
          @click="handleEdit"
          title="Редактировать"
        >
          <svg-icon type="mdi" :path="mdiFileEditOutline"></svg-icon>
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
      <!-- Фильтр по ФИО -->
      <div class="filter-group">
        <label class="filter-label">ФИО:</label>
        <input
          type="text"
          class="filter-select"
          v-model="nameFilter"
          @input="applyFilters"
          placeholder="Поиск по имени"
        />
      </div>

      <!-- Фильтр по телефону -->
      <div class="filter-group">
        <label class="filter-label">Телефон:</label>
        <input
          type="text"
          class="filter-select"
          v-model="phoneFilter"
          @input="applyFilters"
          placeholder="Поиск по телефону"
        />
      </div>

      <!-- Кнопка сброса фильтров -->
      <button class="glass-button" @click="resetFilters" title="Сбросить фильтры">
        <svg-icon type="mdi" :path="mdilRefresh"></svg-icon>
      </button>
    </div>

    <!-- Таблица -->
    <div v-if="referencesStore.clients.length > 0" class="table-scroll-container">
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
      <p>📭 Нет клиентов в базе</p>
    </div>

    <!-- Модалки -->
    <AddClientModal
      :is-visible="showAddModal"
      @close="showAddModal = false"
    />

    <ViewClientModal
      :is-visible="showViewModal"
      :client="selectedClient"
      @close="showViewModal = false; rowSelection = {}"
    />

    <EditClientModal
      :is-visible="showEditModal"
      :client="selectedClient"
      @close="showEditModal = false; rowSelection = {}"
    />

    <ConfirmModal
      :is-visible="showDeleteModal"
      :message="`Вы хотите удалить клиента &quot;${selectedClient?.name}&quot;?`"
      title="Подтверждение удаления"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />

    <AlertModal
      :is-visible="showAlert"
      :message="alertMessage"
      :title="alertTitle"
      @close="showAlert = false"
    />
  </div>
</template>
