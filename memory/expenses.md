# Расходы и возвраты средств

## 📋 Общая информация

Модуль расходов состоит из двух основных частей:
1. **Таблица расходов** - учёт всех расходов студии
2. **Справочник категорий расходов** - управление категориями

---

## 🗂️ Категории расходов

### Структура БД (таблица `expense_categories`)

| ID | Название | Описание |
|----|----------|----------|
| 1 | Аренда студии | Основная категория |
| 2 | Возврат средств | **Особая логика!** |
| 3 | Оборудование | Основная категория |
| 4 | Программное обеспечение | Основная категория |
| 5 | Прочее | Основная категория |
| 6 | Транспорт | Основная категория |

### Защита системных категорий

**Категории ID 1-6 защищены:**
- ❌ Нельзя удалить
- ❌ Нельзя редактировать
- ✅ Кнопки "Удалить" и "Редактировать" автоматически становятся неактивными

**Проверка в коде:**
```typescript
const canDeleteSelected = computed(() => {
  if (!selectedExpenseCategory.value) return false
  return selectedExpenseCategory.value.id > 6
})
```

**Файлы:**
- [ExpenseCategoriesTable.vue](maribulka-vue/src/components/accounting/ExpenseCategoriesTable.vue)

---

## 💰 Возврат средств (ID = 2)

### Особая логика категории "Возврат средств"

Когда пользователь выбирает категорию ID=2 в модалке расхода:

#### 1. Появляется поле "Заказ" (обязательное)
- Выпадающий список со всеми доступными для возврата заказами
- Формат: `МБ123-2026 - Иванов Иван`
- Только заказы со статусом `new` или `failed` + есть оплата

#### 2. Автозаполнение при выборе заказа
- **Сумма** → автоматически заполняется `paid_amount` из заказа
- **Описание** → автоматически заполняется `{order_number} - {client_name}`
- Пользователь может изменить сумму (для частичного возврата)

#### 3. Валидация
- Для категории "Возврат средств" поле "Заказ" обязательно
- Без выбранного заказа сохранение невозможно

### Backend API

**Endpoint:** `GET /api/bookings.php?action=refundable`

```php
$stmt = $db->query("
    SELECT
        b.id,
        b.order_number,
        b.paid_amount,
        c.name as client_name
    FROM bookings b
    LEFT JOIN clients c ON b.client_id = c.id
    WHERE (b.status = 'new' OR b.status = 'failed')
    AND b.payment_status != 'unpaid'
    AND b.paid_amount > 0
    ORDER BY b.shooting_date DESC
");
```

**Файл:** [api/bookings.php:53-69](api/bookings.php#L53-L69)

### Frontend Store

**Store:** [finance.ts](maribulka-vue/src/stores/finance.ts)

```typescript
const refundableBookings = ref<any[]>([])

async function fetchRefundableBookings() {
  try {
    const response = await fetch(`${API_URL}/bookings.php?action=refundable`)
    refundableBookings.value = await response.json()
  } catch (error) {
    console.error('Ошибка загрузки заказов для возврата:', error)
  }
}
```

---

## 🔧 Модалки расходов

### AddExpenseModal.vue

**Логика для возврата:**

```typescript
// Проверка: выбрана ли категория "Возврат средств" (ID = 2)
const isRefundCategory = computed(() => {
  return parseInt(category.value) === 2
})

// Автоматическое заполнение суммы и описания при выборе заказа
watch(booking_id, (newBookingId) => {
  if (isRefundCategory.value && newBookingId) {
    const booking = financeStore.refundableBookings.find(b => b.id === parseInt(newBookingId))
    if (booking && booking.paid_amount) {
      amount.value = booking.paid_amount.toString()
      description.value = `${booking.order_number} - ${booking.client_name}`
    }
  }
})

// Сброс при смене категории
watch(category, (newCategory, oldCategory) => {
  if (oldCategory && newCategory !== oldCategory) {
    booking_id.value = ''
    if (isRefundCategory.value) {
      amount.value = ''
      description.value = ''
    }
  }
})
```

**Template:**
```vue
<!-- Поле ID заказа - только для категории "Возврат средств" -->
<div v-if="isRefundCategory" class="input-field">
  <label class="input-label">Заказ: <span class="required">*</span></label>
  <select class="modal-input" v-model="booking_id">
    <option value="">Выберите заказ</option>
    <option
      v-for="booking in financeStore.refundableBookings"
      :key="booking.id"
      :value="booking.id"
    >
      {{ booking.order_number }} - {{ booking.client_name }}
    </option>
  </select>
</div>
```

**Файл:** [AddExpenseModal.vue](maribulka-vue/src/components/accounting/AddExpenseModal.vue)

---

### EditExpenseModal.vue

**Отличия от AddExpenseModal:**

1. ✅ Та же логика для возврата (isRefundCategory, watches)
2. ✅ Загрузка данных существующего расхода
3. ❌ **Поле "Категория" DISABLED** (нельзя изменить)
4. ❌ **Поле "Заказ" DISABLED** (нельзя изменить для возврата)
5. ✅ **Можно редактировать:** дату, сумму, описание

**Template:**
```vue
<!-- Категория неактивна -->
<select class="modal-input" v-model="category" disabled>
  ...
</select>

<!-- Заказ неактивен (для возврата) -->
<div v-if="isRefundCategory" class="input-field">
  <select class="modal-input" v-model="booking_id" disabled>
    ...
  </select>
</div>
```

**Файл:** [EditExpenseModal.vue](maribulka-vue/src/components/accounting/EditExpenseModal.vue)

---

## 📊 Таблица расходов

**Файл:** [ExpensesTable.vue](maribulka-vue/src/components/accounting/ExpensesTable.vue)

### Функционал:
- ✅ Отображение всех расходов за выбранный месяц
- ✅ Сортировка по дате (по умолчанию DESC)
- ✅ Фильтр по категории
- ✅ Выбор месяца (month picker)
- ✅ CRUD операции: добавить, редактировать, посмотреть, удалить
- ✅ Подсчёт итога за месяц

### Колонки таблицы:
1. **Дата** - формат DD.MM.YY
2. **Сумма ₽** - округлённая, без копеек
3. **Категория** - название категории
4. **Описание** - текст или "—"

---

## 🎯 Итоги реализации

### ✅ Что сделано:

1. **Справочник категорий расходов**
   - CRUD операции
   - Защита системных категорий (ID 1-6)
   - Проверка связей перед удалением

2. **Таблица расходов**
   - Полный CRUD
   - Фильтрация по месяцу и категории
   - Подсчёт итогов

3. **Возвраты средств (ID=2)**
   - Backend API для получения доступных заказов
   - Автозаполнение суммы и описания
   - Валидация обязательности поля "Заказ"
   - Защита от изменения категории и заказа при редактировании

4. **Модалки**
   - AddExpenseModal - полный функционал
   - EditExpenseModal - с ограничениями для возврата
   - ViewExpenseModal - просмотр расхода
   - Валидация всех полей
   - Кастомные AlertModal/ConfirmModal

### 🔗 Связанные файлы:

**Frontend:**
- `maribulka-vue/src/components/accounting/ExpensesTable.vue`
- `maribulka-vue/src/components/accounting/AddExpenseModal.vue`
- `maribulka-vue/src/components/accounting/EditExpenseModal.vue`
- `maribulka-vue/src/components/accounting/ViewExpenseModal.vue`
- `maribulka-vue/src/components/accounting/ExpenseCategoriesTable.vue`
- `maribulka-vue/src/components/accounting/AddExpenseCategoryModal.vue`
- `maribulka-vue/src/components/accounting/EditExpenseCategoryModal.vue`
- `maribulka-vue/src/stores/finance.ts`
- `maribulka-vue/src/stores/references.ts`

**Backend:**
- `api/expenses.php`
- `api/expense_categories.php`
- `api/bookings.php` (endpoint refundable)

**БД:**
- Таблица `expenses`
- Таблица `expense_categories`
- Таблица `bookings` (для возвратов)

---

## 🚀 Дата завершения

**17.02.2026** - Модуль расходов полностью завершён и протестирован!
