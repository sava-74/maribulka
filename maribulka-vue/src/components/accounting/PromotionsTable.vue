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
import ViewPromotionModal from './ViewPromotionModal.vue'
import AddPromotionModal from './AddPromotionModal.vue'
import EditPromotionModal from './EditPromotionModal.vue'
import PromotionsTimeline from './PromotionsTimeline.vue'
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

// Акция для просмотра из Timeline
const timelinePromotion = ref<any>(null)

onMounted(() => {
  referencesStore.fetchPromotions()
})

// Форматирование даты
function formatDate(dateStr: string | null) {
  if (!dateStr) return '∞'
  const [year, month, day] = dateStr.split('-')
  return `${day}.${month}.${year!.slice(-2)}`
}

// Column definitions (БЕЗ checkbox)
const columns: ColumnDef<any>[] = [
  {
    accessorKey: 'id',
    header: '№'
  },
  {
    accessorKey: 'name',
    header: 'Название'
  },
  {
    accessorKey: 'discount_percent',
    header: '%',
    cell: ({ getValue }) => Math.round(parseFloat(getValue() as string)) + '%'
  },
  {
    accessorKey: 'start_date',
    header: 'Начало',
    cell: ({ getValue }) => formatDate(getValue() as string)
  },
  {
    accessorKey: 'end_date',
    header: 'Конец',
    cell: ({ getValue }) => formatDate(getValue() as string)
  }
]

// Create table instance
const table = useVueTable({
  get data() {
    return referencesStore.promotions
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
const selectedPromotions = computed(() => {
  const selected = table.getFilteredSelectedRowModel().rows
  return selected.map(row => row.original)
})

const selectedPromotion = computed(() => {
  return selectedPromotions.value.length === 1 ? selectedPromotions.value[0] : null
})

const hasSelectedRow = computed(() => {
  return Object.keys(rowSelection.value).length > 0
})

const hasSingleSelection = computed(() => {
  return Object.keys(rowSelection.value).length === 1
})

// Filters
const nameFilter = ref('')
const discountFilter = ref('')

// Применение фильтров к таблице
function applyFilters() {
  const filters: ColumnFiltersState = []

  if (nameFilter.value) {
    filters.push({ id: 'name', value: nameFilter.value })
  }
  if (discountFilter.value) {
    filters.push({ id: 'discount_percent', value: discountFilter.value })
  }

  columnFilters.value = filters
}

// Сброс фильтров
function resetFilters() {
  nameFilter.value = ''
  discountFilter.value = ''
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
  if (!hasSelectedRow.value || !selectedPromotion.value) return

  const promotion = selectedPromotion.value
  const hasRelations = await referencesStore.checkPromotionRelations(promotion.id)

  if (hasRelations) {
    alertTitle.value = 'Удаление невозможно'
    alertMessage.value = `Акцию "${promotion.name}" удалить нельзя. С этой акцией связаны другие документы.`
    showAlert.value = true
  } else {
    showDeleteModal.value = true
  }
}

async function confirmDelete() {
  if (!hasSelectedRow.value || !selectedPromotion.value) return

  const result = await referencesStore.deletePromotion(selectedPromotion.value.id)

  if (result.success) {
    rowSelection.value = {}
    showDeleteModal.value = false
  }
}

function toggleFilters() {
  showFilters.value = !showFilters.value
}

function refreshData() {
  referencesStore.fetchPromotions()
}

// Обработка клика по блоку в Timeline
function handleTimelineClick(promotion: any) {
  timelinePromotion.value = promotion
  showViewModal.value = true
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
          title="Добавить акцию"
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

      <!-- Фильтр по скидке -->
      <div class="filter-group">
        <label class="filter-label">Скидка %:</label>
        <input
          type="number"
          class="filter-select"
          v-model="discountFilter"
          @input="applyFilters"
          placeholder="Скидка"
        />
      </div>

      <!-- Кнопка сброса фильтров -->
      <button class="glass-button" @click="resetFilters" title="Сбросить фильтры">
        <svg-icon type="mdi" :path="mdilRefresh"></svg-icon>
      </button>
    </div>

    <!-- Таблица -->
    <div v-if="referencesStore.promotions.length > 0" class="table-containerTab">
      <table class="accounting-table promotions-table">
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
      <p>📭 Нет акций в базе</p>
    </div>

    <!-- Timeline визуализация акций -->
    <PromotionsTimeline
      :promotions="referencesStore.promotions"
      @view-promotion="handleTimelineClick"
    />

    <!-- Модалки -->
    <AddPromotionModal
      :is-visible="showAddModal"
      @close="showAddModal = false"
    />

    <ViewPromotionModal
      :is-visible="showViewModal"
      :promotion="selectedPromotion || timelinePromotion"
      @close="showViewModal = false; rowSelection = {}; timelinePromotion = null"
    />

    <EditPromotionModal
      :is-visible="showEditModal"
      :promotion="selectedPromotion"
      @close="showEditModal = false; rowSelection = {}"
    />

    <ConfirmModal
      :is-visible="showDeleteModal"
      :message="`Вы хотите удалить акцию &quot;${selectedPromotion?.name}&quot;?`"
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
