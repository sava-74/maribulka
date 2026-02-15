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
import ViewShootingTypeModal from './ViewShootingTypeModal.vue'
import AddShootingTypeModal from './AddShootingTypeModal.vue'
import EditShootingTypeModal from './EditShootingTypeModal.vue'
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
  referencesStore.fetchShootingTypes()
})

// Column definitions (БЕЗ checkbox)
const columns: ColumnDef<any>[] = [
  {
    accessorKey: 'id',
    header: 'ID'
  },
  {
    accessorKey: 'name',
    header: 'Название'
  },
  {
    accessorKey: 'base_price',
    header: 'Цена ₽',
    cell: ({ getValue }) => Math.round(parseFloat(getValue() as string))
  },
  {
    accessorKey: 'duration_minutes',
    header: 'Время(мин)',
    cell: ({ getValue }) => getValue() || 30
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
    return referencesStore.shootingTypes
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
const selectedShootingTypes = computed(() => {
  const selected = table.getFilteredSelectedRowModel().rows
  return selected.map(row => row.original)
})

const selectedShootingType = computed(() => {
  return selectedShootingTypes.value.length === 1 ? selectedShootingTypes.value[0] : null
})

const hasSelectedRow = computed(() => {
  return Object.keys(rowSelection.value).length > 0
})

const hasSingleSelection = computed(() => {
  return Object.keys(rowSelection.value).length === 1
})

// Filters
const nameFilter = ref('')
const priceFilter = ref('')
const durationFilter = ref('')

// Применение фильтров к таблице
function applyFilters() {
  const filters: ColumnFiltersState = []

  if (nameFilter.value) {
    filters.push({ id: 'name', value: nameFilter.value })
  }
  if (priceFilter.value) {
    filters.push({ id: 'base_price', value: priceFilter.value })
  }
  if (durationFilter.value) {
    filters.push({ id: 'duration_minutes', value: durationFilter.value })
  }

  columnFilters.value = filters
}

// Сброс фильтров
function resetFilters() {
  nameFilter.value = ''
  priceFilter.value = ''
  durationFilter.value = ''
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
  if (!hasSelectedRow.value || !selectedShootingType.value) return

  const shootingType = selectedShootingType.value
  const hasRelations = await referencesStore.checkShootingTypeRelations(shootingType.id)

  if (hasRelations) {
    alertTitle.value = 'Удаление невозможно'
    alertMessage.value = `Тип съёмки "${shootingType.name}" удалить нельзя. С этим типом связаны другие документы.`
    showAlert.value = true
  } else {
    showDeleteModal.value = true
  }
}

async function confirmDelete() {
  if (!hasSelectedRow.value || !selectedShootingType.value) return

  const result = await referencesStore.deleteShootingType(selectedShootingType.value.id)

  if (result.success) {
    rowSelection.value = {}
    showDeleteModal.value = false
  }
}

function toggleFilters() {
  showFilters.value = !showFilters.value
}

function refreshData() {
  referencesStore.fetchShootingTypes()
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
          title="Добавить тип съёмки"
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
      <!-- Фильтр по названию -->
      <div class="filter-group">
        <label class="filter-label">Название:</label>
        <input
          type="text"
          class="filter-select"
          v-model="nameFilter"
          @input="applyFilters"
          placeholder="Поиск по названию"
        />
      </div>

      <!-- Фильтр по цене -->
      <div class="filter-group">
        <label class="filter-label">Цена:</label>
        <input
          type="number"
          class="filter-select"
          v-model="priceFilter"
          @input="applyFilters"
          placeholder="Цена"
        />
      </div>

      <!-- Фильтр по времени съёмки -->
      <div class="filter-group">
        <label class="filter-label">Время съёмки (мин):</label>
        <input
          type="number"
          class="filter-select"
          v-model="durationFilter"
          @input="applyFilters"
          placeholder="Время"
        />
      </div>

      <!-- Кнопка сброса фильтров -->
      <button class="glass-button" @click="resetFilters" title="Сбросить фильтры">
        <svg-icon type="mdi" :path="mdilRefresh"></svg-icon>
      </button>
    </div>

    <!-- Таблица -->
    <div v-if="referencesStore.shootingTypes.length > 0" class="table-containerTab">
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
            >
              <FlexRender
                :render="cell.column.columnDef.cell"
                :props="cell.getContext()"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="empty-state">
      <p>📭 Нет типов съёмок в базе</p>
    </div>

    <!-- Модалки -->
    <AddShootingTypeModal
      :is-visible="showAddModal"
      @close="showAddModal = false"
    />

    <ViewShootingTypeModal
      :is-visible="showViewModal"
      :shooting-type="selectedShootingType"
      @close="showViewModal = false; rowSelection = {}"
    />

    <EditShootingTypeModal
      :is-visible="showEditModal"
      :shooting-type="selectedShootingType"
      @close="showEditModal = false; rowSelection = {}"
    />

    <ConfirmModal
      :is-visible="showDeleteModal"
      :message="`Вы хотите удалить тип съёмки &quot;${selectedShootingType?.name}&quot;?`"
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
