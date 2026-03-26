# User Permissions Matrix — Защита прав пользователей

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Реализовать матрицу прав доступа к операциям над пользователями согласно диаграмме.

## Матрица прав (эталон)

| Кто \ Чья форма | admin | SuperUser | SuperUser1 | AUser/User/ProUser |
| --- | --- | --- | --- | --- |
| **admin** | Просмотр | Просмотр | Просмотр | Создать + Просмотр + Редактировать |
| **SuperUser** | Просмотр + Редактировать | Просмотр + Редактировать | Создать + Просмотр + Редактировать + Уволить | Создать + Просмотр + Редактировать + Уволить |
| **SuperUser1** | — | — | Просмотр | Создать + Просмотр + Редактировать + Уволить |

## Роли в БД (enum)

`admin`, `superuser`, `superuser1`, `auser`, `prouser`, `user`

## Что меняется в текущем коде

**Должно быть (строго по таблице):**

- `admin` → форма admin: только Просмотр
- `admin` → форма superuser: только Просмотр
- `admin` → форма superuser1: только Просмотр
- `admin` → форма auser/prouser/user: Создать + Просмотр + Редактировать (уволить — НЕТ)
- `superuser` → форма admin: Просмотр + Редактировать
- `superuser` → форма superuser: Просмотр + Редактировать
- `superuser` → форма superuser1: Создать + Просмотр + Редактировать + Уволить
- `superuser` → форма auser/prouser/user: Создать + Просмотр + Редактировать + Уволить
- `superuser1` → форма admin: ничего
- `superuser1` → форма superuser: ничего
- `superuser1` → форма superuser1: Просмотр
- `superuser1` → форма auser/prouser/user: Создать + Просмотр + Редактировать + Уволить

---

## Task 1: Обновить `api/users.php`

**Files:**

- Modify: `api/users.php`

- [ ] **Step 1: Расширить доступ — добавить `superuser` к списку разрешённых ролей**

Текущая проверка уже пропускает `admin` и `superuser`. Но логика внутри action=update неверная.

- [ ] **Step 2: Обновить логику `action=create`**

```php
// admin не может создавать admin, superuser, superuser1
if ($currentRole === 'admin' && in_array($input['role'] ?? '', ['admin', 'superuser', 'superuser1'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав для создания пользователя данной роли']);
    exit;
}
// superuser не может создавать admin
if ($currentRole === 'superuser' && ($input['role'] ?? '') === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав для создания пользователя данной роли']);
    exit;
}
```

- [ ] **Step 3: Обновить логику `action=update`**

```php
// Получаем роль редактируемого пользователя
$check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$check->execute([$id]);
$target = $check->fetch(PDO::FETCH_ASSOC);
$targetRole = $target['role'] ?? ''; 

// admin не может редактировать admin, superuser, superuser1
if ($currentRole === 'admin' && in_array($targetRole, ['admin', 'superuser', 'superuser1'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}
// superuser не может менять роль на admin
if ($currentRole === 'superuser' && ($input['role'] ?? '') === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Нельзя назначить роль admin']);
    exit;
}
```

- [ ] **Step 4: Обновить логику `action=fire`**

```php
// Получаем роль увольняемого
$check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$check->execute([$id]);
$target = $check->fetch(PDO::FETCH_ASSOC);
$targetRole = $target['role'] ?? '';

// admin вообще не может уволить никого
if ($currentRole === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}
// никто не может уволить admin или superuser (только superuser может уволить superuser1)
if (in_array($targetRole, ['admin', 'superuser'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Нельзя уволить этого пользователя']);
    exit;
}
```

---

## Task 2: Обновить `UserActionsModal.vue`

**Files:**

- Modify: `maribulka-vue/src/components/users/UserActionsModal.vue`

Текущий prop `isAdmin: boolean` заменить на `currentRole: string` — нужно знать точную роль текущего пользователя для матрицы.

- [ ] **Step 1: Обновить props**

```typescript
// Было:
const props = defineProps<{ user: User; isAdmin: boolean; isEmpty?: boolean }>()

// Стало:
const props = defineProps<{ user: User; currentRole: string; isEmpty?: boolean }>()
```

- [ ] **Step 2: Вычисляемые флаги видимости кнопок**

```typescript
import { computed } from 'vue'

// Можно ли создать нового пользователя
const canCreate = computed(() => {
  if (props.currentRole === 'admin') return !['admin', 'superuser'].includes(props.user.role) || props.isEmpty
  if (props.currentRole === 'superuser') return true
  return false
})

// Можно ли редактировать
const canEdit = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if (props.currentRole === 'admin') return !['admin', 'superuser'].includes(props.user.role)
  if (props.currentRole === 'superuser') return true // может редактировать всех включая admin
  return false
})

// Можно ли уволить
const canFire = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if (props.user.role === 'admin') return false // никто не может уволить admin
  if (props.currentRole === 'admin') return !['admin', 'superuser'].includes(props.user.role)
  if (props.currentRole === 'superuser') return true
  return false
})

// Можно ли изменить права
const canPermissions = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if (props.user.role === 'admin') return false
  if (props.currentRole === 'admin') return !['admin', 'superuser'].includes(props.user.role)
  if (props.currentRole === 'superuser') return props.user.role !== 'admin'
  return false
})
```

- [ ] **Step 3: Обновить шаблон — заменить v-if на computed**

```html
<!-- Просмотр — всегда (если не пустая строка) -->
<button class="btnGlass iconTextStart" :disabled="props.isEmpty" @click="$emit('view')">...</button>

<!-- Добавить — по canCreate -->
<button v-if="canCreate || props.isEmpty" class="btnGlass iconTextStart" @click="$emit('add')">...</button>

<!-- Редактировать — по canEdit -->
<button v-if="canEdit" class="btnGlass iconTextStart" @click="$emit('edit')">...</button>

<!-- Изменить права — по canPermissions -->
<button v-if="canPermissions" class="btnGlass iconTextStart" @click="$emit('permissions')">...</button>

<!-- Уволить — по canFire -->
<button v-if="canFire" class="btnGlass iconTextStart" @click="$emit('fire')">...</button>
```

---

## Task 3: Обновить `UsersTable.vue`

**Files:**

- Modify: `maribulka-vue/src/components/users/UsersTable.vue`

- [ ] **Step 1: Обновить передачу prop в UserActionsModal**

```html
<!-- Было: -->
<UserActionsModal :is-admin="auth.userRole === 'admin'" ... />

<!-- Стало: -->
<UserActionsModal :current-role="auth.userRole ?? ''" ... />
```

---

## Порядок применения

1. `api/users.php` — логика create, update, fire
2. `UserActionsModal.vue` — props + computed + шаблон
3. `UsersTable.vue` — передача currentRole
4. Сборка + деплой
