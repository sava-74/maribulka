# IncomeTable Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Создать универсальный `table.css`, отрефакторить `calendar-table.css` и `BookingsTable.vue` на новые классы, затем создать таблицу "Приход" (`IncomeTable.vue`) в новом стиле.

**Architecture:**
- `table.css` — общий фундамент для всех таблиц проекта: `.data-table-panel`, `.data-table-filter`, `.data-table-scroll`, `.data-table`, строки, пустое состояние
- `calendar-table.css` — только уникальное для записей: `.row-cancelled`, `.row-overdue`, `.cell-amount`
- `income-table.css` — только уникальное для прихода (пока пустой, зарезервирован)
- `BookingsTable.vue` — классы `bookings-table-*` → `data-table-*`
- `IncomeTable.vue` — новый компонент в `src/components/finance/income/`, использует `data-table-*`

**Tech Stack:** Vue 3, TypeScript, TanStack Table, `@mdi/js` + `@jamescoyle/vue-icon`, `DatePicker`, `SearchTable`, CSS-переменные из `style.css`.

---

## Chunk 0: Рефакторинг — создать table.css, обновить calendar-table.css и BookingsTable.vue

### Task 1: Создать `table.css` с общими стилями

**Files:**
- Create: `maribulka-vue/src/assets/table.css`
- Modify: `maribulka-vue/src/main.ts`

- [ ] **Step 1: Создать `table.css`**

Файл: `maribulka-vue/src/assets/table.css`

```css
/* =============================================
   Универсальные стили таблиц
   Использует только переменные из style.css
   ============================================= */

/* --- Обёртка --- */
.data-table-panel {
  display: flex;
  flex-direction: column;
  height: calc(100% - 20px);
  gap: 12px;
  align-items: stretch;
}

/* --- Строка фильтров --- */
.data-table-filter {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  flex-shrink: 0;
  flex-wrap: wrap;
}

/* --- Контейнер прокрутки --- */
.data-table-scroll {
  flex: 1;
  overflow-x: auto;
  overflow-y: auto;
  min-height: 0;
  width: 100%;
}

/* --- Таблица --- */
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--genTextSize);
  color: var(--text-primary);
  margin: 5px 10px;
}

.data-table thead {
  position: sticky;
  top: 0;
  z-index: 1;
}

.data-table th {
  padding: 8px 10px;
  text-align: left;
  font-weight: 600;
  font-size: var(--genTextSizeSmall);
  color: var(--text-secondary);
  background: var(--glass-bg);
  border-bottom: 1px solid var(--glass-border);
  white-space: nowrap;
  user-select: none;
}

.data-table th.sortable {
  cursor: pointer;
}

.data-table th.sortable:hover {
  color: var(--text-primary);
}

.data-table td {
  padding: 7px 10px;
  border-bottom: 1px solid var(--glass-border);
  white-space: nowrap;
}

/* --- Строки --- */
.data-table tbody tr {
  cursor: pointer;
  transition: background 0.12s;
}

.data-table tbody tr:hover {
  background: rgba(255, 255, 255, 0.04);
}

.data-table tbody tr.row-selected {
  background: rgba(255, 255, 255, 0.09);
}

/* --- Строка итогов --- */
.data-table tfoot tr {
  border-top: 2px solid var(--glass-border);
}

.data-table tfoot td {
  padding: 7px 10px;
  font-weight: 700;
  color: var(--text-primary);
}

/* --- Пустое состояние --- */
.data-table-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  font-size: var(--genTextSizeH1);
}

/* --- Мобильная адаптация --- */
@media (pointer: coarse) {
  .data-table th,
  .data-table td,
  .data-table tfoot td {
    padding: 6px 8px;
  }
}
```

- [ ] **Step 2: Зарегистрировать `table.css` в `main.ts` — вместо `calendar-table.css`**

Файл: `maribulka-vue/src/main.ts`, строка 16.

Было:
```typescript
import './assets/calendar-table.css'
```

Стало:
```typescript
import './assets/table.css'
import './assets/calendar-table.css'
```

- [ ] **Step 3: Убедиться что dev-сервер запускается без ошибок**

```bash
cd maribulka-vue && npm run dev
```

Ожидание: сервер стартует, нет ошибок в консоли.

- [ ] **Step 4: Commit**

```bash
git add maribulka-vue/src/assets/table.css maribulka-vue/src/main.ts
git commit -m "feat: add table.css — universal table base styles"
```

---

### Task 2: Обновить `calendar-table.css` — оставить только уникальное

**Files:**
- Modify: `maribulka-vue/src/assets/calendar-table.css`

Из `calendar-table.css` удаляем всё, что теперь живёт в `table.css`. Оставляем только уникальное для таблицы записей.

- [ ] **Step 1: Заменить содержимое `calendar-table.css`**

Файл: `maribulka-vue/src/assets/calendar-table.css`

```css
/* =============================================
   Bookings Table — специфичные стили
   Общие стили таблицы — в table.css
   ============================================= */

/* --- Отменённые записи --- */
.data-table tbody tr.row-cancelled {
  opacity: 0.45;
}

/* --- Просроченные "новые" записи --- */
.data-table tbody tr.row-overdue {
  color: var(--text-secondary);
}

/* --- Ячейка суммы --- */
.data-table .cell-amount {
  font-weight: 600;
}
```

- [ ] **Step 2: Проверить dev-сервер — таблица записей выглядит как прежде**

```bash
cd maribulka-vue && npm run dev
```

Открыть http://localhost:5173 → Учёт → Запись → переключиться на вид таблицы.
Ожидание: таблица выглядит идентично как до рефакторинга.

- [ ] **Step 3: Commit**

```bash
git add maribulka-vue/src/assets/calendar-table.css
git commit -m "refactor: calendar-table.css — keep only booking-specific styles"
```

---

### Task 3: Обновить `BookingsTable.vue` — заменить классы на универсальные

**Files:**
- Modify: `maribulka-vue/src/components/calendar/BookingsTable.vue`

Заменяем классы в `<template>`:
- `bookings-table-panel` → `data-table-panel`
- `bookings-table-filter` → `data-table-filter`
- `bookings-table-scroll` → `data-table-scroll`
- `bookings-table` → `data-table`
- `bookings-table-empty` → `data-table-empty`

- [ ] **Step 1: Заменить класс обёртки**

Файл: `maribulka-vue/src/components/calendar/BookingsTable.vue`

Было:
```html
<div class="padGlass padGlass-work bookings-table-panel">
```

Стало:
```html
<div class="padGlass padGlass-work data-table-panel">
```

- [ ] **Step 2: Заменить класс строки фильтров**

Было:
```html
<div class="bookings-table-filter">
```

Стало:
```html
<div class="data-table-filter">
```

- [ ] **Step 3: Заменить класс скролл-контейнера**

Было:
```html
<div v-if="bookingsStore.bookings.length > 0" class="bookings-table-scroll">
```

Стало:
```html
<div v-if="bookingsStore.bookings.length > 0" class="data-table-scroll">
```

- [ ] **Step 4: Заменить класс таблицы и ячейки суммы**

Было:
```html
<table class="bookings-table">
```

Стало:
```html
<table class="data-table">
```

Также в `<td>` — `cell-amount` остаётся как есть (он используется как модификатор внутри `.data-table`).

- [ ] **Step 5: Заменить класс пустого состояния**

Было:
```html
<div v-else class="bookings-table-empty">
```

Стало:
```html
<div v-else class="data-table-empty">
```

- [ ] **Step 6: Проверить таблицу записей визуально**

```bash
cd maribulka-vue && npm run dev
```

Открыть http://localhost:5173 → Учёт → Запись (вид таблицы).
Ожидание:
- таблица выглядит идентично как до рефакторинга
- hover, выделение строки, отменённые (opacity) и просроченные (серые) строки работают

- [ ] **Step 7: Commit**

```bash
git add maribulka-vue/src/components/calendar/BookingsTable.vue
git commit -m "refactor: BookingsTable — use universal data-table-* classes"
```

---

## Chunk 1: CSS для Приход и ViewIncomeModal

### Task 4: Создать `income-table.css` и зарегистрировать

**Files:**
- Create: `maribulka-vue/src/assets/income-table.css`
- Modify: `maribulka-vue/src/main.ts`

- [ ] **Step 1: Создать `income-table.css`**

Файл: `maribulka-vue/src/assets/income-table.css`

```css
/* =============================================
   Income Table — специфичные стили
   Общие стили таблицы — в table.css
   ============================================= */

/* Пока пустой — зарезервирован для будущих
   специфичных стилей таблицы Приход */
```

- [ ] **Step 2: Зарегистрировать в `main.ts`**

Файл: `maribulka-vue/src/main.ts` — добавить после `calendar-table.css`:

```typescript
import './assets/income-table.css'
```

- [ ] **Step 3: Commit**

```bash
git add maribulka-vue/src/assets/income-table.css maribulka-vue/src/main.ts
git commit -m "feat: add income-table.css placeholder"
```

---

### Task 5: Создать ViewIncomeModal.vue (новый стиль)

**Files:**
- Create: `maribulka-vue/src/components/finance/income/ViewIncomeModal.vue`
- Reference (логика): `maribulka-vue/src/components/accounting/ViewIncomeModal.vue`

- [ ] **Step 1: Создать папки**

```bash
mkdir -p maribulka-vue/src/components/finance/income
```

- [ ] **Step 2: Создать `ViewIncomeModal.vue`**

Файл: `maribulka-vue/src/components/finance/income/ViewIncomeModal.vue`

```vue
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCashMultiple, mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useFinanceStore } from '../../../stores/finance'
import AlertModal from '../../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  income: any | null
}>()
const emit = defineEmits(['close'])

const financeStore = useFinanceStore()
const payments = ref<any[]>([])
const paymentAmount = ref('')
const showConfirmModal = ref(false)
const showAlert = ref(false)
const alertMessage = ref('')

watch(() => props.isVisible, async (visible) => {
  if (visible && props.income?.booking_id) {
    await financeStore.fetchIncomeByBooking(props.income.booking_id)
    payments.value = financeStore.incomeByBooking
  }
  if (visible && props.income) {
    const total = parseFloat(props.income.total_amount) || 0
    const paid = parseFloat(props.income.paid_amount) || 0
    paymentAmount.value = Math.round(total - paid).toString()
  }
})

const formatDate = (dateStr: string) => {
  if (!dateStr) return '—'
  const [datePart] = dateStr.split(' ')
  if (!datePart) return '—'
  const [year, month, day] = datePart.split('-')
  return `${day}.${month}.${year}`
}

const orderInfo = computed(() => {
  if (!props.income) return null

  const total = parseFloat(props.income.total_amount) || 0
  const paid = parseFloat(props.income.paid_amount) || 0

  const formatDateTime = (dateStr: string) => {
    if (!dateStr) return '—'
    const [datePart, timePart] = dateStr.split(' ')
    if (!datePart) return '—'
    const [year, month, day] = datePart.split('-')
    const time = timePart?.substring(0, 5) || '00:00'
    return `${day}.${month}.${year} ${time}`
  }

  const formatOrderId = () => {
    if (!props.income.booking_id || !props.income.booking_created_at) {
      return props.income.order_number || '—'
    }
    const bookingId = props.income.booking_id
    const createdAt = props.income.booking_created_at
    const [datePart] = createdAt.split(' ')
    if (!datePart) return `МБ-${bookingId}`
    const [year, month, day] = datePart.split('-')
    const magicNumber = parseInt(day) * parseInt(month)
    const shortYear = year.slice(-2)
    return `МБ${bookingId}${magicNumber}${shortYear}`
  }

  const statusMap: Record<string, string> = {
    'new': '🔵 Новая',
    'in_progress': '🟠 Идёт съёмка',
    'completed': '🟠 Состоялась',
    'completed_partially': '🟡 Частично выдано',
    'not_completed': '🔴 Не состоялась',
    'cancelled_by_client': '⚪ Отмена-К',
    'cancelled_by_photographer': '⚪ Отмена-Ф',
    'client_no_show': '🔴 Неявка клиента',
    'delivered': '🟢 Выдано',
    'cancelled': '🔴 Отменена',
    'failed': '🔴 Не состоялась'
  }
  const statusText = statusMap[props.income.booking_status] || props.income.booking_status

  const paymentStatusMap: Record<string, string> = {
    'unpaid': 'Не оплачена',
    'partially_paid': 'Частично оплачена',
    'fully_paid': 'Полностью оплачена'
  }
  const paymentStatusText = paymentStatusMap[props.income.payment_status] || props.income.payment_status

  const paymentStatusColor =
    props.income.payment_status === 'fully_paid' ? '#2E8B57' :
    props.income.payment_status === 'partially_paid' ? '#FFA500' : '#DC143C'

  return {
    orderId: formatOrderId(),
    status: statusText,
    paymentStatus: paymentStatusText,
    paymentStatusColor,
    client: props.income.client_name,
    phone: props.income.phone,
    shootingType: props.income.shooting_type_name,
    quantity: props.income.quantity || 1,
    bookingDate: formatDate(props.income.booking_date),
    shootingDate: formatDateTime(props.income.shooting_date),
    deliveryDate: formatDate(props.income.delivery_date),
    processedAt: formatDateTime(props.income.processed_at),
    total: Math.round(total),
    paid: Math.round(paid),
    remaining: Math.round(total - paid)
  }
})

function formatPaymentCategory(category: string) {
  switch (category) {
    case 'advance': return 'Аванс'
    case 'balance': return 'Доплата'
    case 'full_payment': return 'Оплачено'
    default: return category
  }
}

function getPaymentCategoryColor(category: string) {
  switch (category) {
    case 'advance': return '#FFA500'
    case 'balance': return '#4682B4'
    case 'full_payment': return '#2E8B57'
    default: return 'var(--text-secondary)'
  }
}

async function handlePayment() {
  if (!props.income?.booking_id || !paymentAmount.value) return
  const paymentData = {
    booking_id: props.income.booking_id,
    amount: parseFloat(paymentAmount.value),
    category: 'balance',
    date: new Date().toISOString().split('T')[0]
  }
  try {
    const response = await fetch('/api/income.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(paymentData)
    })
    const result = await response.json()
    if (result.success) {
      await financeStore.fetchIncomeByBooking(props.income.booking_id)
      payments.value = financeStore.incomeByBooking
      showConfirmModal.value = false
    } else {
      alertMessage.value = 'Ошибка: ' + (result.error || 'Не удалось создать платёж')
      showAlert.value = true
      showConfirmModal.value = false
    }
  } catch {
    alertMessage.value = 'Ошибка сети'
    showAlert.value = true
    showConfirmModal.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Информация о платеже</div>

        <div v-if="orderInfo" class="order-details">
          <div class="info-section">
            <div class="info-row">
              <span class="info-label">ID заказа:</span>
              <span class="info-value strong">{{ orderInfo.orderId }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Статус заказа:</span>
              <span class="info-value">{{ orderInfo.status }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Статус оплаты:</span>
              <span class="info-value" :style="{ color: orderInfo.paymentStatusColor, fontWeight: 600 }">
                {{ orderInfo.paymentStatus }}
              </span>
            </div>
          </div>

          <div class="divider"></div>

          <div class="info-section">
            <div class="info-row">
              <span class="info-label">Клиент:</span>
              <span class="info-value">{{ orderInfo.client }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Телефон:</span>
              <span class="info-value"><a :href="`tel:${orderInfo.phone}`">{{ orderInfo.phone }}</a></span>
            </div>
            <div class="info-row">
              <span class="info-label">Тип съёмки:</span>
              <span class="info-value">{{ orderInfo.shootingType }}</span>
            </div>
            <div class="info-row" v-if="orderInfo.quantity > 1">
              <span class="info-label">Количество:</span>
              <span class="info-value">{{ orderInfo.quantity }}</span>
            </div>
          </div>

          <div class="divider"></div>

          <div class="info-section">
            <div class="info-row">
              <span class="info-label">Дата создания:</span>
              <span class="info-value">{{ orderInfo.bookingDate }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Дата съёмки:</span>
              <span class="info-value">{{ orderInfo.shootingDate }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Дата выдачи (план):</span>
              <span class="info-value">{{ orderInfo.deliveryDate }}</span>
            </div>
            <div class="info-row" v-if="orderInfo.processedAt !== '—'">
              <span class="info-label">Дата выдачи (факт):</span>
              <span class="info-value">{{ orderInfo.processedAt }}</span>
            </div>
          </div>

          <div class="divider"></div>

          <div class="info-section">
            <div class="info-row">
              <span class="info-label" style="font-weight: 600;">История платежей</span>
            </div>
            <div v-if="payments.length > 0">
              <div v-for="payment in payments" :key="payment.id" class="info-row">
                <span class="info-label">{{ formatDate(payment.date) }}:</span>
                <span class="info-value" :style="{ color: getPaymentCategoryColor(payment.category), fontWeight: 600 }">
                  {{ formatPaymentCategory(payment.category) }} — {{ Math.round(parseFloat(payment.amount)) }} ₽
                </span>
              </div>
            </div>
            <div v-else class="info-row">
              <span class="info-value">Нет платежей</span>
            </div>
          </div>

          <div class="divider"></div>

          <div class="info-section">
            <div class="info-row">
              <span class="info-label">Итоговая сумма:</span>
              <span class="info-value strong">{{ orderInfo.total }} ₽</span>
            </div>
            <div class="info-row">
              <span class="info-label">Оплачено:</span>
              <span class="info-value" style="color: #2E8B57; font-weight: 600;">{{ orderInfo.paid }} ₽</span>
            </div>
            <div class="info-row" v-if="orderInfo.remaining > 0">
              <span class="info-label">Остаток:</span>
              <input
                type="number"
                v-model="paymentAmount"
                class="modal-input"
                :max="orderInfo.remaining"
                min="1"
              />
              <span class="info-value"> ₽</span>
            </div>
          </div>
        </div>

        <div class="ButtonFooter PosRight">
          <button
            v-if="orderInfo && orderInfo.remaining > 0"
            class="btnGlass iconText"
            @click="showConfirmModal = true"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCashMultiple" class="btn-icon" />
            <span>Оплатить</span>
          </button>
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Закрыть</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Подтверждение платежа -->
    <div v-if="showConfirmModal" class="modal-overlay" @click.self="showConfirmModal = false">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Принять платёж?</div>
        <p style="text-align: center; margin: 8px 0; color: var(--text-primary);">
          Принять платёж на сумму <strong>{{ paymentAmount }} ₽</strong>?
        </p>
        <div class="ButtonFooter PosCenter">
          <button class="btnGlass iconText" @click="showConfirmModal = false">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="handlePayment">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Принять</span>
          </button>
        </div>
      </div>
    </div>

    <AlertModal
      :is-visible="showAlert"
      :message="alertMessage"
      @close="showAlert = false"
    />
  </Teleport>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add maribulka-vue/src/components/finance/income/ViewIncomeModal.vue
git commit -m "feat: add ViewIncomeModal (new style, finance/income)"
```

---

## Chunk 2: IncomeTable.vue

### Task 6: Создать IncomeTable.vue

**Files:**
- Create: `maribulka-vue/src/components/finance/income/IncomeTable.vue`
- Reference (логика): `maribulka-vue/src/components/accounting/IncomeTable.vue`

- [ ] **Step 1: Создать `IncomeTable.vue`**

Файл: `maribulka-vue/src/components/finance/income/IncomeTable.vue`

```vue
<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  type ColumnDef,
  type SortingState,
  FlexRender
} from '@tanstack/vue-table'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiEyeOutline, mdiTrashCanOutline, mdiRefresh } from '@mdi/js'
import { useFinanceStore } from '../../../stores/finance'
import DatePicker, { type DateRange } from '../../ui/datePicker/DatePicker.vue'
import SearchTable from '../../ui/searchTable/SearchTable.vue'
import ViewIncomeModal from './ViewIncomeModal.vue'
import ConfirmModal from '../../ConfirmModal.vue'

const financeStore = useFinanceStore()

// --- Период (по умолчанию текущий месяц) ---
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
const searchQuery = ref('')
const selectedIndex = ref<number | null>(null)

const selectedIncome = computed(() => {
  if (selectedIndex.value === null) return null
  return table.getRowModel().rows[selectedIndex.value]?.original ?? null
})

const showViewModal = ref(false)
const showDeleteModal = ref(false)

function closeModal() {
  showViewModal.value = false
  showDeleteModal.value = false
  selectedIndex.value = null
}

watch(dateRange, (range) => {
  financeStore.fetchIncome(range.from, range.to)
}, { immediate: true, deep: true })

function formatDate(dateStr: string) {
  if (!dateStr) return '—'
  const part = dateStr.split('T')[0]?.split(' ')[0]
  if (!part) return '—'
  const [year, month, day] = part.split('-')
  return `${day}.${month}.${year.slice(-2)}`
}

function formatAmount(val: string | number) {
  return Math.round(parseFloat(val.toString())).toString()
}

function getBookingStatusText(status: string) {
  const map: Record<string, string> = {
    'new': '🔵 Новая',
    'in_progress': '🟠 Идёт съёмка',
    'completed': '🟠 Состоялась',
    'completed_partially': '🟡 Частично выдано',
    'not_completed': '🔴 Не состоялась',
    'cancelled_by_client': '⚪ Отмена-К',
    'cancelled_by_photographer': '⚪ Отмена-Ф',
    'client_no_show': '🔴 Неявка',
    'delivered': '🟢 Выдано',
    'cancelled': '🔴 Отменена',
    'failed': '🔴 Не состоялась'
  }
  return map[status] || status
}

function getPaymentStatusText(status: string) {
  const map: Record<string, string> = {
    'unpaid': '❌ Не оплачена',
    'partially_paid': '🟡 Частично',
    'fully_paid': '✅ Оплачено'
  }
  return map[status] || status
}

function incomeGlobalFilterFn(row: any, _columnId: string, filterValue: string) {
  const search = filterValue.toLowerCase()
  const { id, date, order_number, client_name, total_amount, promo_discount_percent, amount, booking_status, payment_status } = row.original
  const discount = promo_discount_percent ? `${promo_discount_percent}%` : '—'
  return [
    String(id),
    formatDate(date),
    order_number || '—',
    client_name || '',
    formatAmount(total_amount),
    discount,
    formatAmount(amount),
    getBookingStatusText(booking_status).toLowerCase(),
    getPaymentStatusText(payment_status).toLowerCase()
  ].some(v => v.toLowerCase().includes(search))
}
incomeGlobalFilterFn.autoRemove = (val: any) => !val

const columns: ColumnDef<any>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    cell: ({ getValue }) => getValue()
  },
  {
    accessorKey: 'date',
    header: 'Дата',
    cell: ({ getValue }) => formatDate(getValue() as string)
  },
  {
    accessorKey: 'order_number',
    header: 'ID заказа',
    cell: ({ getValue }) => getValue() || '—'
  },
  {
    accessorKey: 'client_name',
    header: 'Клиент'
  },
  {
    accessorKey: 'total_amount',
    header: 'Стоимость ₽',
    cell: ({ getValue }) => formatAmount(getValue() as string | number)
  },
  {
    accessorKey: 'promo_discount_percent',
    header: 'Скидка',
    cell: ({ getValue }) => {
      const v = getValue()
      return v ? `-${v}%` : '—'
    }
  },
  {
    accessorKey: 'amount',
    header: 'Сумма ₽',
    cell: ({ getValue }) => formatAmount(getValue() as string | number)
  },
  {
    accessorKey: 'booking_status',
    header: 'Статус заказа',
    cell: ({ getValue }) => getBookingStatusText(getValue() as string)
  },
  {
    accessorKey: 'payment_status',
    header: 'Статус оплаты',
    cell: ({ getValue }) => getPaymentStatusText(getValue() as string)
  }
]

const sorting = ref<SortingState>([{ id: 'date', desc: true }])

const table = useVueTable({
  get data() { return financeStore.income },
  columns,
  state: {
    get sorting() { return sorting.value },
    get globalFilter() { return searchQuery.value }
  },
  globalFilterFn: incomeGlobalFilterFn,
  onSortingChange: u => {
    sorting.value = typeof u === 'function' ? u(sorting.value) : u
  },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel()
})

const totalAmount = computed(() =>
  table.getFilteredRowModel().rows.reduce((sum, row) =>
    sum + (parseFloat(row.original.amount) || 0), 0)
)

const canDelete = computed(() => {
  if (!selectedIncome.value) return false
  const s = selectedIncome.value.booking_status
  return s !== 'completed' && s !== 'delivered' && s !== 'completed_partially'
})

function selectRow(index: number) {
  selectedIndex.value = selectedIndex.value === index ? null : index
}

async function confirmDelete() {
  if (!selectedIncome.value) return
  await financeStore.deleteIncome(selectedIncome.value.id)
  await financeStore.fetchIncome(dateRange.value.from, dateRange.value.to)
  closeModal()
}
</script>

<template>
  <div class="padGlass padGlass-work data-table-panel">

    <div class="data-table-filter">
      <DatePicker mode="range" v-model="dateRange" :showPresets="true" />
      <SearchTable
        v-model="searchQuery"
        :count="table.getFilteredRowModel().rows.length"
        placeholder="Поиск по приходу..."
      />
      <button
        class="btnGlass iconText"
        :disabled="!selectedIncome"
        @click="showViewModal = true"
      >
        <span class="inner-glow"></span>
        <span class="top-shine"></span>
        <svg-icon type="mdi" :path="mdiEyeOutline" class="btn-icon" />
        <span>Просмотр</span>
      </button>
      <button
        class="btnGlass iconText"
        :disabled="!selectedIncome || !canDelete"
        @click="showDeleteModal = true"
      >
        <span class="inner-glow"></span>
        <span class="top-shine"></span>
        <svg-icon type="mdi" :path="mdiTrashCanOutline" class="btn-icon" />
        <span>Удалить</span>
      </button>
      <button
        class="btnGlass iconText"
        @click="financeStore.fetchIncome(dateRange.from, dateRange.to)"
      >
        <span class="inner-glow"></span>
        <span class="top-shine"></span>
        <svg-icon type="mdi" :path="mdiRefresh" class="btn-icon" />
        <span>Обновить</span>
      </button>
    </div>

    <div v-if="financeStore.income.length > 0" class="data-table-scroll">
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
            v-for="(row, index) in table.getRowModel().rows"
            :key="row.id"
            :class="{ 'row-selected': selectedIndex === index }"
            @click="selectRow(index)"
          >
            <td v-for="cell in row.getVisibleCells()" :key="cell.id">
              <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="6" style="text-align: right; color: var(--text-secondary);">Всего за период:</td>
            <td><strong>{{ Math.round(totalAmount) }} ₽</strong></td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div v-else-if="financeStore.loadingIncome" class="data-table-empty">
      Загрузка...
    </div>

    <div v-else class="data-table-empty">
      📭 Нет поступлений за выбранный период
    </div>

    <ViewIncomeModal
      :is-visible="showViewModal"
      :income="selectedIncome"
      @close="closeModal"
    />

    <ConfirmModal
      :is-visible="showDeleteModal"
      message="Вы уверены, что хотите удалить этот платёж?"
      title="Подтверждение удаления"
      @confirm="confirmDelete"
      @cancel="closeModal"
    />

  </div>
</template>
```

- [ ] **Step 2: Проверить TypeScript**

```bash
cd maribulka-vue && npx tsc --noEmit
```

Ожидание: нет ошибок компиляции.

- [ ] **Step 3: Commit**

```bash
git add maribulka-vue/src/components/finance/income/IncomeTable.vue
git commit -m "feat: add IncomeTable (new style, finance/income)"
```

---

## Chunk 3: Подключение через лаунчпад + удаление старых файлов

### Task 7: Подключить IncomeTable через навигацию

**Files:**
- Modify: `maribulka-vue/src/stores/navigation.ts`
- Modify: `maribulka-vue/src/components/launchpad/LaunchPad.vue`
- Modify: `maribulka-vue/src/App.vue`

- [ ] **Step 1: Добавить `'income'` в PageType**

Файл: `maribulka-vue/src/stores/navigation.ts`

Найти строку с `export type PageType` и добавить `'income'`:

Было:
```typescript
export type PageType = 'home' | 'portfolio' | 'accounting' | 'settings' | 'references' | 'sandbox' | 'calendar' | 'bookings'
```

Стало:
```typescript
export type PageType = 'home' | 'portfolio' | 'accounting' | 'settings' | 'references' | 'sandbox' | 'calendar' | 'bookings' | 'income'
```

- [ ] **Step 2: Подключить кнопку "Приход" в LaunchPad.vue**

Файл: `maribulka-vue/src/components/launchpad/LaunchPad.vue`

Найти функцию `openBookings` как образец и добавить рядом:

```typescript
function openIncome() {
  navStore.navigateTo('income')
  close()
}
```

Найти кнопку "Приход" в template (там где только `onRipple`) и добавить вызов:

Было:
```html
@click="onRipple($event)"
```

Стало:
```html
@click="onRipple($event); openIncome()"
```

- [ ] **Step 3: Добавить IncomeTable в App.vue**

Файл: `maribulka-vue/src/App.vue`

Добавить импорт рядом с `BookingsTable`:
```typescript
import IncomeTable from './components/finance/income/IncomeTable.vue'
```

Добавить рендер рядом с `<BookingsTable>`:
```html
<IncomeTable v-if="navStore.currentPage === 'income'" />
```

- [ ] **Step 4: Проверить визуально**

```bash
cd maribulka-vue && npm run dev
```

Открыть http://localhost:5173 → клик на меню → Лаунчпад → Приход.
Ожидание:
- таблица открывается на весь рабочий стол (как BookingsTable — padGlass-work на весь экран)
- DatePicker меняет период → данные перезагружаются
- SearchTable фильтрует строки, счётчик обновляется
- клик по строке → выделение, повторный клик → снятие
- кнопка "Просмотр" активна только при выбранной строке → открывает ViewIncomeModal
- кнопка "Удалить" заблокирована для статусов `completed`, `delivered`, `completed_partially`
- последняя строка таблицы показывает сумму "Всего за период"

- [ ] **Step 5: Commit**

```bash
git add maribulka-vue/src/stores/navigation.ts maribulka-vue/src/components/launchpad/LaunchPad.vue maribulka-vue/src/App.vue
git commit -m "feat: connect IncomeTable to launchpad navigation"
```

---

### Task 8: Удалить старые файлы

**Files:**
- Delete: `maribulka-vue/src/components/accounting/IncomeTable.vue`
- Delete: `maribulka-vue/src/components/accounting/ViewIncomeModal.vue`

- [ ] **Step 1: Удалить старые файлы**

```bash
rm maribulka-vue/src/components/accounting/IncomeTable.vue
rm maribulka-vue/src/components/accounting/ViewIncomeModal.vue
```

- [ ] **Step 2: Убедиться что сборка проходит без ошибок**

```bash
cd maribulka-vue && npx tsc --noEmit
```

Ожидание: нет ошибок — старые файлы нигде больше не импортируются.

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "chore: remove old IncomeTable and ViewIncomeModal from accounting/"
```

---

## Chunk 4: IncomeActionsModal + заголовки таблиц

### Task 9: Создать IncomeActionsModal.vue

**Files:**
- Create: `maribulka-vue/src/components/finance/income/IncomeActionsModal.vue`

Модалка открывается при клике на строку таблицы (по аналогии с `BookingActionsModal`).
Три кнопки: **Внести**, **Просмотр**, **Удалить**.

**Props:**
```typescript
defineProps<{
  income: any | null
}>()
defineEmits(['close', 'add', 'view', 'delete'])
```

**Кнопка "Внести"** — форма прямо в модалке:
- Дата: текущая (`new Date().toISOString().split('T')[0]`), поле `disabled`
- От кого: "Администратор", поле `disabled`
- Сумма: `<input type="number" class="modal-input" v-model="amount" min="1">`
- Примечания: `<input type="text" class="modal-input" v-model="notes">`
- Кнопка "Сохранить" → POST `/api/income.php` → emit `'close'` + `financeStore.fetchIncome`

**Структура шаблона:**
```html
<Teleport to="body">
  <div class="modal-overlay" @click.self="emit('close')">
    <div class="padGlass modal-sm">
      <div class="modal-glassTitle">Действия с платежом</div>

      <!-- Форма внесения платежа -->
      <div class="info-row">
        <span class="info-label">Дата:</span>
        <input type="text" class="modal-input" :value="today" disabled />
      </div>
      <div class="info-row">
        <span class="info-label">От кого:</span>
        <input type="text" class="modal-input" value="Администратор" disabled />
      </div>
      <div class="info-row">
        <span class="info-label">Сумма:</span>
        <input type="number" class="modal-input" v-model="amount" min="1" placeholder="0" />
      </div>
      <div class="info-row">
        <span class="info-label">Примечания:</span>
        <input type="text" class="modal-input" v-model="notes" placeholder="—" />
      </div>

      <div class="ButtonFooter PosSpace">
        <button class="btnGlass iconText" @click="handleAdd" :disabled="!amount">
          <span class="inner-glow"></span><span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiCashPlus" class="btn-icon" />
          <span>Внести</span>
        </button>
        <button class="btnGlass iconText" @click="emit('view')">
          <span class="inner-glow"></span><span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiEyeOutline" class="btn-icon" />
          <span>Просмотр</span>
        </button>
        <button class="btnGlass iconText" @click="emit('delete')">
          <span class="inner-glow"></span><span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiTrashCanOutline" class="btn-icon" />
          <span>Удалить</span>
        </button>
      </div>
    </div>
  </div>
</Teleport>
```

**handleAdd:**
```typescript
async function handleAdd() {
  if (!amount.value || !props.income?.booking_id) return
  const body = {
    booking_id: props.income.booking_id,
    amount: parseFloat(amount.value),
    category: 'balance',
    date: today,
    notes: notes.value || null
  }
  const res = await fetch('/api/income.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  })
  const result = await res.json()
  if (result.success) {
    emit('close')
  } else {
    // показать AlertModal с ошибкой
  }
}
```

Иконки: `mdiCashPlus`, `mdiEyeOutline`, `mdiTrashCanOutline` из `@mdi/js`.

---

### Task 10: Обновить IncomeTable.vue — клик открывает IncomeActionsModal

**Files:**
- Modify: `maribulka-vue/src/components/finance/income/IncomeTable.vue`

Изменения:
- Убрать кнопки "Просмотр", "Удалить", "Обновить" из фильтр-бара
- Добавить импорт `IncomeActionsModal`
- `showActionsModal = ref(false)`
- Клик по строке: `selectRow(index); showActionsModal.value = true`
- `@close` — закрывает, сбрасывает выделение
- `@view` — закрывает actions, открывает ViewIncomeModal
- `@delete` — закрывает actions, открывает ConfirmModal
- `@close` из actions также вызывает `financeStore.fetchIncome(dateRange.from, dateRange.to)` (после "Внести")

---

### Task 11: Добавить `.pad-title` заголовки в таблицы

**Files:**
- Modify: `maribulka-vue/src/components/finance/income/IncomeTable.vue`
- Modify: `maribulka-vue/src/components/calendar/BookingsTable.vue`

В `IncomeTable.vue` — первым элементом внутри `.data-table-panel`:
```html
<div class="pad-title">Приход</div>
```

В `BookingsTable.vue` — первым элементом внутри `.data-table-panel`:
```html
<div class="pad-title">Записи</div>
```
