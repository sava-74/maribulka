# Patterns - Эталоны и паттерны кода

## Ключевые паттерны

### Модалки
- **НИКОГДА** не использовать `alert()` / `confirm()` / `prompt()`
- **ВСЕГДА** использовать кастомные модалки:
  - `AlertModal.vue` - уведомления
  - `ConfirmModal.vue` - подтверждения
  - Custom модалки для форм (Add/Edit/View)

### Иконки
- **ТОЛЬКО** @mdi/light-js (Material Design Icons Light)
- НЕ использовать другие библиотеки иконок

### ID заказа
```typescript
// Формат: МБ{id}{magicNumber}{year}
// magicNumber = день * месяц
// year из created_at (последние 2 цифры)

const day = new Date(created_at).getDate()
const month = new Date(created_at).getMonth() + 1
const year = new Date(created_at).getFullYear().toString().slice(-2)
const magicNumber = day * month
const orderId = `МБ${id}${magicNumber}${year}`
```

---

## Эталон: Структура таблицы

**Файлы-эталоны:** `ClientsTable.vue`, `PromotionsTable.vue`

### 🚨 КРИТИЧНО: БЕЗ ЧЕКБОКСОВ!

### Column definitions

```typescript
import { ColumnDef } from '@tanstack/vue-table'

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
    accessorKey: 'description',
    header: 'Описание'
  }
  // БЕЗ checkbox колонки!
]
```

### Table instance

```typescript
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  getFacetedUniqueValues
} from '@tanstack/vue-table'

const table = useVueTable({
  get data() { return store.data },
  columns,
  state: {
    get sorting() { return sorting.value },
    get rowSelection() { return rowSelection.value },
    get columnFilters() { return columnFilters.value }
  },
  enableRowSelection: true,
  enableMultiRowSelection: false, // Только одна строка!
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getFacetedUniqueValues: getFacetedUniqueValues()
})
```

### Кнопки управления

```typescript
import {
  mdilPlus,          // Добавить
  mdilEye,           // Просмотр
  mdiFileEditOutline, // Редактировать
  mdilDelete,        // Удалить
  mdilMagnify,       // Фильтры
  mdilRefresh        // Обновить
} from '@mdi/light-js'
```

**Состояния disabled:**
```vue
<button @click="showViewModal = true" :disabled="!hasSingleSelection">
  <!-- Просмотр -->
</button>

<button @click="showEditModal = true" :disabled="!hasSingleSelection">
  <!-- Редактировать -->
</button>

<button @click="handleDelete" :disabled="!hasSelectedRow">
  <!-- Удалить -->
</button>
```

### Выбор строк

```typescript
const selectedItems = computed(() =>
  table.getFilteredSelectedRowModel().rows.map(row => row.original)
)

const selectedItem = computed(() =>
  selectedItems.value.length === 1 ? selectedItems.value[0] : null
)

const hasSelectedRow = computed(() =>
  Object.keys(rowSelection.value).length > 0
)

const hasSingleSelection = computed(() =>
  Object.keys(rowSelection.value).length === 1
)
```

### Клик по строке

```typescript
function handleRowClick(row: any) {
  // Сбрасываем все выделения
  rowSelection.value = {}

  // Выделяем только эту строку
  rowSelection.value[row.id] = true
}
```

### Template таблицы

```vue
<table>
  <thead>
    <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
      <th v-for="header in headerGroup.headers" :key="header.id"
          @click="header.column.getToggleSortingHandler()?.($event)">
        {{ header.column.columnDef.header }}
        <span v-if="header.column.getIsSorted()">
          {{ header.column.getIsSorted() === 'asc' ? ' ↑' : ' ↓' }}
        </span>
      </th>
    </tr>
  </thead>
  <tbody>
    <tr v-for="row in table.getRowModel().rows" :key="row.id"
        :class="{ 'selected': row.getIsSelected() }"
        @click="handleRowClick(row)">
      <td v-for="cell in row.getVisibleCells()" :key="cell.id">
        {{ cell.getValue() }}
      </td>
    </tr>
  </tbody>
</table>
```

---

## Эталон: Структура модалки

### Template

```vue
<template>
  <div v-if="isVisible" class="modal-overlay" @click.self="close">
    <div class="modal-glass">
      <div class="modal-header">
        <h2>Заголовок</h2>
        <button class="glass-button" @click="close">
          <svg><!-- иконка закрытия --></svg>
        </button>
      </div>

      <div class="modal-body">
        <!-- Контент -->
      </div>

      <div class="modal-footer">
        <button class="glass-button-text" @click="handleSave">
          <svg><!-- иконка --></svg>
          <span>Сохранить</span>
        </button>
        <button class="glass-button-text" @click="close">
          <svg><!-- иконка --></svg>
          <span>Отмена</span>
        </button>
      </div>
    </div>
  </div>
</template>
```

### Script

```typescript
interface Props {
  isVisible: boolean
}

interface Emits {
  (e: 'close'): void
  (e: 'save', data: any): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

function close() {
  emit('close')
}

function handleSave() {
  // Валидация
  if (!isValid()) {
    showAlert('Ошибка', 'Заполните все поля')
    return
  }

  // Сохранение
  emit('save', formData)
  close()
}
```

---

## Эталон: Pinia Store

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useMyStore = defineStore('myStore', () => {
  // State
  const items = ref<Item[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const itemCount = computed(() => items.value.length)

  // Actions
  async function fetchItems() {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/items.php')
      if (!response.ok) throw new Error('Ошибка загрузки')

      items.value = await response.json()
    } catch (e) {
      error.value = e.message
      console.error('Ошибка:', e)
    } finally {
      loading.value = false
    }
  }

  async function createItem(data: CreateItemDto) {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/items.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })

      if (!response.ok) throw new Error('Ошибка создания')

      const newItem = await response.json()
      items.value.push(newItem)

      return newItem
    } catch (e) {
      error.value = e.message
      console.error('Ошибка:', e)
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    items,
    loading,
    error,
    itemCount,
    fetchItems,
    createItem
  }
})
```

---

## Эталон: PHP API

```php
<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Подключение к БД
require_once 'db.php';

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT * FROM items ORDER BY created_at DESC");
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($items);
            break;

        case 'create':
            $data = json_decode(file_get_contents('php://input'), true);

            // Валидация
            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указано название']);
                exit;
            }

            // Вставка
            $stmt = $pdo->prepare("INSERT INTO items (name, description) VALUES (?, ?)");
            $stmt->execute([$data['name'], $data['description'] ?? null]);

            // Возврат созданного объекта
            $id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode($item);
            break;

        case 'delete':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Неизвестное действие']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
```

---

## Форматирование даты

### Локальное форматирование (БЕЗ toISOString!)

```typescript
function formatDate(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}
```

### Формат ДД.ММ.ГГ

```typescript
function formatDateShort(dateString: string): string {
  const date = new Date(dateString)
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = String(date.getFullYear()).slice(-2)

  return `${day}.${month}.${year}`
}
```

### Формат ДД.ММ.ГГГГ

```typescript
function formatDateFull(dateString: string): string {
  const date = new Date(dateString)
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = date.getFullYear()

  return `${day}.${month}.${year}`
}
```
