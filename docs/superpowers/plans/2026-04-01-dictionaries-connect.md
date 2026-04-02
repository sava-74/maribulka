# План реализации: Подключение справочников (ФИНАЛЬНАЯ ВЕРСИЯ)

**Дата:** 1 апреля 2026
**Обновлено:** 2 апреля 2026
**Статус:** В работе — эталон salary_types готов
**Автор:** Архитектор Maribulka

---

## 1. Эталон справочника — `components/salaryTypes/`

> **ВАЖНО:** Эталоном для всех справочников является `salaryTypes/`, а НЕ `users/`.
> `users/` — сложный компонент с увольнением и правами, не подходит как образец для простых справочников.

### Структура каталога `components/salaryTypes/` (ЭТАЛОН)
```
salaryTypes/
├── SalaryTypesTable.vue        # Основная таблица (TanStack Table)
├── SalaryTypeFormModal.vue     # Форма создания/редактирования
├── SalaryTypeActionsModal.vue  # Модальное окно действий
├── ViewSalaryTypeModal.vue     # Просмотр карточки
└── salaryTypes.css             # Субстили компонента (импортируется в main.ts)
```

**Отдельный DeleteModal НЕ нужен** — удаление реализуется через универсальный `ConfirmModal`.

### Ключевые особенности эталона:

#### ActionsModal (ЭТАЛОН)
- Оверлей **2-го порядка:** `modal-overlay-main` (z-index 999)
- `onMounted` → `check_relations` → `hasRelations: boolean` (по умолчанию `true` — кнопка скрыта до ответа)
- Кнопка "Удалить": `v-if="canDelete"` — скрыта если есть связи
- `ConfirmModal` (1-й порядок, `modal-overlay`, z-index 9999) открывается поверх через собственный `Teleport`
- Кнопка "Закрыть" всегда внизу списка
- Emit: `close`, `add`, `view`, `edit`, `delete`

#### FormModal (ЭТАЛОН)
- Оверлей **2-го порядка:** `modal-overlay-main`
- Поля с надписью `input-label` сверху, структура `input-row` → `input-field`
- `ValidAlertModal` для ошибок валидации (не `AlertModal`!)
- `AlertModal` для серверных ошибок
- При успехе — сразу `emit('save')`, без алерта успеха
- `SwitchToggle` вместо нативных `<input type="checkbox">`
- Субстили в отдельном `.css` файле каталога (если нужны специфичные классы)

#### CSS субстили
- Специфичные классы компонента — в отдельном файле `{name}.css` в папке компонента
- Импортируется в `main.ts` рядом с другими субстилями
- Использует только CSS переменные из `style.css`

#### API (ЭТАЛОН)
- Эндпоинт: `/api/{name}.php?action={action}`
- `GET ?action=list` — список
- `GET ?action=get&id=X` — один элемент
- `GET ?check_relations=1&id=X` — проверка связей (возвращает `true`/`false`)
- `POST ?action=create` — создание
- `POST ?action=update` — обновление (id в теле)
- `DELETE ?action=delete&id=X` — удаление (сам проверяет связи, 409 если есть)
- Защита: сессия + проверка роли по паттерну `users.php`

---

## 2. Таблицы БД (исправлено)

### 2.1 `clients` — Клиенты
| Поле | Тип | Описание |
|------|-----|----------|
| id | INT | PK |
| name | VARCHAR(100) | ФИО клиента |
| phone | VARCHAR(20) | Уникальный телефон |
| total_bookings | INT | Счётчик записей |
| notes | TEXT | Заметки |
| created_at | TIMESTAMP | Дата создания |
| created_by | INT | FK → users.id |
| updated_by | INT | FK → users.id |

**Ограничения:**
- UNIQUE KEY `phone` — телефон должен быть уникальным
- **Связи:** `bookings.client_id`, `income.client_id`
- **Удаление:** невозможно при наличии записей в bookings или income

---

### 2.2 `shooting_types` — Типы съёмок
| Поле | Тип | Описание |
|------|-----|----------|
| id | INT | PK |
| name | VARCHAR(100) | Название |
| base_price | DECIMAL(10,2) | Базовая цена |
| duration_minutes | INT | Длительность (по умолчанию 30) |
| description | TEXT | Описание |
| is_active | TINYINT(1) | Активен ли (по умолчанию 1) |
| created_at | TIMESTAMP | Дата создания |
| created_by | INT | FK → users.id |
| updated_by | INT | FK → users.id |

**Ограничения:**
- **Связи:** `bookings.shooting_type_id`
- **Удаление:** невозможно при наличии записей в bookings → только деактивация (is_active = 0)

---

### 2.3 `promotions` — Акции
| Поле | Тип | Описание |
|------|-----|----------|
| id | INT | PK |
| name | VARCHAR(100) | Название акции |
| discount_percent | DECIMAL(5,2) | Процент скидки |
| start_date | DATE | Дата начала (NULL для бессрочных) |
| end_date | DATE | Дата окончания (NULL для бессрочных) |
| is_active | TINYINT(1) | Активна ли (по умолчанию 1) |
| created_at | TIMESTAMP | Дата создания |
| created_by | INT | FK → users.id |
| updated_by | INT | FK → users.id |

**Ограничения:**
- **Связи:** `bookings.promotion_id`
- **Удаление:** невозможно при использовании в bookings → только деактивация

---

### 2.4 `expense_categories` — Категории расходов
| Поле | Тип | Описание |
|------|-----|----------|
| id | INT | PK |
| name | VARCHAR(100) | Название категории |
| is_active | TINYINT(1) | Активна ли (по умолчанию 1) |
| created_at | TIMESTAMP | Дата создания |
| created_by | INT | FK → users.id |
| updated_by | INT | FK → users.id |

**Ограничения:**
- **Связи:** `expenses.category`
- **Удаление:** невозможно при наличии расходов → только деактивация

---

### 2.5 `salary_type` — Типы зарплат (ИСПРАВЛЕНО! 2 апреля)

**ВАЖНО:** Эта таблица НЕ рассчитывает зарплату, только хранит настройки как рассчитывать!

**ПОЛЯ БД (реальные из init-database.sql):**

| Поле | Тип | Описание |
|------|-----|----------|
| id | INT | PK |
| title | VARCHAR(255) | **Название типа** (обязательное поле) |
| monthly_salary | TINYINT | Оклад в месяц (0/1) |
| salary_value | INT | Значение оклада (руб) |
| percentage_of_the_order | TINYINT | Процент от заказа (0/1) |
| the_percentage_value | INT | Значение процента (%) |
| interest_dividends | TINYINT | Проценты дивидендов (0/1) |
| value_dividend_percentages | INT | Значение процентов (%) |
| fixed_order | TINYINT | Фиксированное от заказа (0/1) |
| fixed_value_order | INT | Фиксированное значение (руб) |

**Ограничения:**
- **Связи:** `users.id_salary_type`
- **Нет полей аудита** (created_by, updated_by, is_active)
- **Удаление:** невозможно при наличии пользователей с этим типом зарплаты

**Форма редактирования (ИСПРАВЛЕНО!):**

**Первое поле:** Название (text input, обязательное)

**4 начисления НЕ взаимоисключающие:** паттерн `[checkbox] [input поле]`:

```
┌─────────────────────────────────────────────┐
│ Название: [_________________________________] │
│                                             │
│ ☐ Оклад в месяц       [__________] руб      │
│    monthly_salary     salary_value          │
│                                             │
│ ☐ Процент от заказа   [__________] %        │
│    percentage_of_the_order                  │
│    the_percentage_value                     │
│                                             │
│ ☐ Проценты дивидендов [__________] %        │
│    interest_dividends                       │
│    value_dividend_percentages               │
│                                             │
│ ☐ Фиксированное       [__________] руб      │
│    fixed_order        fixed_value_order     │
└─────────────────────────────────────────────┘
```

**Логика формы:**
- Галка включена → поле активно (вводишь значение)
- Галка выключена → поле неактивно (disabled), значение = 0
- **Можно выбрать несколько вариантов одновременно** (НЕ взаимоисключающие!)
- Это просто справочник настроек зарплаты, не более!
- Это просто справочник настроек зарплаты, не более!

---

## 3. Права доступа (ИСПРАВЛЕНО! 2 апреля)

### Жёсткое требование: ОТДЕЛЬНЫЕ секции для каждого справочника

**НЕ объединять в единую секцию `references`!** Использовать отдельные секции как в permissions.ts.

### Секции справочников:
```typescript
export type Section =
  | 'calendar' | 'bookings' | 'income' | 'expenses' | 'reports'
  | 'clients' | 'shooting_types' | 'promotions' | 'expense_categories' | 'salary_types'
  | 'users' | 'settings' | 'home' | 'news' | 'portfolio' | 'logs'
```

### Проверка прав:
```typescript
auth.can('clients', 'full')           // FULL доступ к клиентам
auth.can('shooting_types', 'view')    // VIEW доступ к типам съёмок
auth.can('promotions', 'view')        // VIEW доступ к акциям
auth.can('expense_categories', 'no')  // NO доступ к категориям расходов
auth.can('salary_types', 'view')      // VIEW доступ к зарплатам
```

### Права по умолчанию (ROLE_DEFAULTS) — текущие из permissions.ts:
```typescript
export const ROLE_DEFAULTS: Record<number, Record<Section, AccessLevel>> = {
  1: { // admin
    calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL',
    reports: 'FULL', clients: 'FULL', shooting_types: 'FULL', promotions: 'FULL',
    expense_categories: 'FULL', users: 'FULL', settings: 'FULL',
    home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'FULL',
    // salary_types: 'FULL' — добавить
  },
  2: { // superuser
    calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL',
    reports: 'FULL', clients: 'FULL', shooting_types: 'FULL', promotions: 'FULL',
    expense_categories: 'FULL', users: 'FULL', settings: 'FULL',
    home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'NO',
    // salary_types: 'FULL' — добавить
  },
  3: { // superuser1
    calendar: 'FULL', bookings: 'FULL', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'FULL', shooting_types: 'VIEW', promotions: 'VIEW',
    expense_categories: 'NO', users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'FULL', portfolio: 'VIEW', logs: 'NO',
    // salary_types: 'NO' — добавить
  },
  4: { // auser
    calendar: 'FULL', bookings: 'FULL', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'FULL', shooting_types: 'VIEW', promotions: 'VIEW',
    expense_categories: 'NO', users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'FULL', portfolio: 'VIEW', logs: 'NO',
    // salary_types: 'NO' — добавить
  },
  5: { // prouser
    calendar: 'VIEW', bookings: 'VIEW', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'NO', shooting_types: 'NO', promotions: 'NO',
    expense_categories: 'NO', users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO',
    // salary_types: 'NO' — добавить
  },
  6: { // user
    calendar: 'NO', bookings: 'NO', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'NO', shooting_types: 'NO', promotions: 'NO',
    expense_categories: 'NO', users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO',
    // salary_types: 'NO' — добавить
  },
}
```

**Отдельные секции для каждого справочника:**
- `clients` — клиенты
- `shooting_types` — типы съёмок
- `promotions` — акции
- `expense_categories` — категории расходов
- `salary_types` — зарплаты (добавить)

---

## 4. Удаление элементов (ФИНАЛЬНАЯ ВЕРСИЯ!)

### Критическое правило: удаление возможно ТОЛЬКО если элемент нигде не участвует

**Для всех таблиц в проекте!**

### Паттерн реализации (как в expense-categories.php):

**Бэкенд:**
1. Проверка связей: `SELECT COUNT(*) FROM {table} WHERE {fk} = ?`
2. Если count > 0 → вернуть 409 с сообщением
3. Если count = 0 → DELETE

**Фронтенд:**
1. Сначала `?check_relations=1&id=X` → если true → alert «Удаление невозможно»
2. Если false → показать DeleteModal
3. После подтверждения → DELETE

### Таблица проверок связей:

| Справочник | SQL запрос проверки |
|------------|---------------------|
| **clients** | `SELECT COUNT(*) FROM bookings WHERE client_id = ?` + `SELECT COUNT(*) FROM income WHERE client_id = ?` |
| **shooting_types** | `SELECT COUNT(*) FROM bookings WHERE shooting_type_id = ?` |
| **promotions** | `SELECT COUNT(*) FROM bookings WHERE promotion_id = ?` |
| **expense_categories** | `SELECT COUNT(*) FROM expenses WHERE category = ?` |
| **salary_type** | `SELECT COUNT(*) FROM users WHERE id_salary_type = ?` |

### API endpoint (пример для clients.php):
```php
// Проверка связей
if (isset($_GET['check_relations']) && isset($_GET['id'])) {
    $relations = [];

    // Проверка bookings
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE client_id = ?");
    $stmt->execute([$_GET['id']]);
    $count = $stmt->fetch()['count'];
    if ($count > 0) {
        $relations[] = "Записи на съёмку: {$count}";
    }

    // Проверка income
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM income WHERE client_id = ?");
    $stmt->execute([$_GET['id']]);
    $count = $stmt->fetch()['count'];
    if ($count > 0) {
        $relations[] = "Платежи: {$count}";
    }

    if (!empty($relations)) {
        http_response_code(409);
        echo json_encode([
            'error' => 'Невозможно удалить элемент',
            'relations' => $relations  // Массив связей
        ]);
        exit;
    }

    echo json_encode(false);  // Связей нет
    break;
}

// Удаление (только если check_relations вернул false)
if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = $db->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    echo json_encode(['success' => true]);
}
```

### UI: модальное окно с перечнем связей
```vue
<AlertModal
  :is-visible="showAlert"
  title="Удаление невозможно"
  :message="`Категорию &quot;${name}&quot; удалить нельзя. С этой категорией связаны другие документы:\n\n${relations.join('\n')}`"
  @close="showAlert = false"
/>
```

### Фронтенд логика удаления:
```typescript
async function checkRelations(id: number) {
  const response = await fetch(`/api/clients.php?check_relations=1&id=${id}`)
  
  if (response.status === 409) {
    const data = await response.json()
    showAlert.value = true
    alertTitle.value = 'Удаление невозможно'
    alertMessage.value = `Категорию "${name}" удалить нельзя. С этой категорией связаны другие документы:\n\n${data.relations.join('\n')}`
    return true
  }
  
  return false  // Связей нет, можно показывать DeleteModal
}

async function deleteClient(id: number) {
  const hasRelations = await checkRelations(id)
  if (hasRelations) return
  
  // Показываем DeleteModal для подтверждения
  showDeleteModal.value = true
  clientToDelete.value = id
}
```

---

## 5. Стили (ИСПРАВЛЕНО!)

### Жёсткое требование: аналогично `components/users/`

**Никаких новых стилей — копировать padGlass, btnGlass, неоновые акценты!**

### Используемые классы:
- **Панель таблицы:** `padGlass padGlass-work data-table-panel`
- **Кнопки:** `btnGlass iconText` или `btnGlass bigIcon`
- **Модальные окна:** `padGlass modal-sm` или `padGlass modal-md`
- **Таблица:** `data-table` + `accounting-table`
- **Неоновые акценты:** `inner-glow`, `top-shine`

### Никаких "привести к единому виду" — сразу делать как в users/!

**Glassmorphism + неон — стандарт проекта.**

---

## 6. LaunchPad (ИСПРАВЛЕНО!)

### Кнопки УЖЕ ЕСТЬ для:
- ✅ Клиенты (`mdiAccountGroup`)
- ✅ Типы съёмок (`mdiCamera`)
- ✅ Акции (`mdiTagMultiple`)
- ✅ Категории расходов (`mdiShapeOutline`)

### НЕТ кнопки только для: зарплаты (salary_type)

### Добавить 1 кнопку с иконкой `mdiCashEdit`:

```vue
<!-- После кнопки "Категории расходов" -->
<div class="pad-icon-cell" v-if="auth.can('salary_types', 'view')">
  <button class="btnGlass bigIcon" @click="onRipple($event); openSalaryTypes()">
    <span class="inner-glow"></span>
    <span class="top-shine"></span>
    <svg-icon type="mdi" :path="mdiCashEdit" class="btn-icon-big" />
  </button>
  <p class="pad-icon-label">Зарплаты</p>
</div>
```

### Функция навигации:
```typescript
function openSalaryTypes() {
  navStore.navigateTo('salary_types')
  close()
}
```

---

## 7. Файлы для создания

### 7.1 API endpoint (1 файл)
- [ ] `api/salary-types.php` — CRUD для типов зарплат

### 7.2 Frontend компоненты (ИСПРАВЛЕНО! DeleteModal не нужен — используем ConfirmModal)

| Справочник | Table | Form | View | Actions | CSS | Итого |
|------------|-------|------|------|---------|-----|-------|
| **clients** | ❌ | ❌ | ❌ | ❌ | ❌ | 0 |
| **shooting_types** | ❌ | ❌ | ❌ | ❌ | ❌ | 0 |
| **promotions** | ❌ | ❌ | ❌ | ❌ | ❌ | 0 |
| **expense_categories** | ❌ | ❌ | ❌ | ❌ | ❌ | 0 |
| **salary_types** | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |

**Структура каталогов (camelCase, по эталону salaryTypes/):**

```
components/
├── clients/
│   ├── ClientsTable.vue
│   ├── ClientFormModal.vue
│   ├── ViewClientModal.vue
│   ├── ClientActionsModal.vue
│   └── clients.css             ← субстили (если нужны)
├── shootingTypes/
│   ├── ShootingTypesTable.vue
│   ├── ShootingTypeFormModal.vue
│   ├── ViewShootingTypeModal.vue
│   ├── ShootingTypeActionsModal.vue
│   └── shootingTypes.css
├── promotions/
│   ├── PromotionsTable.vue
│   ├── PromotionFormModal.vue
│   ├── ViewPromotionModal.vue
│   ├── PromotionActionsModal.vue
│   └── promotions.css
├── expenseCategories/
│   ├── ExpenseCategoriesTable.vue
│   ├── ExpenseCategoryFormModal.vue
│   ├── ViewExpenseCategoryModal.vue
│   ├── ExpenseCategoryActionsModal.vue
│   └── expenseCategories.css
└── salaryTypes/                ← ЭТАЛОН ✅ ГОТОВО
    ├── SalaryTypesTable.vue
    ├── SalaryTypeFormModal.vue
    ├── ViewSalaryTypeModal.vue
    ├── SalaryTypeActionsModal.vue
    └── salaryTypes.css
```

**Удаление:** через универсальный `ConfirmModal` внутри ActionsModal — отдельный DeleteModal не создавать!

---

## 8. Файлы для изменения

### 8.1 `LaunchPad.vue`
**Путь:** `maribulka-vue/src/components/launchpad/LaunchPad.vue`

**Изменения:**
- Добавить импорт иконки `mdiCashEdit`
- Добавить функцию `openSalaryTypes()`
- Добавить кнопку для зарплат в секцию "Справочники"
- Проверка прав: `v-if="auth.can('salary_types', 'view')"`

### 8.2 `permissions.ts` (ИСПРАВЛЕНО! 2 апреля)
**Путь:** `maribulka-vue/src/stores/permissions.ts`

**Изменения:**
- Добавить секцию `'salary_types'` в тип `Section`
- Добавить `'salary_types'` в ROLE_DEFAULTS для всех ролей

```typescript
export type Section =
  | 'calendar' | 'bookings' | 'income' | 'expenses' | 'reports'
  | 'clients' | 'shooting_types' | 'promotions' | 'expense_categories' | 'salary_types'
  | 'users' | 'settings' | 'home' | 'news' | 'portfolio' | 'logs'

export const ROLE_DEFAULTS: Record<number, Record<Section, AccessLevel>> = {
  1: { // admin
    calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL',
    reports: 'FULL', clients: 'FULL', shooting_types: 'FULL', promotions: 'FULL',
    expense_categories: 'FULL', salary_types: 'FULL',  // ← ДОБАВИТЬ
    users: 'FULL', settings: 'FULL',
    home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'FULL',
  },
  2: { // superuser
    calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL',
    reports: 'FULL', clients: 'FULL', shooting_types: 'FULL', promotions: 'FULL',
    expense_categories: 'FULL', salary_types: 'FULL',  // ← ДОБАВИТЬ
    users: 'FULL', settings: 'FULL',
    home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'NO',
  },
  3: { // superuser1
    calendar: 'FULL', bookings: 'FULL', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'FULL', shooting_types: 'VIEW', promotions: 'VIEW',
    expense_categories: 'NO', salary_types: 'NO',  // ← ДОБАВИТЬ
    users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'FULL', portfolio: 'VIEW', logs: 'NO',
  },
  4: { // auser
    calendar: 'FULL', bookings: 'FULL', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'FULL', shooting_types: 'VIEW', promotions: 'VIEW',
    expense_categories: 'NO', salary_types: 'NO',  // ← ДОБАВИТЬ
    users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'FULL', portfolio: 'VIEW', logs: 'NO',
  },
  5: { // prouser
    calendar: 'VIEW', bookings: 'VIEW', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'NO', shooting_types: 'NO', promotions: 'NO',
    expense_categories: 'NO', salary_types: 'NO',  // ← ДОБАВИТЬ
    users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO',
  },
  6: { // user
    calendar: 'NO', bookings: 'NO', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'NO', shooting_types: 'NO', promotions: 'NO',
    expense_categories: 'NO', salary_types: 'NO',  // ← ДОБАВИТЬ
    users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO',
  },
}
```

### 8.3 `App.vue` (ИСПРАВЛЕНО! 2 апреля)
**Путь:** `maribulka-vue/src/App.vue`

**Изменения:**
- Добавить импорты компонентов справочников (camelCase для каталогов!)
- Добавить роутинг через навигацию

```vue
<script setup lang="ts">
// Импорты
import ClientsTable from './components/clients/ClientsTable.vue'
import ShootingTypesTable from './components/shootingTypes/ShootingTypesTable.vue'  ← camelCase!
import PromotionsTable from './components/promotions/PromotionsTable.vue'
import ExpenseCategoriesTable from './components/expenseCategories/ExpenseCategoriesTable.vue'  ← camelCase!
import SalaryTypesTable from './components/salaryTypes/SalaryTypesTable.vue'  ← camelCase!
</script>

<template>
  <ClientsTable v-if="navStore.currentPage === 'clients'" />
  <ShootingTypesTable v-if="navStore.currentPage === 'shooting_types'" />
  <PromotionsTable v-if="navStore.currentPage === 'promotions'" />
  <ExpenseCategoriesTable v-if="navStore.currentPage === 'expense_categories'" />
  <SalaryTypesTable v-if="navStore.currentPage === 'salary_types'" />
</template>
```

### 8.4 `navigation.ts`
**Путь:** `maribulka-vue/src/stores/navigation.ts`

**Изменения:**
- Добавить типы навигации для справочников в `PageType`
- Убедиться что все 5 справочников поддерживаются

```typescript
export type PageType =
  | 'calendar' | 'bookings' | 'income' | 'expenses' | 'reports'
  | 'clients' | 'shooting_types' | 'promotions' | 'expense_categories' | 'salary_types'  ← ДОБАВИТЬ
  | 'users' | 'settings' | 'home' | 'news' | 'portfolio' | 'logs'
```

---

## 9. Пошаговый план

### Этап 1: API для зарплат
- [ ] Создать `/api/salary-types.php` по аналогии с `expense-categories.php`
- [ ] Методы: GET (list, get), POST (create), PUT (update), DELETE
- [ ] Проверка связей: `users.id_salary_type`
- [ ] Добавить CORS для localhost и продакшн

### Этап 2: Компоненты таблиц (5 справочников)
- [ ] `components/clients/ClientsTable.vue`
- [ ] `components/shootingTypes/ShootingTypesTable.vue`  ← camelCase!
- [ ] `components/promotions/PromotionsTable.vue`
- [ ] `components/expenseCategories/ExpenseCategoriesTable.vue`  ← camelCase!
- [ ] `components/salaryTypes/SalaryTypesTable.vue`  ← camelCase!

**Структура каждой таблицы:**
- TanStack Table с сортировкой и фильтрацией
- Кнопки: Добавить, Просмотр, Редактировать, Удалить, Фильтр
- Панель фильтров (показывать/скрывать)
- Модальные окна действий

### Этап 3: Модальные окна (Form, View, Actions, Delete)
- [ ] Формы создания/редактирования (5 файлов)
- [ ] Формы просмотра (5 файлов)
- [ ] Модальные окна действий (5 файлов)
- [ ] Модальные окна удаления (5 файлов)

**Особенности форм:**
- **clients:** валидация телефона (уникальность), ФИО (кириллица)
- **shooting_types:** цена (число > 0), длительность (минуты)
- **promotions:** валидация дат (пересечение периодов), процент (0-100)
- **expense_categories:** только название
- **salary_types:** ✅ ГОТОВО — 4 `SwitchToggle` + input в строку `.salary-check-row`

**Правила формы (по эталону SalaryTypeFormModal):**
- Нативный `<input type="checkbox">` не использовать — только `SwitchToggle`
- Каждая строка toggle+поле: `<div class="salary-check-row">` (или аналог в субстилях)
- `ValidAlertModal` для ошибок валидации, `AlertModal` для серверных ошибок
- При успехе сразу `emit('save')` — без алерта успеха
- `watch` на toggle → при выключении сбрасывать значение поля в 0

### Этап 4: Интеграция в App.vue
- [ ] Импортировать все компоненты таблиц
- [ ] Добавить условный рендеринг по `navStore.currentPage`
- [ ] Проверить навигацию из LaunchPad

### Этап 5: Кнопка для зарплат в LaunchPad
- [ ] Добавить иконку `mdiCashEdit` в импорт
- [ ] Добавить функцию `openSalaryTypes()`
- [ ] Добавить кнопку в секцию "Справочники"
- [ ] Проверка прав: `v-if="auth.can('salary_types', 'view')"`

### Этап 6: Права доступа
- [ ] Добавить секцию `'salary_types'` в `permissions.ts`
- [ ] Настроить права по умолчанию для всех ролей
- [ ] Проверить работу `auth.can('salary_types', 'view')`

### Этап 7: Тестирование
- [ ] Проверить CRUD для каждого справочника
- [ ] Проверить права доступа для разных ролей
- [ ] Проверить валидацию форм
- [ ] Проверить удаление с проверкой связей
- [ ] Проверить навигацию из LaunchPad

---

## 10. Технические детали

### Аудит безопасности (2 апреля 2026)

**🔴 Критические проблемы бэкенда:**

| Файл | Проблема | Риск | Решение |
|------|----------|------|---------|
| `clients.php` | Нет сессии и проверки роли | Любой может DELETE/POST/PUT | Переписать по паттерну users.php |
| `shooting-types.php` | Нет сессии и проверки роли | Любой может DELETE/POST/PUT | Переписать по паттерну users.php |
| `promotions.php` | Нет сессии и проверки роли | Любой может DELETE/POST/PUT | Переписать по паттерну users.php |
| `expense-categories.php` | Нет сессии и проверки роли | Любой может DELETE/POST/PUT | Переписать по паттерну users.php |
| `bookings.php` | Нет сессии и проверки роли | Любой может DELETE/POST/PUT | Отдельный план |
| `income.php` | Нет сессии и проверки роли | Любой может DELETE/POST/PUT | Отдельный план |
| `expenses.php` | Нет сессии и проверки роли | Любой может DELETE/POST/PUT | Отдельный план |

**🟡 Высокие проблемы фронтенда:**

| Проблема | Где | Решение |
|----------|-----|---------|
| `salary_types` нет в `Section` type | `permissions.ts` | Добавить в тип Section |
| Компоненты не проверяют права перед запросами | Таблицы справочников | Обернуть кнопки в `v-if="auth.can(...)"` |

**✅ Что уже работает:**
- `ROLE_DEFAULTS` полностью заполнен для всех 6 ролей
- `hasPermission()` работает корректно
- Все секции в LaunchPad объявлены в `Section` type
- `users.php` и `permissions.php` защищены сессией

---

### API endpoints (ИСПРАВЛЕНО! 2 апреля)

**ВСЕ API файлы требуют переписывания по паттерну users.php!**

| Справочник | Endpoint | Статус | Что добавить | Приоритет |
|------------|----------|--------|--------------|-----------|
| clients | `/api/clients.php` | ⚠️ Переписать | сессии, роли, ?action= | 🔴 Высокий |
| shooting_types | `/api/shooting-types.php` | ⚠️ Переписать | сессии, роли, ?action= | 🔴 Высокий |
| promotions | `/api/promotions.php` | ⚠️ Переписать | сессии, роли, ?action= | 🔴 Высокий |
| expense_categories | `/api/expense-categories.php` | ⚠️ Переписать | сессии, роли, ?action= | 🔴 Высокий |
| salary_types | `/api/salary-types.php` | ❌ Создать | сессии, роли, ?action= | 🔴 Высокий |

**Паттерн users.php:**
```php
require_once 'session.php';
require_once 'database.php';
initSession();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentRole = (int)($_SESSION['user']['role'] ?? 0);
if (!in_array($currentRole, [1, 2, 3])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? 'list';

// GET ?action=list
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    // ...
}

// GET ?action=get&id=X
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    // ...
}

// POST ?action=create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    // ...
}

// PUT ?action=update
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $action === 'update') {
    // ...
}

// DELETE ?action=delete&id=X
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'delete') {
    // ...
}

// GET ?action=check_relations&id=X
if (isset($_GET['check_relations']) && isset($_GET['id'])) {
    // Проверка связей → true/false
}
```

### Типы данных для форм
```typescript
// Client
interface Client {
  id?: number
  name: string
  phone: string
  notes?: string
}

// ShootingType
interface ShootingType {
  id?: number
  name: string
  base_price: number
  duration_minutes?: number
  description?: string
  is_active?: boolean
}

// Promotion
interface Promotion {
  id?: number
  name: string
  discount_percent: number
  start_date?: string
  end_date?: string
  is_active?: boolean
}

// ExpenseCategory
interface ExpenseCategory {
  id?: number
  name: string
  is_active?: boolean
}

// SalaryType (ФИНАЛЬНАЯ ВЕРСИЯ!)
interface SalaryType {
  id?: number
  title: string  // Обязательное поле
  percent_from_shooting?: boolean
  percent_from_shooting_value?: number
  fixed_amount?: boolean
  fixed_amount_value?: number
  hourly_rate?: boolean
  hourly_rate_value?: number
  other?: boolean
  other_value?: number
  other_description?: string
}
```

### Проверка прав (ИСПРАВЛЕНО! 2 апреля)

**В шаблоне — КОНКРЕТНЫЕ секции!**
```typescript
import { useAuthStore } from '@/stores/auth'
const auth = useAuthStore()

// Кнопки действий — проверять перед отображением!
<button v-if="auth.can('clients', 'create')">Добавить клиента</button>
<button v-if="auth.can('shooting_types', 'edit')">Редактировать</button>
<button v-if="auth.can('salary_types', 'delete')" @click="handleDelete">Удалить</button>

// Для зарплат
<button v-if="auth.can('salary_types', 'view')">Открыть</button>
```

**В скрипте — двойная проверка!**
```typescript
async function handleDelete() {
  if (!selectedItem.value) return
  
  // Проверка прав перед вызовом модалки
  if (!auth.can('clients', 'delete')) {
    showAlert('Нет прав на удаление')
    return
  }
  
  // Проверка связей
  const hasRelations = await checkRelations(selectedItem.value.id)
  if (hasRelations) {
    showAlert('Удаление невозможно')
    return
  }
  
  // Показать модалку подтверждения
  showDeleteModal.value = true
}

async function confirmDelete() {
  // Финальная проверка перед запросом
  if (!auth.can('clients', 'delete')) {
    showAlert('Нет прав на удаление')
    return
  }
  
  const response = await fetch(`/api/clients.php?action=delete&id=${selectedItem.value.id}`, {
    method: 'DELETE'
  })
  // ...
}
```

**Важно:**
- LaunchPad скрывает кнопки через `v-if="auth.can(...)"` ✅
- **Компоненты таблиц** тоже должны проверять права перед отображением кнопок ⚠️
- **Функции действий** должны проверять права перед вызовом API ⚠️

### Стили (ИСПРАВЛЕНО!)
- **Панель таблицы:** `padGlass padGlass-work data-table-panel`
- **Кнопки:** `btnGlass iconText` или `btnGlass bigIcon`
- **Таблица:** `data-table` + `accounting-table`
- **Модальные окна:** `padGlass modal-sm` или `padGlass modal-md`
- **Неоновые акценты:** `inner-glow`, `top-shine`

---

## 11. Риски и особенности (ИСПРАВЛЕНО!)

### 11.1 clients
- **Риск:** Удаление клиента с активными заказами
- **Решение:** Проверка связей через `check_relations` перед удалением
- **Особенность:** Поле `total_bookings` требует синхронизации при изменении заказов

### 11.2 shooting_types
- **Риск:** Удаление типа с активными записями
- **Решение:** Деактивация вместо удаления (is_active = 0)
- **Особенность:** Влияет на расчёт стоимости в заказах

### 11.3 promotions
- **Риск:** Пересекающиеся периоды акций
- **Решение:** Валидация на уровне API (проверка пересечений)
- **Особенность:** Бессрочные акции (NULL даты) не пересекаются

### 11.4 expense_categories
- **Риск:** Удаление категории с расходами
- **Решение:** Проверка связей, деактивация вместо удаления
- **Особенность:** Простая структура — минимальная валидация

### 11.5 salary_type (ИСПРАВЛЕНО! 2 апреля)
- **Риск:** Сложная логика формы (8 полей начислений)
- **Решение:** 4 checkbox + input, галка включена → поле активно
- **Особенности:**
  - Нет `is_active` — только удаление
  - Нет полей аудита (`created_by`, `updated_by`)
  - Используется только в `users` — нельзя удалить при наличии сотрудников
  - **4 варианта начисления НЕ взаимоисключающие** — могут быть выбраны несколько одновременно
  - **Обязательное поле `title`** — название типа зарплаты
  - **Реальные поля БД:** `monthly_salary`, `salary_value`, `percentage_of_the_order`, `the_percentage_value`, `interest_dividends`, `value_dividend_percentages`, `fixed_order`, `fixed_value_order`
  - Это просто справочник настроек зарплаты, не более!

### 11.6 Общие риски (ИСПРАВЛЕНО! 2 апреля)
- **Стили:** Никаких новых стилей — копировать padGlass, btnGlass, неоновые акценты из users/
- **Навигация:** LaunchPad не имеет кнопки для зарплат — добавить 1 кнопку
- **Права:** Отдельные секции для каждого справочника (`clients`, `shooting_types`, `promotions`, `expense_categories`, `salary_types`), НЕ объединять в `references`
- **API:** Все файлы требуют переписывания с сессиями и ?action= паттерном

---

## 12. Архитектурные решения (ИСПРАВЛЕНО!)

### Почему отдельные каталоги (не accounting/)?
1. **Архитектурная чистота:** `accounting/` будет переименован в `ArchiveAccounting` и удалён
2. **Единый стиль:** аналогия с `users/` — каждый справочник в своём каталоге
3. **Масштабируемость:** легко добавить новые справочники
4. **Разделение ответственности:** каждый компонент отвечает за свой справочник

### Наследование от users/
- **Структура каталога:** 5-6 компонентов на справочник
- **Стили:** glassmorphism + неоновые акценты (padGlass, btnGlass)
- **TanStack Table:** единый подход к таблицам
- **Модальные окна:** Actions → Form/View/Delete

### Права доступа (ИСПРАВЛЕНО! 2 апреля)
- **Отдельные секции:** `clients`, `shooting_types`, `promotions`, `expense_categories`, `salary_types`
- **Проверка:** `auth.can(section, action)`
- **ROLE_DEFAULTS:** для каждой роли и секции (как в permissions.ts)

### Удаление элементов (ИСПРАВЛЕНО! 2 апреля)
- **Критическое правило:** удаление возможно ТОЛЬКО если элемент нигде не участвует
- **Проверка связей:** `?check_relations=1&id=X` → `true/false`
- **API endpoint:** SELECT COUNT(*) → true/false
- **UI:** alert если есть связи, DeleteModal если связей нет

---

## 13. Итоговый список файлов (ИСПРАВЛЕНО! 2 апреля)

### API (5 файлов — переписать + 1 создать)
- [ ] `api/salary-types.php` — создать
- [ ] `api/clients.php` — переписать (сессии, роли, ?action=)
- [ ] `api/shooting-types.php` — переписать (сессии, роли, ?action=)
- [ ] `api/promotions.php` — переписать (сессии, роли, ?action=)
- [ ] `api/expense-categories.php` — переписать (сессии, роли, ?action=)

### Frontend компоненты (25 файлов)
| Справочник | Table | Form | View | Actions | Delete | Итого |
|------------|-------|------|------|---------|--------|-------|
| clients | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| shooting_types | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| promotions | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| expense_categories | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| salary_types | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| **Итого** | **5** | **5** | **5** | **5** | **5** | **25** |

### Изменение существующих (3 файла)
- [ ] `LaunchPad.vue`
- [ ] `permissions.ts`
- [ ] `App.vue`

---

## 14. Критерии готовности (ИСПРАВЛЕНО! 2 апреля)

### Бэкенд (🔴 Критично)
- [ ] Все 5 API переписаны с сессиями и ?action= паттерном
- [ ] `initSession()` вызывается в начале каждого файла
- [ ] Проверка роли: `if (!in_array($currentRole, [1, 2, 3]))`
- [ ] Возврат 401/403 при отсутствии прав
- [ ] `?check_relations=1&id=X` работает для всех справочников

### Фронтенд (🟡 Важно)
- [ ] Все 5 справочников имеют полный CRUD
- [ ] Кнопка для зарплат добавлена в LaunchPad
- [ ] `salary_types` добавлена в тип `Section` в permissions.ts
- [ ] Компоненты проверяют права перед отображением кнопок
- [ ] Функции действий проверяют права перед вызовом API
- [ ] Валидация форм соответствует БД
- [ ] Удаление с проверкой связей для ВСЕХ справочников
- [ ] Стили: glassmorphism + неоновые акценты (как в users/)
- [ ] Навигация из LaunchPad работает
- [ ] API для зарплат готов и протестирован
- [ ] Форма salary_type: название + 4 checkbox + input (НЕ взаимоисключающие)

---

## 15. Финальные требования (ИСПРАВЛЕНО! 2 апреля)

### 🔴 Приоритет 1: Безопасность (критично)

**1. API — переписать все (5 файлов):**
- Сессии: `initSession()`, проверка роли
- Паттерн `?action=`: `list`, `get`, `create`, `update`, `delete`
- Проверка связей: `?check_relations=1&id=X` → `true/false`
- Возврат 401/403 при отсутствии прав

**2. salary_types — поля БД (реальные!):**
- `title` (обязательное)
- `monthly_salary` + `salary_value` — Оклад в месяц
- `percentage_of_the_order` + `the_percentage_value` — Процент от заказа
- `interest_dividends` + `value_dividend_percentages` — Проценты дивидендов
- `fixed_order` + `fixed_value_order` — Фиксированное от заказа
- 4 начисления НЕ взаимоисключающие

### 🟡 Приоритет 2: Фронтенд (важно)

**3. Удаление — проверка в бэкенде:**
- `SELECT COUNT(*)` → `true/false`
- Фронтенд: сначала check_relations → alert или DeleteModal
- Двойная проверка прав (в шаблоне + в скрипте)

**4. Права — отдельные секции:**
- `clients`, `shooting_types`, `promotions`, `expense_categories`, `salary_types`
- `auth.can(section, action)`
- `salary_types` добавить в тип `Section`

**5. Стили — копировать users/:**
- padGlass, btnGlass, неоновые акценты
- Никаких новых стилей

**6. LaunchPad — добавить 1 кнопку:**
- Для зарплат с `mdiCashEdit`
- Проверка: `v-if="auth.can('salary_types', 'view')"`

**7. Структура каталогов — camelCase:**
- `clients/`, `shootingTypes/`, `promotions/`, `expenseCategories/`, `salaryTypes/`

---

*План обновлён: 2 апреля 2026*  
*Исправления по замечаниям:*
1. *salary_type — реальные поля БД, НЕ выдуманные*
2. *API — все требуют переписывания с сессиями и ?action=*
3. *expense-categories.php — существует, но требует переписывания*
4. *Права — отдельные секции, НЕ объединять в `references`*
5. *Структура — camelCase для каталогов (expenseCategories/)*
6. ***Правило: редактировать существующий план, а не создавать новый файл***

*Аудит безопасности (2 апреля 2026):*
- *🔴 7 API файлов без сессии и проверки роли (clients.php, shooting-types.php, promotions.php, expense-categories.php, bookings.php, income.php, expenses.php)*
- *🟡 salary_types нет в Section type — добавить в permissions.ts*
- *🟡 Компоненты не проверяют права перед запросами — обернуть кнопки в v-if="auth.can(...)"+*
- *✅ ROLE_DEFAULTS полностью заполнен, hasPermission() работает корректно*
