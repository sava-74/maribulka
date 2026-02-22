# Finance - Финансовая система

## 📋 Обзор

Полная система управления финансами фотостудии, включающая учёт доходов, расходов и аналитику.

**Дата внедрения:** 18-20 февраля 2026

---

## 🗄️ База данных

### Таблица expenses

```sql
CREATE TABLE expenses (
  id INT PRIMARY KEY AUTO_INCREMENT,
  date DATE NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  category_id INT NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES expense_categories(id)
);
```

### Таблица expense_categories

```sql
CREATE TABLE expense_categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  is_active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Важная категория:**
- **ID=2** "Возврат средств" - используется для специальной логики автозаполнения

### Таблица income

```sql
CREATE TABLE income (
  id INT PRIMARY KEY AUTO_INCREMENT,
  booking_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  category VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id)
);
```

---

## 🔌 API Endpoints

### Расходы (expenses.php)

**GET /api/expenses.php?month=YYYY-MM**
- Получить расходы за месяц
- Пример: `/api/expenses.php?month=2026-02`
- Response: массив расходов с category_name

**POST /api/expenses.php**
- Создать расход
- Body:
```json
{
  "date": "2026-02-22",
  "amount": 5000,
  "category_id": 1,
  "description": "Зарплата помощника"
}
```

**PUT /api/expenses.php**
- Обновить расход
- Body: те же поля + `id`

**DELETE /api/expenses.php?id=X**
- Удалить расход

### Категории расходов (expense-categories.php)

**GET /api/expense-categories.php**
- Список активных категорий (is_active = 1)

**GET /api/expense-categories.php?check_relations=1&id=X**
- Проверка связей перед удалением
- Response: `{ "has_relations": true/false, "count": N }`

**POST /api/expense-categories.php**
- Создать категорию
- Body: `{ "name": "Название", "is_active": 1 }`

**PUT /api/expense-categories.php**
- Обновить категорию

**DELETE /api/expense-categories.php?id=X**
- Удалить категорию (только если нет связей)

### Доходы (income.php)

**GET /api/income.php?month=YYYY-MM**
- Платежи за месяц
- Response: массив платежей с данными заказа (order_number, client_name, payment_status)

**GET /api/income.php?booking_id=X**
- Платежи по конкретному заказу

**POST /api/income.php**
- Создать платёж
- Body:
```json
{
  "booking_id": 123,
  "amount": 10000,
  "payment_date": "2026-02-22",
  "category": "Основной платёж"
}
```

**DELETE /api/income.php?id=X**
- Удалить платёж

### Специальные endpoints в bookings.php

**GET /api/bookings.php?action=refundable**
- Заказы доступные для возврата средств
- Условие: `(status = 'new' OR status = 'failed') AND paid_amount > 0`
- Response: массив заказов с полной информацией

**GET /api/bookings.php?action=income_by_type&month=YYYY-MM**
- Доход по типам съёмок за месяц
- Условие: `payment_status = 'fully_paid' AND (status = 'delivered' OR status = 'completed')`
- Response:
```json
[
  {
    "shooting_type_name": "Портретная",
    "count": 15,
    "total": 150000
  }
]
```

---

## 💾 Pinia Store: finance.ts

### State

```typescript
const income = ref<Income[]>([])              // Платежи за текущий месяц
const incomeByBooking = ref<Income[]>([])     // Платежи по заказу
const expenses = ref<Expense[]>([])           // Расходы за текущий месяц
const refundableBookings = ref<Booking[]>([]) // Заказы для возврата
const loadingIncome = ref(false)
const loadingExpenses = ref(false)
const currentMonth = ref('')                  // YYYY-MM для доходов
const currentExpenseMonth = ref('')           // YYYY-MM для расходов
```

### Computed

```typescript
const totalIncome = computed(() =>
  income.value.reduce((sum, item) => sum + parseFloat(item.amount), 0)
)

const totalExpenses = computed(() =>
  expenses.value.reduce((sum, item) => sum + parseFloat(item.amount), 0)
)

const profit = computed(() => totalIncome.value - totalExpenses.value)

const profitability = computed(() => {
  if (totalIncome.value === 0) return 0
  return (profit.value / totalIncome.value) * 100
})
```

### Actions

**fetchIncome(month?: string)**
- Загрузить платежи за месяц
- Если month не указан - текущий месяц

**fetchExpenses(month?: string)**
- Загрузить расходы за месяц

**fetchIncomeByBooking(bookingId: number)**
- Загрузить платежи по заказу

**fetchRefundableBookings()**
- Загрузить заказы для возврата средств

**fetchIncomeByShootingType(month?: string)**
- Загрузить статистику дохода по типам съёмок

**createExpense(data: ExpenseData)**
- Создать расход
- Обновляет expenses после создания

**updateExpense(data: ExpenseData)**
- Обновить расход

**deleteExpense(id: number)**
- Удалить расход

**deleteIncome(id: number)**
- Удалить платёж

**setCurrentMonth(month: string)**
- Установить текущий месяц для доходов

**setCurrentExpenseMonth(month: string)**
- Установить текущий месяц для расходов

---

## 🎨 Компоненты

### Таблицы

#### ExpensesTable.vue

**Функционал:**
- TanStack Table для отображения расходов
- Колонки: дата, сумма, категория, описание
- Фильтрация по месяцам (input[type="month"])
- Сортировка (по умолчанию по дате DESC)
- CRUD операции: View, Add, Edit, Delete

**Кнопки:**
- Обновить (mdilRefresh)
- Фильтры (mdilMagnify) - открывает фильтр по месяцам
- Добавить (mdilPlus)
- Просмотр (mdilEye) - disabled если не выбран элемент
- Редактировать (mdiFileEditOutline) - disabled если не выбран элемент
- Удалить (mdilDelete) - disabled если не выбран элемент

**Форматирование:**
- Дата: DD.MM.YY
- Сумма: без знака ₽ в таблице (только число)

#### ExpenseCategoriesTable.vue

**Функционал:**
- Справочник категорий расходов
- Колонки: ID, название, статус
- Статус: "Активна" / "Неактивна"
- Проверка связей перед удалением

**Особенности:**
- Нельзя удалить категорию, если есть расходы
- При попытке удаления показывается количество связанных расходов

#### IncomeTable.vue

**Функционал:**
- Таблица платежей
- Колонки: дата, ID заказа, клиент, сумма, статус платежа
- Форматирование ID заказа: МБ{id}{magicNumber}{year}
- Форматирование статуса оплаты (цветные метки)
- Операции: View, Delete

**Форматирование:**
- Дата: DD.MM.YY
- ID заказа: МБ1234 (кликабельная ссылка для перехода к заказу)
- Статус платежа: зелёный (полностью оплачено), жёлтый (частично), красный (не оплачено)

### Модальные окна расходов

#### AddExpenseModal.vue

**Поля:**
- Дата (input[type="date"])
- Сумма (input[type="number"])
- Категория (select из expense_categories)
- Описание (textarea)

**Логика возвратов средств:**
```typescript
// Если выбрана категория ID=2 "Возврат средств"
if (categoryId === 2) {
  // Загрузить refundableBookings
  await financeStore.fetchRefundableBookings()

  // Показать селект заказов
  showBookingSelect = true

  // При выборе заказа:
  amount.value = selectedBooking.paid_amount
  description.value = `${selectedBooking.order_number} - ${selectedBooking.client_name}`
}

// При смене категории
watch(categoryId, (newCategoryId) => {
  if (newCategoryId !== 2) {
    // Сбросить выбор заказа
    selectedBookingId.value = null
    amount.value = 0
    description.value = ''
  }
})
```

**Валидация:**
- Дата обязательна
- Сумма > 0
- Категория обязательна
- Для возвратов - заказ обязателен

#### EditExpenseModal.vue

**Аналогично AddExpenseModal, но:**
- Загружает существующие данные расхода
- Обновляет существующий расход вместо создания

#### ViewExpenseModal.vue

**Только просмотр:**
- Дата (DD.MM.YYYY)
- Сумма (с ₽)
- Категория (название)
- Описание (текст)
- Кнопка "Закрыть"

#### AddExpenseCategoryModal.vue

**Поля:**
- Название (input[type="text"])
- Статус активности (checkbox)

#### EditExpenseCategoryModal.vue

**Аналогично Add, но для редактирования**

### Другие компоненты

#### AddPaymentModal.vue

**Функционал:**
- Добавление платежа к заказу
- Автоматический расчёт долга
- Обновление статуса оплаты заказа

**Поля:**
- Сумма платежа
- Дата платежа
- Категория платежа (опционально)

#### ViewIncomeModal.vue

**Только просмотр платежа:**
- ID заказа
- Клиент
- Сумма
- Дата платежа
- Категория

---

## 📊 Финансовые отчёты (Reports.vue)

### Компонент Reports.vue

**Путь:** `components/accounting/Reports.vue`

**CSS:** `assets/reports.css`

### Структура

**1. Sticky панель фильтров**
```css
.reports-header-panel {
  position: sticky;
  top: 86px;
  z-index: 100;
}
```

Содержит:
- Селект периода (месяц/квартал/год)
- Input месяца (type="month")

**2. Метрики (4 карточки)**
```
┌─────────────┬─────────────┬─────────────┬──────────────────┐
│   Доход     │   Расход    │  Прибыль    │  Рентабельность  │
│  150000 ₽   │  50000 ₽    │ 100000 ₽    │      66.7%       │
└─────────────┴─────────────┴─────────────┴──────────────────┘
```

Цвета:
- Доход: зелёный (#4ade80)
- Расход: красный (#f87171)
- Прибыль: зелёный/красный в зависимости от знака
- Рентабельность: зелёный/красный

**3. Диаграммы (2 графика)**

#### Диаграмма 1: Расходы по категориям

```javascript
Chart.js - Horizontal Bar Chart
```

Данные:
- Первая строка: "Всего" (серый цвет #6b7280)
- Остальные: категории (цветная палитра)

Кастомный плагин `barLabels`:
```javascript
{
  id: 'barLabels',
  afterDatasetDraw(chart) {
    // Отображает сумму справа от бара
    ctx.fillText(amount + ' ₽', x + 10, y)
  }
}
```

Палитра цветов:
```javascript
const chartColors = [
  '#ef4444', '#f97316', '#f59e0b', '#84cc16',
  '#10b981', '#14b8a6', '#06b6d4', '#3b82f6',
  '#6366f1', '#8b5cf6', '#a855f7', '#ec4899'
]
```

#### Диаграмма 2: Доход по источникам (типам съёмок)

```javascript
Chart.js - Horizontal Bar Chart
```

Данные из API `bookings.php?action=income_by_type`

Формат метки: "{count} - {total} ₽"

Кастомный плагин `incomeBarLabels`:
```javascript
{
  id: 'incomeBarLabels',
  afterDatasetDraw(chart) {
    // Отображает "{count} - {amount} ₽"
    ctx.fillText(label, x + 10, y)
  }
}
```

### Фильтрация по периодам

**Режимы:**
- month - текущий месяц
- quarter - квартал (3 месяца)
- year - год (12 месяцев)

**Логика filterByPeriod():**

```typescript
function filterByPeriod(data: any[], dateField: string) {
  const selectedDate = new Date(selectedDateValue + '-01')

  if (selectedPeriod === 'month') {
    // YYYY-MM
    return data.filter(item =>
      item[dateField].startsWith(selectedDateValue)
    )
  }

  if (selectedPeriod === 'quarter') {
    // Вычислить квартал (0-3)
    const month = selectedDate.getMonth()
    const quarter = Math.floor(month / 3)
    const startMonth = quarter * 3
    const endMonth = startMonth + 3

    return data.filter(item => {
      const itemDate = new Date(item[dateField])
      const itemMonth = itemDate.getMonth()
      return itemDate.getFullYear() === selectedDate.getFullYear() &&
             itemMonth >= startMonth && itemMonth < endMonth
    })
  }

  if (selectedPeriod === 'year') {
    // YYYY
    const year = selectedDate.getFullYear()
    return data.filter(item =>
      item[dateField].startsWith(year.toString())
    )
  }
}
```

### Computed метрики

```typescript
const expensesByCategory = computed(() => {
  // Группировка расходов по категориям
  const filtered = filterByPeriod(financeStore.expenses, 'date')
  const grouped = {}

  filtered.forEach(expense => {
    if (!grouped[expense.category_name]) {
      grouped[expense.category_name] = 0
    }
    grouped[expense.category_name] += parseFloat(expense.amount)
  })

  return grouped
})

const periodIncomeTotal = computed(() => {
  const filtered = filterByPeriod(financeStore.income, 'payment_date')
  return filtered.reduce((sum, item) => sum + parseFloat(item.amount), 0)
})

const periodExpensesTotal = computed(() => {
  const filtered = filterByPeriod(financeStore.expenses, 'date')
  return filtered.reduce((sum, item) => sum + parseFloat(item.amount), 0)
})

const periodProfit = computed(() =>
  periodIncomeTotal.value - periodExpensesTotal.value
)

const profitability = computed(() => {
  if (periodIncomeTotal.value === 0) return 0
  return (periodProfit.value / periodIncomeTotal.value) * 100
})
```

### Инициализация диаграмм

```typescript
import {
  Chart,
  BarController,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

Chart.register(
  BarController,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
)

onMounted(() => {
  initExpensesChart()
  initIncomeChart()
})

function initExpensesChart() {
  const canvas = expensesChartCanvas.value
  const ctx = canvas.getContext('2d')

  expensesChart.value = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: [...],
      datasets: [{
        label: 'Сумма',
        data: [...],
        backgroundColor: [...],
        indexAxis: 'y'  // Горизонтальный bar
      }]
    },
    options: {
      indexAxis: 'y',
      plugins: {
        legend: { display: false },
        barLabels: {}  // Кастомный плагин
      }
    },
    plugins: [barLabelsPlugin]
  })
}
```

### Обновление диаграмм

```typescript
watch([expensesByCategory, incomeByShootingType], () => {
  updateExpensesChart()
  updateIncomeChart()
})

function updateExpensesChart() {
  if (!expensesChart.value) return

  const data = expensesByCategory.value
  const labels = Object.keys(data)
  const values = Object.values(data)
  const total = values.reduce((sum, v) => sum + v, 0)

  // Добавить "Всего" в начало
  expensesChart.value.data.labels = ['Всего', ...labels]
  expensesChart.value.data.datasets[0].data = [total, ...values]
  expensesChart.value.data.datasets[0].backgroundColor = [
    '#6b7280',  // Серый для "Всего"
    ...labels.map((_, i) => chartColors[i % chartColors.length])
  ]

  expensesChart.value.update()
}
```

---

## 🎯 Логика возвратов средств

### Категория "Возврат средств" (ID=2)

**Особенность:** Специальная обработка при создании расхода.

### Алгоритм

**1. При загрузке AddExpenseModal:**
```typescript
onMounted(async () => {
  await referencesStore.fetchExpenseCategories()
})
```

**2. При выборе категории:**
```typescript
watch(() => selectedCategoryId.value, async (newCategoryId) => {
  if (newCategoryId === 2) {
    // Загрузить заказы для возврата
    await financeStore.fetchRefundableBookings()
    showBookingSelect.value = true
  } else {
    // Скрыть селект и сбросить поля
    showBookingSelect.value = false
    selectedBookingId.value = null
    amount.value = 0
    description.value = ''
  }
})
```

**3. При выборе заказа:**
```typescript
watch(() => selectedBookingId.value, (bookingId) => {
  if (!bookingId) return

  const booking = financeStore.refundableBookings.find(b => b.id === bookingId)
  if (!booking) return

  // Автозаполнение
  amount.value = booking.paid_amount
  description.value = `${booking.order_number} - ${booking.client_name}`
})
```

**4. При сохранении:**
```typescript
async function handleSave() {
  // Валидация
  if (selectedCategoryId.value === 2 && !selectedBookingId.value) {
    showAlert('Ошибка', 'Выберите заказ для возврата')
    return
  }

  const data = {
    date: date.value,
    amount: amount.value,
    category_id: selectedCategoryId.value,
    description: description.value
  }

  await financeStore.createExpense(data)
  emit('close')
}
```

### API endpoint для возвратов

**GET /api/bookings.php?action=refundable**

SQL:
```sql
SELECT *
FROM bookings
WHERE (status = 'new' OR status = 'failed')
  AND paid_amount > 0
ORDER BY created_at DESC
```

Response:
```json
[
  {
    "id": 123,
    "order_number": "МБ1234",
    "client_name": "Иван Иванов",
    "paid_amount": "5000.00",
    "status": "failed",
    ...
  }
]
```

---

## 🎨 Стили (reports.css)

### Основные классы

**.reports**
- Основной контейнер отчётов
- padding: 20px

**.reports-header-panel**
- Sticky панель с фильтрами
- position: sticky
- top: 86px
- z-index: 100
- background: var(--glass-bg)
- border: 1px solid var(--generalColor)
- border-radius: var(--panelRadius)

**.filter-controls**
- Flexbox контейнер для фильтров
- display: flex
- gap: 15px
- align-items: center

**.glass-select, .glass-input**
- Стили для селектов и инпутов
- background: rgba(255, 255, 255, 0.6)
- border: 1px solid var(--generalColor)
- padding: 8px 12px
- border-radius: 6px

**.stats-grid**
- Grid для 4 метрик
- display: grid
- grid-template-columns: repeat(4, 1fr)
- gap: 15px

**.stat-card**
- Карточка метрики
- background: var(--glass-bg)
- padding: 20px
- border-radius: var(--panelRadius)
- text-align: center

**.analysis-grid**
- Grid для 2 диаграмм
- display: grid
- grid-template-columns: repeat(2, 1fr)
- gap: 20px

**.analysis-card**
- Карточка диаграммы
- background: var(--glass-bg)
- padding: 20px
- height: 250px

**.chart-container**
- Контейнер canvas
- height: 150px
- position: relative

### Мобильная адаптация

```css
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .analysis-grid {
    grid-template-columns: 1fr;
  }

  .filter-controls {
    flex-direction: column;
    align-items: stretch;
  }
}
```

---

## 📝 Примеры использования

### Создание расхода

```typescript
// В компоненте
import { useFinanceStore } from '@/stores/finance'

const financeStore = useFinanceStore()

const expenseData = {
  date: '2026-02-22',
  amount: 5000,
  category_id: 1,
  description: 'Зарплата помощника'
}

await financeStore.createExpense(expenseData)
```

### Загрузка расходов за месяц

```typescript
// За текущий месяц
await financeStore.fetchExpenses()

// За конкретный месяц
await financeStore.fetchExpenses('2026-02')
```

### Создание платежа

```typescript
const paymentData = {
  booking_id: 123,
  amount: 10000,
  payment_date: '2026-02-22',
  category: 'Основной платёж'
}

await fetch('/api/income.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(paymentData)
})
```

### Фильтрация расходов по периоду

```typescript
import { filterByPeriod } from '@/utils/filterHelpers'

const expenses = financeStore.expenses
const filtered = filterByPeriod(expenses, 'date', 'quarter', '2026-02')
```

---

## ⚠️ Известные ограничения

1. **Нет кэширования диаграмм** - Chart.js пересоздаёт графики при каждом изменении данных
2. **Нет пагинации** - все данные загружаются за раз
3. **Нет экспорта** - невозможно экспортировать отчёты в Excel/PDF
4. **Нет сравнения периодов** - нельзя сравнить текущий месяц с предыдущим
5. **Горизонтальные диаграммы** - могут быть узкими на мобилке при большом количестве категорий

---

## 🔮 Будущие улучшения

- [ ] Экспорт отчётов в Excel/PDF
- [ ] Сравнение периодов (текущий vs предыдущий)
- [ ] Прогнозирование расходов
- [ ] Графики динамики (line chart)
- [ ] Круговые диаграммы (pie chart)
- [ ] Фильтрация диаграмм (клик по категории)
- [ ] Детализация при клике на элемент диаграммы
- [ ] Кэширование данных диаграмм
- [ ] Responsive canvas (адаптивная высота)
- [ ] Анимация при обновлении диаграмм

---

## 🔗 Связанные файлы

- [architecture.md](architecture.md) - архитектура проекта
- [changelog.md](changelog.md) - история изменений
- [patterns.md](patterns.md) - паттерны кода
- [expenses-plan.md](expenses-plan.md) - план работ (устарел, реализовано)
