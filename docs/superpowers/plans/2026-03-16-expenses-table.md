# Таблица Расход — план реализации

> **For agentic workers:** Use superpowers:subagent-driven-development to implement this plan.

**Goal:** Создать таблицу расходов по аналогии с таблицей приходов

**Architecture:** Клон структуры income/ с заменой данных на expenses. Те же паттерны: selectedIndex, ActionsModal, Add/View/Refund модалки.

**Tech Stack:** Vue 3, TypeScript, TanStack Table, Pinia (finance store + references store)

---

### Задача 1: Навигация — navigation.ts + LaunchPad.vue + App.vue

**Файлы:**
- Изменить: `maribulka-vue/src/stores/navigation.ts`
- Изменить: `maribulka-vue/src/components/launchpad/LaunchPad.vue`
- Изменить: `maribulka-vue/src/App.vue`

- [ ] **Шаг 1: Добавить `'expenses'` в PageType**

Файл: `maribulka-vue/src/stores/navigation.ts`

Найти строку с `export type PageType` и добавить `'expenses'` в union.

- [ ] **Шаг 2: Добавить `openExpenses()` в LaunchPad.vue**

После функции `openIncome()` добавить:

```typescript
function openExpenses() {
  navStore.navigateTo('expenses')
  close()
}
```

- [ ] **Шаг 3: Привязать кнопку "Расходы" к openExpenses в LaunchPad.vue**

Кнопка уже существует (строка ~120), изменить только `@click`:

```html
<!-- Было: -->
<button class="btnGlass bigIcon" @click="onRipple($event)">
<!-- Стало: -->
<button class="btnGlass bigIcon" @click="onRipple($event); openExpenses()">
```

Это кнопка с `mdiTrendingDown` и подписью "Расходы".

- [ ] **Шаг 4: Подключить ExpensesTable в App.vue**

Добавить импорт рядом с `IncomeTable`:
```typescript
import ExpensesTable from './components/finance/expenses/ExpensesTable.vue'
```

Добавить рендер рядом с `<IncomeTable ...>`:
```html
<ExpensesTable v-if="navStore.currentPage === 'expenses'" />
```

---

### Задача 2: ExpensesActionsModal.vue

**Файлы:**
- Создать: `maribulka-vue/src/components/finance/expenses/ExpensesActionsModal.vue`
- Эталон: `maribulka-vue/src/components/finance/income/IncomeActionsModal.vue`

- [ ] **Шаг 1: Создать `ExpensesActionsModal.vue`**

```vue
<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCashMinus, mdiEyeOutline, mdiUndoVariant, mdiTrashCanOutline } from '@mdi/js'

const props = defineProps<{
  expense: any | null
}>()

const emit = defineEmits(['close', 'add', 'view', 'refund', 'delete'])
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ expense?.category_name ?? 'Расход' }}</div>
        <div style="color: var(--text-secondary); font-size: 0.85em; margin-top: -8px; margin-bottom: 4px;">
          {{ expense?.description ?? '—' }}
        </div>

        <div class="ButtonFooter PosCenter" style="flex-direction: column; gap: 8px;">
          <button class="btnGlass iconTextStart" @click="emit('view')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiEyeOutline" class="btn-icon" />
            <span>Просмотр</span>
          </button>
          <button class="btnGlass iconTextStart" @click="emit('add')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCashMinus" class="btn-icon" />
            <span>Выдать</span>
          </button>
          <button class="btnGlass iconTextStart" @click="emit('refund')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiUndoVariant" class="btn-icon" />
            <span>Возврат</span>
          </button>
          <button class="btnGlass iconTextStart" @click="emit('delete')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiTrashCanOutline" class="btn-icon" />
            <span>Удалить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
```

---

### Задача 3: AddExpenseModal.vue

**Файлы:**
- Создать: `maribulka-vue/src/components/finance/expenses/AddExpenseModal.vue`
- Эталон: `maribulka-vue/src/components/finance/income/AddIncomeModal.vue`

- [ ] **Шаг 1: Создать `AddExpenseModal.vue`**

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useFinanceStore } from '../../../stores/finance'
import { useReferencesStore } from '../../../stores/references'
import { useAuthStore } from '../../../stores/auth'
import AlertModal from '../../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  dateFrom: string
  dateTo: string
}>()

const emit = defineEmits(['close'])

const financeStore = useFinanceStore()
const refsStore = useReferencesStore()
const authStore = useAuthStore()

const today = new Date().toISOString().split('T')[0]
const categoryId = ref<number | null>(null)
const amount = ref('')
const description = ref('')
const showAlert = ref(false)
const alertMessage = ref('')

onMounted(async () => {
  if (refsStore.expenseCategories.length === 0) {
    await refsStore.fetchExpenseCategories()
  }
})

async function handleSave() {
  if (!amount.value || !categoryId.value) return
  try {
    const result = await financeStore.createExpense({
      date: today,
      category: categoryId.value,
      amount: parseFloat(amount.value),
      description: description.value || null,
      created_by: authStore.userId
    })
    if (result.success) {
      await financeStore.fetchExpenses(props.dateFrom, props.dateTo)
      categoryId.value = null
      amount.value = ''
      description.value = ''
      emit('close')
    } else {
      alertMessage.value = 'Ошибка: ' + (result.error || 'Не удалось создать расход')
      showAlert.value = true
    }
  } catch {
    alertMessage.value = 'Ошибка сети'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Выдать расход</div>

        <div class="info-row">
          <span class="info-label">Дата:</span>
          <span class="info-value">{{ today }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Выдаёт:</span>
          <span class="info-value">{{ authStore.userName }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Категория:</span>
          <select class="modal-input" v-model="categoryId">
            <option :value="null" disabled>— выберите —</option>
            <option v-for="cat in refsStore.expenseCategories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>
        <div class="info-row">
          <span class="info-label">Сумма:</span>
          <input type="number" class="modal-input" v-model="amount" min="1" placeholder="0" />
        </div>
        <div class="info-row">
          <span class="info-label">Описание:</span>
          <input type="text" class="modal-input" v-model="description" placeholder="—" />
        </div>

        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" :disabled="!amount || !categoryId" @click="handleSave">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Сохранить</span>
          </button>
        </div>
      </div>
    </div>

    <AlertModal :is-visible="showAlert" :message="alertMessage" @close="showAlert = false" />
  </Teleport>
</template>
```

---

### Задача 4: ViewExpenseModal.vue

**Файлы:**
- Создать: `maribulka-vue/src/components/finance/expenses/ViewExpenseModal.vue`

- [ ] **Шаг 1: Создать `ViewExpenseModal.vue`**

```vue
<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline } from '@mdi/js'

const props = defineProps<{
  isVisible: boolean
  expense: any | null
}>()

const emit = defineEmits(['close'])

function formatDate(dateStr: string) {
  if (!dateStr) return '—'
  const part = dateStr.split('T')[0]?.split(' ')[0]
  if (!part) return '—'
  const [year, month, day] = part.split('-')
  return `${day}.${month}.${year}`
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay-main" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Просмотр расхода</div>

        <div v-if="expense">
          <div class="info-row">
            <span class="info-label">ID:</span>
            <span class="info-value">{{ expense.id }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Дата:</span>
            <span class="info-value">{{ formatDate(expense.date) }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Категория:</span>
            <span class="info-value">{{ expense.category_name ?? '—' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Сумма:</span>
            <span class="info-value" style="font-weight: 600;">{{ Math.round(parseFloat(expense.amount)) }} ₽</span>
          </div>
          <div class="info-row">
            <span class="info-label">Описание:</span>
            <span class="info-value">{{ expense.description || '—' }}</span>
          </div>
        </div>

        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Закрыть</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
```

---

### Задача 5: RefundExpenseModal.vue

**Файлы:**
- Создать: `maribulka-vue/src/components/finance/expenses/RefundExpenseModal.vue`
- Логика: POST `/api/expenses.php` с `category = 2` (ID категории "Возврат"), `booking_id`, `amount` = paid_amount заказа

- [ ] **Шаг 1: Создать `RefundExpenseModal.vue`**

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useFinanceStore } from '../../../stores/finance'
import AlertModal from '../../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  dateFrom: string
  dateTo: string
}>()

const emit = defineEmits(['close'])

const financeStore = useFinanceStore()

const selectedBookingId = ref<number | null>(null)
const showAlert = ref(false)
const alertMessage = ref('')

const today = new Date().toISOString().split('T')[0]

const selectedBooking = computed(() =>
  financeStore.refundableBookings.find((b: any) => b.id === selectedBookingId.value) ?? null
)

const refundAmount = computed(() =>
  selectedBooking.value ? Math.round(parseFloat(selectedBooking.value.paid_amount) || 0) : 0
)

onMounted(async () => {
  await financeStore.fetchRefundableBookings()
})

function formatOrderId(booking: any) {
  if (!booking?.id || !booking?.created_at) return `#${booking?.id ?? '?'}`
  const [datePart] = booking.created_at.split(' ')
  if (!datePart) return `МБ-${booking.id}`
  const [year, month, day] = datePart.split('-')
  const magicNumber = parseInt(day) * parseInt(month)
  const shortYear = year.slice(-2)
  return `МБ${booking.id}${magicNumber}${shortYear}`
}

async function handleSave() {
  if (!selectedBookingId.value || refundAmount.value <= 0) return
  try {
    const result = await financeStore.createExpense({
      date: today,
      category: 2,
      amount: refundAmount.value,
      booking_id: selectedBookingId.value,
      description: `Возврат по заказу ${formatOrderId(selectedBooking.value)}`,
      created_by: authStore.userId
    })
    if (result.success) {
      await financeStore.fetchExpenses(props.dateFrom, props.dateTo)
      selectedBookingId.value = null
      emit('close')
    } else {
      alertMessage.value = 'Ошибка: ' + (result.error || 'Не удалось создать возврат')
      showAlert.value = true
    }
  } catch {
    alertMessage.value = 'Ошибка сети'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Возврат средств</div>

        <div class="info-row">
          <span class="info-label">Дата:</span>
          <span class="info-value">{{ today }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Заказ:</span>
          <select class="modal-input" v-model="selectedBookingId">
            <option :value="null" disabled>— выберите заказ —</option>
            <option
              v-for="booking in financeStore.refundableBookings"
              :key="booking.id"
              :value="booking.id"
            >
              {{ formatOrderId(booking) }} — {{ booking.client_name }}
            </option>
          </select>
        </div>
        <div class="info-row">
          <span class="info-label">Сумма возврата:</span>
          <span class="info-value" style="font-weight: 600;">
            {{ refundAmount > 0 ? refundAmount + ' ₽' : '—' }}
          </span>
        </div>

        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button
            class="btnGlass iconText"
            :disabled="!selectedBookingId || refundAmount <= 0"
            @click="handleSave"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Оформить возврат</span>
          </button>
        </div>
      </div>
    </div>

    <AlertModal :is-visible="showAlert" :message="alertMessage" @close="showAlert = false" />
  </Teleport>
</template>
```

---

### Задача 6: ExpensesTable.vue

**Файлы:**
- Создать: `maribulka-vue/src/components/finance/expenses/ExpensesTable.vue`
- Эталон: `maribulka-vue/src/components/finance/income/IncomeTable.vue`

Колонки: ID, Дата, Категория, Описание, Сумма ₽ (итого в `<tfoot>`).
Паттерн: `selectedIndex` + клик по строке открывает `ExpensesActionsModal`.

- [ ] **Шаг 1: Создать `ExpensesTable.vue`**

```vue
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  type ColumnDef,
  type SortingState,
  FlexRender
} from '@tanstack/vue-table'
import DatePicker, { type DateRange } from '../../ui/datePicker/DatePicker.vue'
import SearchTable from '../../ui/searchTable/SearchTable.vue'
import { useFinanceStore } from '../../../stores/finance'
import { useReferencesStore } from '../../../stores/references'
import ExpensesActionsModal from './ExpensesActionsModal.vue'
import AddExpenseModal from './AddExpenseModal.vue'
import ViewExpenseModal from './ViewExpenseModal.vue'
import RefundExpenseModal from './RefundExpenseModal.vue'
import ConfirmModal from '../../ConfirmModal.vue'

const financeStore = useFinanceStore()
const refsStore = useReferencesStore()

function getDefaultRange(): DateRange {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const lastDay = new Date(y, d.getMonth() + 1, 0).getDate()
  return {
    from: `${y}-${m}-01`,
    to: `${y}-${m}-${String(lastDay).padStart(2, '0')}`
  }
}

const dateRange = ref<DateRange>(getDefaultRange())

onMounted(async () => {
  if (refsStore.expenseCategories.length === 0) {
    await refsStore.fetchExpenseCategories()
  }
  financeStore.fetchExpenses(dateRange.value.from, dateRange.value.to)
})

watch(dateRange, (range) => {
  financeStore.fetchExpenses(range.from, range.to)
}, { deep: true })

const selectedIndex = ref<number | null>(null)

const selectedExpense = computed(() => {
  if (selectedIndex.value === null) return null
  return table.getRowModel().rows[selectedIndex.value]?.original ?? null
})

function selectRow(index: number) {
  selectedIndex.value = selectedIndex.value === index ? null : index
}

function formatDate(dateString: string): string {
  if (!dateString) return '—'
  const datePart = dateString.split('T')[0]?.split(' ')[0]
  if (!datePart) return '—'
  const parts = datePart.split('-')
  return `${parts[2]}.${parts[1]}.${(parts[0] || '').slice(-2)}`
}

function expensesGlobalFilterFn(row: any, _columnId: string, filterValue: string): boolean {
  const search = filterValue.toLowerCase()
  const { id, date, category_name, description, amount } = row.original
  return [
    String(id),
    formatDate(date),
    category_name || '',
    description || '',
    Math.round(parseFloat(amount)).toString()
  ].some(v => v.toLowerCase().includes(search))
}
expensesGlobalFilterFn.autoRemove = (val: any) => !val

const columns: ColumnDef<any>[] = [
  { accessorKey: 'id', header: 'ID', cell: ({ getValue }) => getValue() },
  { accessorKey: 'date', header: 'Дата', cell: ({ getValue }) => formatDate(getValue() as string) },
  { accessorKey: 'category_name', header: 'Категория', cell: ({ getValue }) => getValue() || '—' },
  { accessorKey: 'description', header: 'Описание', cell: ({ getValue }) => getValue() || '—' },
  {
    accessorKey: 'amount',
    header: 'Сумма ₽',
    cell: ({ getValue }) => Math.round(parseFloat(getValue() as string)).toString()
  }
]

const sorting = ref<SortingState>([{ id: 'date', desc: true }])
const searchQuery = ref('')

const table = useVueTable({
  get data() { return financeStore.expenses },
  columns,
  state: {
    get sorting() { return sorting.value },
    get globalFilter() { return searchQuery.value }
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
  globalFilterFn: expensesGlobalFilterFn
})

const totalAmount = computed(() => {
  return table.getFilteredRowModel().rows.reduce((sum, row) => {
    return sum + (parseFloat(row.original.amount) || 0)
  }, 0)
})

const showActionsModal = ref(false)
const showAddModal = ref(false)
const showViewModal = ref(false)
const showRefundModal = ref(false)
const showDeleteModal = ref(false)

function openActions() {
  if (selectedExpense.value) showActionsModal.value = true
}

function closeModal() {
  showActionsModal.value = false
  showAddModal.value = false
  showViewModal.value = false
  showRefundModal.value = false
  showDeleteModal.value = false
  selectedIndex.value = null
}

function handleAdd() {
  showActionsModal.value = false
  showAddModal.value = true
}

function handleView() {
  showActionsModal.value = false
  showViewModal.value = true
}

function handleRefund() {
  showActionsModal.value = false
  showRefundModal.value = true
}

function handleDelete() {
  showActionsModal.value = false
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!selectedExpense.value) return
  await financeStore.deleteExpense(selectedExpense.value.id)
  await financeStore.fetchExpenses(dateRange.value.from, dateRange.value.to)
  closeModal()
}
</script>

<template>
  <div class="padGlass padGlass-work data-table-panel">
    <div class="pad-title">Расход</div>

    <div class="data-table-filter">
      <DatePicker mode="range" v-model="dateRange" :showPresets="true" />
      <SearchTable
        v-model="searchQuery"
        :count="table.getFilteredRowModel().rows.length"
        placeholder="Поиск по расходам..."
      />
    </div>

    <div v-if="financeStore.expenses.length > 0" class="data-table-scroll">
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
            v-for="(row, rowIndex) in table.getRowModel().rows"
            :key="row.id"
            :class="{ 'row-selected': selectedIndex === rowIndex }"
            @click="selectRow(rowIndex); openActions()"
          >
            <td
              v-for="cell in row.getVisibleCells()"
              :key="cell.id"
              :class="{ 'cell-amount': cell.column.id === 'amount' }"
            >
              <FlexRender
                :render="cell.column.columnDef.cell"
                :props="cell.getContext()"
              />
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4"></td>
            <td class="cell-amount">{{ Math.round(totalAmount) }} ₽</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div v-else class="data-table-empty">
      📭 Нет расходов за выбранный период
    </div>

    <ExpensesActionsModal
      v-if="showActionsModal"
      :expense="selectedExpense"
      @close="closeModal"
      @add="handleAdd"
      @view="handleView"
      @refund="handleRefund"
      @delete="handleDelete"
    />

    <AddExpenseModal
      :isVisible="showAddModal"
      :dateFrom="dateRange.from"
      :dateTo="dateRange.to"
      @close="closeModal"
    />

    <ViewExpenseModal
      v-if="showViewModal"
      :isVisible="showViewModal"
      :expense="selectedExpense"
      @close="closeModal"
    />

    <RefundExpenseModal
      :isVisible="showRefundModal"
      :dateFrom="dateRange.from"
      :dateTo="dateRange.to"
      @close="closeModal"
    />

    <ConfirmModal
      :isVisible="showDeleteModal"
      message="Удалить этот расход?"
      title="Подтверждение"
      @confirm="confirmDelete"
      @cancel="closeModal"
    />
  </div>
</template>
```

---

---

### Задача 7: Сохранение created_by в expenses.php

**Файлы:**
- Изменить: `api/expenses.php`

В таблице `expenses` есть колонки `created_by INT` и `updated_by INT` — сейчас не заполняются.

- [ ] **Шаг 1: Добавить `created_by` в POST обработчик**

Файл: `api/expenses.php`, блок `case 'POST'`:

```php
// Было:
if (!isset($data['date']) || !isset($data['amount']) || !isset($data['category']) || !isset($data['description'])) {
    ...
}

$stmt = $db->prepare("
    INSERT INTO expenses (date, amount, category, description, booking_id)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
    $data['date'],
    $data['amount'],
    $data['category'],
    $data['description'],
    $data['booking_id'] ?? null
]);

// Стало:
if (!isset($data['date']) || !isset($data['amount']) || !isset($data['category']) || !isset($data['description'])) {
    ...
}

$stmt = $db->prepare("
    INSERT INTO expenses (date, amount, category, description, booking_id, created_by)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $data['date'],
    $data['amount'],
    $data['category'],
    $data['description'],
    $data['booking_id'] ?? null,
    $data['created_by'] ?? null
]);
```

- [ ] **Шаг 2: Добавить `created_by_name` в GET запросы**

В SELECT запросах `?start=&end=` и `?month=` добавить JOIN с таблицей users для отображения имени:

```sql
LEFT JOIN users u ON e.created_by = u.id
```

И в SELECT добавить поле:
```sql
u.name as created_by_name
```

- [ ] **Шаг 4: Показать `created_by_name` в ViewExpenseModal**

В `ViewExpenseModal.vue` добавить строку после "Описание":

```html
<div class="info-row">
  <span class="info-label">Выдал:</span>
  <span class="info-value">{{ expense.created_by_name || '—' }}</span>
</div>
```

---

## Проверка

- [ ] `cd maribulka-vue && npx tsc --noEmit` — нет ошибок TypeScript
- [ ] `npm run dev` — сервер стартует без ошибок
- [ ] Лаунчпад → кнопка "Расходы" → открывается таблица ExpensesTable
- [ ] DatePicker меняет период → данные перезагружаются
- [ ] SearchTable фильтрует по ID, дате, категории, описанию, сумме
- [ ] Клик по строке → выделение + открывается ActionsModal с 4 кнопками
- [ ] Кнопка "Выдать" → AddExpenseModal: дата=today (label), выдаёт=userName, категория (select), сумма, описание → POST /api/expenses.php
- [ ] Кнопка "Просмотр" → ViewExpenseModal: ID, дата, категория, сумма, описание
- [ ] Кнопка "Возврат" → RefundExpenseModal: select заказа, сумма=paid_amount → POST category=2
- [ ] Кнопка "Удалить" → ConfirmModal → DELETE /api/expenses.php?id=
- [ ] tfoot показывает итоговую сумму по отфильтрованным строкам
