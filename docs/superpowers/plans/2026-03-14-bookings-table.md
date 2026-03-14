# Bookings Table Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Создать страницу "Записи" — таблицу бронирований с фильтром диапазона дат в glass-стиле, доступную через кнопку "Записи" в LaunchPad.

**Architecture:** Новый компонент `BookingsTable.vue` в `src/components/calendar/` использует TanStack Table для рендеринга 13 колонок из `useBookingsStore`. Над таблицей — два flatpickr-поля (начало/конец периода), по умолчанию текущий месяц. Стили — отдельный `calendar-table.css` только с CSS-переменными из `style.css`.

> **Важно:** `src/components/accounting/` — архив старых файлов, используется ТОЛЬКО как справочник логики. Не импортировать из него ничего. Все модалки берём из `src/components/calendar/`.

**Tech Stack:** Vue 3 + TypeScript, TanStack Table (`@tanstack/vue-table`), flatpickr, Pinia (`useBookingsStore`), `@mdi/js` + `@jamescoyle/vue-icon`

---

## Chunk 1: Инфраструктура — навигация и подключение

### Task 1: Добавить `'bookings'` в PageType

**Files:**
- Modify: `maribulka-vue/src/stores/navigation.ts`

- [ ] Открыть `src/stores/navigation.ts`
- [ ] Изменить тип `PageType` — добавить `'bookings'` в union:
  ```typescript
  export type PageType = 'home' | 'portfolio' | 'accounting' | 'settings' | 'references' | 'sandbox' | 'calendar' | 'bookings'
  ```
- [ ] Убедиться что `navigateTo` не требует правок (принимает `PageType` — автоматически)

---

### Task 2: Подключить кнопку "Записи" в LaunchPad

**Files:**
- Modify: `maribulka-vue/src/components/launchpad/LaunchPad.vue`

- [ ] В `<script setup>` добавить функцию `openBookings` после `openCalendar`:
  ```typescript
  function openBookings() {
    navStore.navigateTo('bookings')
    close()
  }
  ```
- [ ] В template найти кнопку "Записи" (иконка `mdiTableLarge`, строка ~93) — сейчас `@click="onRipple($event)"`. Изменить на:
  ```html
  <button class="btnGlass bigIcon" @click="onRipple($event); openBookings()">
  ```

---

### Task 3: Зарегистрировать страницу в App.vue

**Files:**
- Modify: `maribulka-vue/src/App.vue`

- [ ] В `<script setup>` добавить импорт нового компонента (после импорта `CalendarPanel`):
  ```typescript
  import BookingsTable from './components/calendar/BookingsTable.vue'
  ```
- [ ] В `<template>` внутри `<div class="worck-table">` добавить после `</template>` блока calendar:
  ```html
  <BookingsTable v-if="navStore.currentPage === 'bookings'" />
  ```

---

## Chunk 2: CSS-файл таблицы

### Task 4: Создать `calendar-table.css`

**Files:**

- Create: `maribulka-vue/src/assets/calendar-table.css` ← в общей папке assets, не в calendar/

Файл содержит ТОЛЬКО стили для таблицы записей. Никаких новых переменных — только из `style.css`.

- [ ] Создать файл `src/assets/calendar-table.css` со следующим содержимым:

```css
/* =============================================
   Bookings Table Styles
   Использует только переменные из style.css
   ============================================= */

/* --- Обёртка страницы --- */
.bookings-table-panel {
  display: flex;
  flex-direction: column;
  height: calc(100% - 20px);
  gap: 12px;
  overflow: hidden;
}

/* --- Строка фильтра дат --- */
.bookings-table-filter {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
  flex-wrap: wrap;
}

.bookings-table-filter-label {
  font-size: var(--genTextSize);
  color: var(--text-secondary);
  white-space: nowrap;
}

.bookings-table-filter-input {
  box-sizing: border-box;
  padding: 6px 10px;
  font-size: var(--genTextSize);
  font-family: inherit;
  color: var(--text-primary);
  background: var(--glass-bg);
  border: 1px solid var(--glass-border);
  border-radius: calc(var(--padRadius) / 2);
  outline: none;
  transition: border-color 0.2s ease;
  width: 120px;
  cursor: pointer;
}

.bookings-table-filter-input:focus {
  border-color: var(--text-secondary);
}

/* --- Контейнер прокрутки таблицы --- */
.bookings-table-scroll {
  flex: 1;
  overflow: auto;
  min-height: 0;
}

/* --- Таблица --- */
.bookings-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--genTextSize);
  color: var(--text-primary);
}

.bookings-table thead {
  position: sticky;
  top: 0;
  z-index: 1;
}

.bookings-table th {
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

.bookings-table th.sortable {
  cursor: pointer;
}

.bookings-table th.sortable:hover {
  color: var(--text-primary);
}

.bookings-table td {
  padding: 7px 10px;
  border-bottom: 1px solid var(--glass-border);
  white-space: nowrap;
}

/* --- Строки --- */
.bookings-table tbody tr {
  cursor: pointer;
  transition: background 0.12s;
}

.bookings-table tbody tr:hover {
  background: rgba(255, 255, 255, 0.04);
}

.bookings-table tbody tr.row-selected {
  background: rgba(255, 255, 255, 0.09);
}

/* Отменённые записи */
.bookings-table tbody tr.row-cancelled {
  opacity: 0.45;
}

/* Просроченные "новые" записи */
.bookings-table tbody tr.row-overdue {
  color: var(--text-secondary);
}

/* --- Сумма --- */
.bookings-table .cell-amount {
  font-weight: 600;
}

/* --- Пустое состояние --- */
.bookings-table-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  font-size: var(--genTextSizeH1);
}
```

- [ ] Импортировать в `src/main.ts` после остальных CSS-импортов:
  ```typescript
  import './assets/calendar-table.css'
  ```

---

## Chunk 3: Компонент BookingsTable.vue

### Task 5: Создать `BookingsTable.vue`

**Files:**
- Create: `maribulka-vue/src/components/calendar/BookingsTable.vue`

#### 5.1 Script setup — импорты и реактивные данные

- [ ] Создать файл `src/components/calendar/BookingsTable.vue`
- [ ] Добавить `<script setup lang="ts">` с импортами:
  ```typescript
  import { ref, computed, onMounted, watch } from 'vue'
  import {
    useVueTable,
    getCoreRowModel,
    getSortedRowModel,
    type ColumnDef,
    type SortingState,
    FlexRender
  } from '@tanstack/vue-table'
  import flatpickr from 'flatpickr'
  import { Russian } from 'flatpickr/dist/l10n/ru.js'
  import 'flatpickr/dist/flatpickr.min.css'
  import { useBookingsStore } from '../../stores/bookings'
  // Все модалки уже существуют в calendar/ — просто импортируем, ничего не создаём
  import BookingActionsModal from './BookingActionsModal.vue'
  import BookingFormModal from './BookingFormModal.vue'
  import AddPaymentModal from './AddPaymentModal.vue'
  import DeleteConfirmModal from './DeleteConfirmModal.vue'
  import DeliverBookingModal from './DeliverBookingModal.vue'
  import ViewBookingModal from './ViewBookingModal.vue'
  import CancelBookingModal from './CancelBookingModal.vue'
  import ConfirmSessionModal from './ConfirmSessionModal.vue'
  import RefundModal from './RefundModal.vue'
  ```

  > **Важно:** flatpickr импортируется из пакета, уже установленного в проекте. CSS flatpickr перезаписывается нашим `flatpickr.css` (он импортируется в `main.ts` после).

#### 5.2 Состояние периода с дефолтом текущий месяц

- [ ] Добавить вычисление дефолтных дат и ref-переменные:
  ```typescript
  const bookingsStore = useBookingsStore()

  // Дефолт: первый и последний день текущего месяца
  function getDefaultStart(): Date {
    const d = new Date()
    return new Date(d.getFullYear(), d.getMonth(), 1)
  }
  function getDefaultEnd(): Date {
    const d = new Date()
    return new Date(d.getFullYear(), d.getMonth() + 1, 0)
  }

  const periodStart = ref<Date>(getDefaultStart())
  const periodEnd = ref<Date>(getDefaultEnd())

  // Форматирование для API (YYYY-MM-DD)
  function formatForApi(date: Date): string {
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
  }
  ```

#### 5.3 Refs для flatpickr-инпутов

- [ ] Добавить template refs и инициализацию flatpickr:
  ```typescript
  const startInputRef = ref<HTMLInputElement | null>(null)
  const endInputRef = ref<HTMLInputElement | null>(null)

  onMounted(() => {
    if (startInputRef.value) {
      flatpickr(startInputRef.value, {
        locale: Russian,
        dateFormat: 'd.m.Y',
        defaultDate: periodStart.value,
        onChange: (dates) => {
          if (dates[0]) periodStart.value = dates[0]
        }
      })
    }
    if (endInputRef.value) {
      flatpickr(endInputRef.value, {
        locale: Russian,
        dateFormat: 'd.m.Y',
        defaultDate: periodEnd.value,
        onChange: (dates) => {
          if (dates[0]) periodEnd.value = dates[0]
        }
      })
    }

    // Загрузка при маунте
    bookingsStore.fetchBookings(formatForApi(periodStart.value), formatForApi(periodEnd.value))
  })

  // Перезагрузка при смене периода
  watch([periodStart, periodEnd], ([start, end]) => {
    bookingsStore.fetchBookings(formatForApi(start), formatForApi(end))
  })
  ```

#### 5.4 Хелперы форматирования (логику смотреть в архиве: `accounting/BookingsCalendar.vue`)

- [ ] Перенести функции форматирования:
  ```typescript
  function formatDate(dateString: string): string {
    if (!dateString) return ''
    const datePart = dateString.split('T')[0]?.split(' ')[0]
    if (!datePart) return ''
    const parts = datePart.split('-')
    const year = parts[0] || ''
    const month = parts[1] || ''
    const day = parts[2] || ''
    return `${day}.${month}.${year.slice(-2)}`
  }

  function formatDateTime(dateString: string): string {
    if (!dateString) return ''
    const [datePart, timePart] = dateString.split(' ')
    if (!datePart) return ''
    const parts = datePart.split('-')
    const year = parts[0] || ''
    const month = parts[1] || ''
    const day = parts[2] || ''
    const time = timePart?.substring(0, 5) || ''
    return time ? `${day}.${month}.${year.slice(-2)} ${time}` : `${day}.${month}.${year.slice(-2)}`
  }

  function getStatusText(status: string): string {
    const map: Record<string, string> = {
      new: '🔵 Новый',
      in_progress: '🟠 В работе',
      completed: '🟢 Выполнен',
      completed_partially: '🟡 Выполнен частично',
      not_completed: '🟤 Не выполнен',
      cancelled_by_client: '⚪ Отменён клиентом',
      cancelled_by_photographer: '⚪ Отменён фотографом',
      client_no_show: '⚪ Клиент не пришёл',
    }
    return map[status] ?? status
  }

  function getPaymentStatusText(status: string): string {
    const map: Record<string, string> = {
      unpaid: '🔴 Не оплачено',
      partially_paid: '🟡 Частично',
      fully_paid: '🟢 Оплачено',
    }
    return map[status] ?? status
  }
  ```

#### 5.5 Определение колонок (13 штук из старого файла)

- [ ] Добавить `columns`:
  ```typescript
  const columns: ColumnDef<any>[] = [
    {
      accessorKey: 'order_number',
      header: 'ID заказа',
      cell: ({ getValue }) => getValue() || '—',
    },
    {
      accessorKey: 'booking_date',
      header: 'Дата создания',
      cell: ({ getValue }) => formatDate(getValue() as string),
    },
    {
      accessorKey: 'shooting_date',
      header: 'Дата съёмки',
      cell: ({ getValue }) => formatDateTime(getValue() as string),
    },
    {
      accessorKey: 'delivery_date',
      header: 'Дата выдачи',
      cell: ({ getValue }) => formatDate(getValue() as string),
    },
    {
      accessorKey: 'client_name',
      header: 'Клиент',
    },
    {
      accessorKey: 'phone',
      header: 'Телефон',
    },
    {
      accessorKey: 'shooting_type_name',
      header: 'Тип съёмки',
    },
    {
      accessorKey: 'quantity',
      header: 'Кол-во',
    },
    {
      accessorKey: 'base_price',
      header: 'Стоимость',
      cell: ({ getValue }) => {
        const v = getValue()
        return v ? `${Math.round(parseFloat(v as string))}` : '—'
      },
    },
    {
      accessorKey: 'promo_discount_percent',
      header: 'Скидка',
      cell: ({ row }) => {
        const promotionId = row.original.promotion_id
        const discountPercent = parseFloat(row.original.promo_discount_percent) || 0
        return promotionId && discountPercent > 0 ? `-${discountPercent}%` : '—'
      },
    },
    {
      accessorKey: 'total_amount',
      header: 'Сумма ₽',
      cell: ({ getValue }) => `${Math.round(parseFloat(getValue() as string))}`,
    },
    {
      accessorKey: 'payment_status',
      header: 'Статус оплаты',
      cell: ({ getValue }) => getPaymentStatusText(getValue() as string),
    },
    {
      accessorKey: 'status',
      header: 'Статус записи',
      cell: ({ getValue }) => getStatusText(getValue() as string),
    },
  ]
  ```

#### 5.6 TanStack Table instance + выделение строки

- [ ] Добавить state и инстанс таблицы:
  ```typescript
  const sorting = ref<SortingState>([{ id: 'order_number', desc: true }])
  const selectedIndex = ref<number | null>(null)

  const table = useVueTable({
    get data() { return bookingsStore.bookings },
    columns,
    state: {
      get sorting() { return sorting.value },
    },
    onSortingChange: updater => {
      sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
  })

  const selectedBooking = computed(() => {
    if (selectedIndex.value === null) return null
    return bookingsStore.bookings[selectedIndex.value] ?? null
  })

  function selectRow(index: number) {
    selectedIndex.value = selectedIndex.value === index ? null : index
  }
  ```

#### 5.7 Модальные окна — state и обработчики

- [ ] Добавить state модалок и обработчики (аналогично CalendarPanel.vue):
  ```typescript
  const showActionsModal = ref(false)
  const showAddModal = ref(false)
  const showEditModal = ref(false)
  const showPaymentModal = ref(false)
  const showDeleteModal = ref(false)
  const showDeliverModal = ref(false)
  const showViewModal = ref(false)
  const showCancelModal = ref(false)
  const showConfirmSessionModal = ref(false)
  const showRefundModal = ref(false)

  function openActions() {
    if (selectedBooking.value) showActionsModal.value = true
  }

  function handleView() { showActionsModal.value = false; showViewModal.value = true }
  function handleEdit() { showActionsModal.value = false; showEditModal.value = true }
  function handlePayment() { showActionsModal.value = false; showPaymentModal.value = true }
  function handleDelete() { showActionsModal.value = false; showDeleteModal.value = true }
  function handleConfirmSession() { showActionsModal.value = false; showConfirmSessionModal.value = true }
  function handleCancel() { showActionsModal.value = false; showCancelModal.value = true }
  function handleDeliver() { showActionsModal.value = false; showDeliverModal.value = true }
  function handleRefund() { showActionsModal.value = false; showPaymentModal.value = true }

  function closeModal() {
    showActionsModal.value = false
    showAddModal.value = false
    showEditModal.value = false
    showPaymentModal.value = false
    showDeleteModal.value = false
    showDeliverModal.value = false
    showViewModal.value = false
    showCancelModal.value = false
    showConfirmSessionModal.value = false
    selectedIndex.value = null
  }
  ```

#### 5.8 Вспомогательные вычисления для CSS-классов строк

- [ ] Добавить хелпер для классов строки:
  ```typescript
  const CANCELLED_STATUSES = new Set([
    'cancelled_by_client',
    'cancelled_by_photographer',
    'client_no_show',
  ])

  function getRowClass(booking: any, rowIndex: number): Record<string, boolean> {
    return {
      'row-selected': selectedIndex.value === rowIndex,
      'row-cancelled': CANCELLED_STATUSES.has(booking.status),
      'row-overdue': booking.status === 'new' && new Date(booking.shooting_date) < new Date(),
    }
  }
  ```

#### 5.9 Template

- [ ] Добавить `<template>`:
  ```html
  <template>
    <div class="padGlass padGlass-work bookings-table-panel">

      <!-- Фильтр диапазона дат -->
      <div class="bookings-table-filter">
        <span class="bookings-table-filter-label">С:</span>
        <input ref="startInputRef" class="bookings-table-filter-input" placeholder="дд.мм.гггг" readonly />
        <span class="bookings-table-filter-label">По:</span>
        <input ref="endInputRef" class="bookings-table-filter-input" placeholder="дд.мм.гггг" readonly />
      </div>

      <!-- Таблица -->
      <div v-if="bookingsStore.bookings.length > 0" class="bookings-table-scroll">
        <table class="bookings-table">
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
              :class="getRowClass(row.original, rowIndex)"
              @click="selectRow(rowIndex); openActions()"
            >
              <td v-for="cell in row.getVisibleCells()" :key="cell.id"
                :class="{ 'cell-amount': cell.column.id === 'total_amount' }"
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

      <!-- Пустое состояние -->
      <div v-else class="bookings-table-empty">
        📭 Нет записей за выбранный период
      </div>

      <!-- Модальные окна -->
      <BookingActionsModal
        v-if="showActionsModal"
        :booking="selectedBooking"
        @close="closeModal"
        @view="handleView"
        @edit="handleEdit"
        @payment="handlePayment"
        @delete="handleDelete"
        @confirmSession="handleConfirmSession"
        @cancel="handleCancel"
        @deliver="handleDeliver"
        @refund="handleRefund"
      />
      <BookingFormModal mode="add" :isVisible="showAddModal" @close="closeModal" />
      <BookingFormModal mode="edit" :isVisible="showEditModal" :booking="selectedBooking" @close="closeModal" />
      <AddPaymentModal :isVisible="showPaymentModal" :booking="selectedBooking" @close="closeModal" />
      <DeleteConfirmModal :isVisible="showDeleteModal" :booking="selectedBooking" @close="closeModal" />
      <ConfirmSessionModal :isVisible="showConfirmSessionModal" :booking="selectedBooking" @close="closeModal" @openPayment="handlePayment" />
      <DeliverBookingModal :isVisible="showDeliverModal" :booking="selectedBooking" @close="closeModal" @openPayment="handlePayment" />
      <ViewBookingModal :isVisible="showViewModal" :booking="selectedBooking" @close="closeModal" />
      <CancelBookingModal :isVisible="showCancelModal" :booking="selectedBooking" @close="closeModal" />
    </div>
  </template>
  ```

---

## Chunk 4: Финальная проверка

### Task 6: Проверить сборку

- [ ] Выполнить сборку:
  ```bash
  cd maribulka-vue && npm run build
  ```
  Ожидаемый результат: `✓ built in ...` без TypeScript-ошибок.

- [ ] Запустить dev-сервер:
  ```bash
  npm run dev
  ```

- [ ] Открыть http://localhost:5173, войти как admin (login: `admin`, pass: `123`)
- [ ] Открыть LaunchPad → нажать "Записи" → убедиться что открывается панель с таблицей
- [ ] Проверить дефолтный период (текущий месяц)
- [ ] Изменить период — убедиться что данные перезагружаются
- [ ] Нажать на строку → открывается `BookingActionsModal`
- [ ] Проверить сортировку по колонкам (нажать заголовок)
- [ ] Проверить пустое состояние: выбрать период без записей

---

## Итог: затронутые файлы

| Файл | Действие |
|------|---------|
| `maribulka-vue/src/stores/navigation.ts` | Modify — добавить `'bookings'` в `PageType` |
| `maribulka-vue/src/components/launchpad/LaunchPad.vue` | Modify — функция `openBookings()` + привязка к кнопке |
| `maribulka-vue/src/App.vue` | Modify — импорт + `v-if` для страницы bookings |
| `maribulka-vue/src/main.ts` | Modify — импорт `calendar-table.css` |
| `maribulka-vue/src/assets/calendar-table.css` | Create — стили таблицы (glass-стиль) |
| `maribulka-vue/src/components/calendar/BookingsTable.vue` | Create — компонент таблицы записей |
