# План реализации: Подключение справочников (ИСПРАВЛЕННЫЙ)

**Дата:** 1 апреля 2026
**Статус:** Готов к реализации
**Автор:** Архитектор Maribulka

---

## 1. Анализ эталона (users/)

### Структура каталога `components/users/`
```
users/
├── UsersTable.vue           # Основная таблица (TanStack Table)
├── UserFormModal.vue        # Форма создания/редактирования
├── UserActionsModal.vue     # Модальное окно действий
├── ViewUserModal.vue        # Просмотр карточки
├── FireUserModal.vue        # Увольнение пользователя
└── UserPermissionsModal.vue # Права доступа
```

### Ключевые особенности эталона:
- **TanStack Table** для отображения данных
- **Pinia store** для состояния (auth.can для прав)
- **Модульная структура:** каждый компонент отвечает за одно действие
- **Стили:** glassmorphism (padGlass, btnGlass) + неоновые акценты
- **API:** единый endpoint `/api/users.php?action={action}`

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

### 2.5 `salary_type` — Типы зарплат (ИСПРАВЛЕНО!)

**ВАЖНО:** Эта таблица НЕ рассчитывает зарплату, только хранит настройки как рассчитывать!

| Поле | Тип | Описание |
|------|-----|----------|
| id | INT | PK |
| title | VARCHAR(255) | Название типа |
| monthly_salary | TINYINT | Оклад в месяц (0/1) |
| salary_value | INT | Значение оклада (руб) |
| percentage_of_the_order | TINYINT | Процент от заказа (0/1) |
| the_percentage_value | INT | Значение процента (%) |
| interest_dividends | TINYINT | Проценты дивидендов (0/1) |
| value_dividend_percentages | INT | Значение дивидендов (%) |
| fixed_order | TINYINT | Фиксированное от заказа (0/1) |
| fixed_value_order | INT | Фиксированное значение (руб) |

**Ограничения:**
- **Связи:** `users.id_salary_type`
- **Нет полей аудита** (created_by, updated_by, is_active)
- **Удаление:** невозможно при наличии пользователей с этим типом зарплаты

**Форма редактирования (ИСПРАВЛЕНО!):**
4 строки с паттерном `[checkbox] [input поле]`:

```
┌─────────────────────────────────────────────┐
│ ☐ Оклад в месяц      [__________] руб      │
│ ☐ Процент от заказа  [__________] %        │
│ ☐ Проценты дивидендов [_________] %        │
│ ☐ Фиксированное от заказа [______] руб     │
└─────────────────────────────────────────────┘
```

**Логика формы:**
- Галка включена → поле активно (вводишь значение)
- Галка выключена → поле неактивно (disabled), значение обнуляется
- **Можно выбрать несколько вариантов одновременно** (НЕ взаимоисключающие!)
- Это просто справочник настроек зарплаты, не более!

---

## 3. Права доступа (ИСПРАВЛЕНО!)

### Жёсткое требование: все справочники наследуют права от секции `"references"`

**НЕ создавать отдельные секции для каждого справочника!**

### Единая секция: `references`
```typescript
export type Section =
  | 'calendar' | 'bookings' | 'income' | 'expenses' | 'reports'
  | 'references'  // ← ЕДИНАЯ секция для ВСЕХ справочников
  | 'users' | 'settings' | 'home' | 'news' | 'portfolio' | 'logs'
```

### Проверка прав:
```typescript
auth.can('references', 'full')  // FULL доступ ко всем справочникам
auth.can('references', 'view')  // VIEW доступ ко всем справочникам
auth.can('references', 'no')    // NO доступ ко всем справочникам
```

### Права по умолчанию (ROLE_DEFAULTS):
```typescript
export const ROLE_DEFAULTS: Record<number, Record<Section, AccessLevel>> = {
  1: { // admin
    references: 'FULL',  // Все справочники: FULL
    users: 'FULL',
    // ...
  },
  2: { // superuser
    references: 'FULL',  // Все справочники: FULL
    users: 'FULL',
    // ...
  },
  3: { // superuser1
    references: 'VIEW',  // Все справочники: VIEW
    users: 'NO',
    // ...
  },
  4: { // auser
    references: 'VIEW',  // Все справочники: VIEW
    users: 'NO',
    // ...
  },
  5: { // worker
    references: 'NO',    // Все справочники: NO
    users: 'NO',
    // ...
  },
  6: { // user
    references: 'NO',    // Все справочники: NO
    users: 'NO',
    // ...
  },
}
```

**Все справочники используют одну секцию:**
- clients
- shooting_types
- promotions
- expense_categories
- salary_types

---

## 4. Удаление элементов (ИСПРАВЛЕНО!)

### Критическое правило: удаление возможно ТОЛЬКО если элемент нигде не участвует

**Для всех таблиц в проекте!**

### Проверка связей перед удалением:

| Справочник | Где проверяется связь | Таблица связи | Поле связи |
|------------|----------------------|---------------|------------|
| **clients** | bookings, income | `bookings` | `client_id` |
| | | `income` | `client_id` |
| **shooting_types** | bookings | `bookings` | `shooting_type_id` |
| **promotions** | bookings | `bookings` | `promotion_id` |
| **expense_categories** | expenses | `expenses` | `category` |
| **salary_type** | users | `users` | `id_salary_type` |

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
<div class="pad-icon-cell" v-if="auth.can('references', 'view')">
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

### 7.2 Frontend компоненты (25 файлов)

| Справочник | Table | Form | View | Actions | Delete | Итого |
|------------|-------|------|------|---------|--------|-------|
| **clients** | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| **shooting_types** | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| **promotions** | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| **expense_categories** | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| **salary_types** | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| **Итого** | **5** | **5** | **5** | **5** | **5** | **25** |

**Структура каталогов:**
```
components/
├── clients/
│   ├── ClientsTable.vue
│   ├── ClientFormModal.vue
│   ├── ViewClientModal.vue
│   ├── ClientActionsModal.vue
│   └── DeleteClientModal.vue
├── shooting_types/
│   ├── ShootingTypesTable.vue
│   ├── ShootingTypeFormModal.vue
│   ├── ViewShootingTypeModal.vue
│   ├── ShootingTypeActionsModal.vue
│   └── DeleteShootingTypeModal.vue
├── promotions/
│   ├── PromotionsTable.vue
│   ├── PromotionFormModal.vue
│   ├── ViewPromotionModal.vue
│   ├── PromotionActionsModal.vue
│   └── DeletePromotionModal.vue
├── expense_categories/
│   ├── ExpenseCategoriesTable.vue
│   ├── ExpenseCategoryFormModal.vue
│   ├── ViewExpenseCategoryModal.vue
│   ├── ExpenseCategoryActionsModal.vue
│   └── DeleteExpenseCategoryModal.vue
└── salary_types/
    ├── SalaryTypesTable.vue
    ├── SalaryTypeFormModal.vue
    ├── ViewSalaryTypeModal.vue
    ├── SalaryTypeActionsModal.vue
    └── DeleteSalaryTypeModal.vue
```

---

## 8. Файлы для изменения

### 8.1 `LaunchPad.vue`
**Путь:** `maribulka-vue/src/components/launchpad/LaunchPad.vue`

**Изменения:**
- Добавить импорт иконки `mdiCashEdit`
- Добавить функцию `openSalaryTypes()`
- Добавить кнопку для зарплат в секцию "Справочники"
- Проверка прав: `v-if="auth.can('references', 'view')"`

### 8.2 `permissions.ts`
**Путь:** `maribulka-vue/src/stores/permissions.ts`

**Изменения:**
- Заменить отдельные секции справочников на единую `'references'`
- Добавить права по умолчанию для всех ролей

```typescript
export type Section =
  | 'calendar' | 'bookings' | 'income' | 'expenses' | 'reports'
  | 'references'  // ← ЕДИНАЯ секция для ВСЕХ справочников
  | 'users' | 'settings' | 'home' | 'news' | 'portfolio' | 'logs'

export const ROLE_DEFAULTS: Record<number, Record<Section, AccessLevel>> = {
  1: {
    calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL',
    reports: 'FULL', references: 'FULL',  // ← Все справочники: FULL
    users: 'FULL', settings: 'FULL',
    home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'FULL',
  },
  2: {
    calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL',
    reports: 'FULL', references: 'FULL',  // ← Все справочники: FULL
    users: 'FULL', settings: 'FULL',
    home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'NO',
  },
  3: {
    calendar: 'FULL', bookings: 'FULL', income: 'NO', expenses: 'NO',
    reports: 'NO', references: 'VIEW',  // ← Все справочники: VIEW
    users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'FULL', portfolio: 'VIEW', logs: 'NO',
  },
  4: {
    calendar: 'FULL', bookings: 'FULL', income: 'NO', expenses: 'NO',
    reports: 'NO', references: 'VIEW',  // ← Все справочники: VIEW
    users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'FULL', portfolio: 'VIEW', logs: 'NO',
  },
  5: {
    calendar: 'VIEW', bookings: 'VIEW', income: 'NO', expenses: 'NO',
    reports: 'NO', references: 'NO',  // ← Все справочники: NO
    users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO',
  },
  6: {
    calendar: 'NO', bookings: 'NO', income: 'NO', expenses: 'NO',
    reports: 'NO', references: 'NO',  // ← Все справочники: NO
    users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO',
  },
}
```

### 8.3 `App.vue`
**Путь:** `maribulka-vue/src/App.vue`

**Изменения:**
- Добавить импорты компонентов справочников
- Добавить роутинг через навигацию

```vue
<script setup lang="ts">
// Импорты
import ClientsTable from './components/clients/ClientsTable.vue'
import ShootingTypesTable from './components/shooting_types/ShootingTypesTable.vue'
import PromotionsTable from './components/promotions/PromotionsTable.vue'
import ExpenseCategoriesTable from './components/expense_categories/ExpenseCategoriesTable.vue'
import SalaryTypesTable from './components/salary_types/SalaryTypesTable.vue'
</script>

<template>
  <ClientsTable v-if="navStore.currentPage === 'clients'" />
  <ShootingTypesTable v-if="navStore.currentPage === 'shooting_types'" />
  <PromotionsTable v-if="navStore.currentPage === 'promotions'" />
  <ExpenseCategoriesTable v-if="navStore.currentPage === 'expense_categories'" />
  <SalaryTypesTable v-if="navStore.currentPage === 'salary_types'" />
</template>
```

### 8.4 `navigation.ts` (если существует)
**Путь:** `maribulka-vue/src/stores/navigation.ts`

**Изменения:**
- Добавить типы навигации для справочников

---

## 9. Пошаговый план

### Этап 1: API для зарплат
- [ ] Создать `/api/salary-types.php` по аналогии с `expense-categories.php`
- [ ] Методы: GET (list, get), POST (create), PUT (update), DELETE
- [ ] Проверка связей: `users.id_salary_type`
- [ ] Добавить CORS для localhost и продакшн

### Этап 2: Компоненты таблиц (5 справочников)
- [ ] `components/clients/ClientsTable.vue`
- [ ] `components/shooting_types/ShootingTypesTable.vue`
- [ ] `components/promotions/PromotionsTable.vue`
- [ ] `components/expense_categories/ExpenseCategoriesTable.vue`
- [ ] `components/salary_types/SalaryTypesTable.vue`

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
- **salary_types:** 4 checkbox + input (галка включена → поле активно)

### Этап 4: Интеграция в App.vue
- [ ] Импортировать все компоненты таблиц
- [ ] Добавить условный рендеринг по `navStore.currentPage`
- [ ] Проверить навигацию из LaunchPad

### Этап 5: Кнопка для зарплат в LaunchPad
- [ ] Добавить иконку `mdiCashEdit` в импорт
- [ ] Добавить функцию `openSalaryTypes()`
- [ ] Добавить кнопку в секцию "Справочники"
- [ ] Проверка прав: `v-if="auth.can('references', 'view')"`

### Этап 6: Права доступа
- [ ] Заменить отдельные секции на `'references'` в `permissions.ts`
- [ ] Настроить права по умолчанию для всех ролей
- [ ] Проверить работу `auth.can('references', 'view')`

### Этап 7: Тестирование
- [ ] Проверить CRUD для каждого справочника
- [ ] Проверить права доступа для разных ролей
- [ ] Проверить валидацию форм
- [ ] Проверить удаление с проверкой связей
- [ ] Проверить навигацию из LaunchPad

---

## 10. Технические детали

### API endpoints
| Справочник | Endpoint | Статус |
|------------|----------|--------|
| clients | `/api/clients.php` | ✅ Готов |
| shooting_types | `/api/shooting-types.php` | ✅ Готов |
| promotions | `/api/promotions.php` | ✅ Готов |
| expense_categories | `/api/expense-categories.php` | ✅ Готов |
| salary_types | `/api/salary-types.php` | ❌ Требуется создать |

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

// SalaryType (ИСПРАВЛЕНО!)
interface SalaryType {
  id?: number
  title: string
  monthly_salary?: boolean
  salary_value?: number
  percentage_of_the_order?: boolean
  the_percentage_value?: number
  interest_dividends?: boolean
  value_dividend_percentages?: number
  fixed_order?: boolean
  fixed_value_order?: number
}
```

### Проверка прав (ИСПРАВЛЕНО!)
```typescript
import { useAuthStore } from '@/stores/auth'
const auth = useAuthStore()

// В шаблоне
<button v-if="auth.can('references', 'create')">Добавить</button>

// В скрипте
if (!auth.can('references', 'delete')) {
  // Показать ошибку
}
```

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

### 11.5 salary_type (ИСПРАВЛЕНО!)
- **Риск:** Сложная логика формы
- **Решение:** 4 checkbox + input, галка включена → поле активно
- **Особенности:**
  - Нет `is_active` — только удаление
  - Нет полей аудита (`created_by`, `updated_by`)
  - Используется только в `users` — нельзя удалить при наличии сотрудников
  - **4 варианта начисления НЕ взаимоисключающие** — могут быть выбраны несколько одновременно
  - Это просто справочник настроек зарплаты, не более!

### 11.6 Общие риски
- **Стили:** Никаких новых стилей — копировать padGlass, btnGlass, неоновые акценты из users/
- **Навигация:** LaunchPad не имеет кнопки для зарплат — добавить 1 кнопку
- **Права:** Все справочники используют единую секцию `'references'`

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

### Права доступа (ИСПРАВЛЕНО!)
- **Role-based:** все справочники наследуют от секции `'references'`
- **Проверка:** `auth.can('references', action)`
- **Индивидуальные:** через `user_permissions` (опционально)

### Удаление элементов (ИСПРАВЛЕНО!)
- **Критическое правило:** удаление возможно ТОЛЬКО если элемент нигде не участвует
- **Проверка связей:** для всех таблиц в проекте
- **API endpoint:** возвращает ошибку с перечнем связей если они есть
- **UI:** показывает модальное окно с перечнем связей где участвует элемент

---

## 13. Итоговый список файлов

### API (1 файл)
- [ ] `api/salary-types.php`

### Frontend компоненты (25 файлов)
| Справочник | Table | Form | View | Actions | Delete | Итого |
|------------|-------|------|------|---------|--------|-------|
| clients | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| shooting_types | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| promotions | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| expense_categories | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| salary_types | ✅ | ✅ | ✅ | ✅ | ✅ | 5 |
| **Итого** | **5** | **5** | **5** | **5** | **5** | **25** |

### Изменение существующих (4 файла)
- [ ] `LaunchPad.vue`
- [ ] `permissions.ts`
- [ ] `App.vue`
- [ ] `navigation.ts` (опционально)

---

## 14. Критерии готовности (ИСПРАВЛЕНО!)

- [ ] Все 5 справочников имеют полный CRUD
- [ ] Кнопка для зарплат добавлена в LaunchPad
- [ ] Права доступа работают через единую секцию `'references'`
- [ ] Валидация форм соответствует БД
- [ ] Удаление с проверкой связей для ВСЕХ справочников
- [ ] Стили: glassmorphism + неоновые акценты (как в users/)
- [ ] Навигация из LaunchPad работает
- [ ] API для зарплат готов и протестирован
- [ ] Форма salary_type: 4 checkbox + input (НЕ взаимоисключающие)

---

*План исправлён: 1 апреля 2026*
*Исправления внесены по критическим замечаниям:*
1. *salary_type — НЕ рассчитывает зарплату, 4 checkbox + input НЕ взаимоисключающие*
2. *Права доступа — единая секция `references` для всех справочников*
3. *Удаление — проверка связей для ВСЕХ справочников*
4. *Стили — копировать users/ (padGlass, btnGlass, неон)*
5. *LaunchPad — кнопки есть, добавить только для зарплат*
