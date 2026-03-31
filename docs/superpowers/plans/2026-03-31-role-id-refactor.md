# Role ID Refactor — замена строковых ролей на числовые id

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Заменить все сравнения `role` со строками на сравнение по числовому `id` из таблицы `user_permissions`. Дропдаун ролей загружается из БД — `value: id` (число, для логики), `label: name` (кириллица, для отображения).

**Architecture:** `users.role` теперь `int` (FK → `user_permissions.id`). Никаких констант, никаких строковых id — везде чистые числа. SelectBox везде работает с `value: number`.

**Tech Stack:** Vue 3 + TypeScript, PHP 8.4, MySQL

---

## Справочник ролей (только для чтения плана)

```
id=1  name=СисАдмин
id=2  name=Директор
id=3  name=Руководитель
id=4  name=Администратор
id=5  name=Работник
id=6  name=Пользователь
```

---

## Порядок выполнения

1. Backend: `api/users.php`, `api/auth.php`
2. Backend: `api/permissions.php`, `api/home_blocks.php`, `api/upload-image.php`, `api/studio_description.php`, `api/setup_home_blocks.php`
3. Frontend stores: `stores/permissions.ts`, `stores/auth.ts`
4. Frontend компоненты: UserFormModal, UsersTable, ViewUserModal, UserActionsModal, UserPermissionsModal, BookingActionsModal

---

### Task 1: api/users.php

**Files:** Modify: `api/users.php`

- [ ] **1.1 Endpoint `action=permissions`** — добавить после `action=salary_types`:

```php
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'permissions') {
    $stmt = $pdo->query("SELECT id, name FROM user_permissions WHERE active = 1 ORDER BY id");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
```

- [ ] **1.2 Guard** — строку на int:

```php
$currentRole = (int)($_SESSION['user']['role'] ?? 0);
if (!in_array($currentRole, [1, 2, 3])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}
```

- [ ] **1.3 SELECT в `action=list` и `action=get`** — добавить JOIN и поле:

```sql
LEFT JOIN user_permissions ur ON u.role = ur.id
```
Добавить в SELECT: `ur.name AS permission_name`

- [ ] **1.4 Проверки при `create`**:

```php
$inputRole = (int)($input['role'] ?? 0);
if (empty($inputRole)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Укажите роль пользователя']);
    exit;
}
// admin(1) не может создавать 1,2,3
if ($currentRole === 1 && in_array($inputRole, [1, 2, 3])) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Недостаточно прав']); exit;
}
// superuser(2) не может создавать admin(1)
if ($currentRole === 2 && $inputRole === 1) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Недостаточно прав']); exit;
}
// superuser1(3) не может создавать 1,2,3
if ($currentRole === 3 && in_array($inputRole, [1, 2, 3])) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Недостаточно прав']); exit;
}
// superuser(2) — только 1 активный
$countSU = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 2 AND fired_at IS NULL")->fetchColumn();
if ($inputRole === 2 && $countSU >= 1) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'Директор уже существует']); exit;
}
// superuser1(3) — только 1 активный
$countSU1 = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 3 AND fired_at IS NULL")->fetchColumn();
if ($inputRole === 3 && $countSU1 >= 1) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'Руководитель уже существует']); exit;
}
```

В INSERT заменить: `$input['role'] ?? 'prouser'` → `$inputRole ?: 5`

- [ ] **1.5 Проверки при `update`**:

```php
$targetRole = (int)($target['role'] ?? 0);
// admin(1) не может редактировать 1,2,3
if ($currentRole === 1 && in_array($targetRole, [1, 2, 3])) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Недостаточно прав']); exit;
}
// superuser1(3) не может редактировать 1,2
if ($currentRole === 3 && in_array($targetRole, [1, 2])) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Недостаточно прав']); exit;
}
// Для вечных (1,2) роль не меняется
$roleToSave = in_array($targetRole, [1, 2]) ? $targetRole : ((int)($input['role'] ?? $targetRole) ?: $targetRole);
```

- [ ] **1.6 Проверки при `fire`**:

```php
$targetRole = (int)($target['role'] ?? 0);
if ($currentRole === 1) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Недостаточно прав']); exit;
}
if (in_array($targetRole, [1, 2])) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Нельзя уволить']); exit;
}
if ($currentRole === 3 && $targetRole === 3) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Недостаточно прав']); exit;
}
```

---

### Task 2: api/auth.php

**Files:** Modify: `api/auth.php`

- [ ] **2.1 SELECT пользователя** — добавить JOIN:

```sql
LEFT JOIN user_permissions up ON u.role = up.id
```
Добавить в SELECT: `up.name AS permission_name`

- [ ] **2.2 `buildUserResponse`** — `role` как int, добавить `permission_name`:

```php
return [
    'id'             => (int)$user['id'],
    'login'          => $user['login'],
    'name'           => $user['full_name'] ?? $user['name'] ?? '',
    'role'           => (int)($user['role'] ?? 5),
    'permission_name'=> $user['permission_name'] ?? '',
    'specializations'=> $specializations,
    'permissions'    => $perms,
];
```

- [ ] **2.3 `mustChangePassword`** — по числовым id:

```php
$userRoleId = (int)($user['role'] ?? 0);
if (in_array($userRoleId, [1, 2]) && $password === '123') {
    $mustChangePassword = true;
}
```

- [ ] **2.4 Сессия** — сохранять `role` как int:

```php
$_SESSION['user'] = [
    'id'    => $user['id'],
    'login' => $user['login'],
    'role'  => (int)$user['role'],
    'name'  => $user['name'] ?? ''
];
```

---

### Task 3: api/permissions.php + остальные PHP файлы

**Files:** Modify: `api/permissions.php`, `api/home_blocks.php`, `api/upload-image.php`, `api/studio_description.php`, `api/setup_home_blocks.php`

- [ ] **3.1 `permissions.php` guard**:
```php
if (!isset($_SESSION['user']) || (int)($_SESSION['user']['role'] ?? 0) !== 1) {
```

- [ ] **3.2 `permissions.php` проверки `$targetUser['role']`**:
```php
if (in_array((int)($targetUser['role'] ?? 0), [1, 2])) {
```

- [ ] **3.3 `home_blocks.php`**:
```php
if (!isset($_SESSION['user']) || (int)($_SESSION['user']['role'] ?? 0) !== 1) {
```

- [ ] **3.4 `upload-image.php`**:
```php
if (!isset($_SESSION['user']) || (int)($_SESSION['user']['role'] ?? 0) !== 1) {
```

- [ ] **3.5 `studio_description.php`**:
```php
if (!isset($_SESSION['user']) || (int)($_SESSION['user']['role'] ?? 0) !== 1) {
```

- [ ] **3.6 `setup_home_blocks.php`**:
```php
if (!isset($_SESSION['user']) || (int)($_SESSION['user']['role'] ?? 0) !== 1) {
```

---

### Task 4: stores/permissions.ts

**Files:** Modify: `maribulka-vue/src/stores/permissions.ts`

- [ ] **4.1 Тип `Role` → `number`**:

```typescript
export type Role = number
```

- [ ] **4.2 `ROLE_DEFAULTS`** — числовые ключи вместо строковых:

```typescript
export const ROLE_DEFAULTS: Record<number, Record<Section, AccessLevel>> = {
  1: { calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL', reports: 'FULL', clients: 'FULL', shooting_types: 'FULL', promotions: 'FULL', expense_categories: 'FULL', users: 'FULL', settings: 'FULL', home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'FULL' },
  2: { calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL', reports: 'FULL', clients: 'FULL', shooting_types: 'FULL', promotions: 'FULL', expense_categories: 'FULL', users: 'FULL', settings: 'FULL', home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'NO' },
  3: { calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL', reports: 'FULL', clients: 'FULL', shooting_types: 'FULL', promotions: 'FULL', expense_categories: 'FULL', users: 'FULL', settings: 'FULL', home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'NO' },
  4: { calendar: 'FULL', bookings: 'FULL', income: 'NO',   expenses: 'NO',   reports: 'NO',   clients: 'FULL', shooting_types: 'VIEW', promotions: 'VIEW', expense_categories: 'NO', users: 'NO', settings: 'NO', home: 'VIEW', news: 'FULL', portfolio: 'VIEW', logs: 'NO' },
  5: { calendar: 'VIEW', bookings: 'VIEW', income: 'NO',   expenses: 'NO',   reports: 'NO',   clients: 'NO',   shooting_types: 'NO',   promotions: 'NO',   expense_categories: 'NO', users: 'NO', settings: 'NO', home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO' },
  6: { calendar: 'NO',   bookings: 'NO',   income: 'NO',   expenses: 'NO',   reports: 'NO',   clients: 'NO',   shooting_types: 'NO',   promotions: 'NO',   expense_categories: 'NO', users: 'NO', settings: 'NO', home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO' },
}
```

- [ ] **4.3 `hasPermission`** — тип `role: number` (без изменений в логике)

---

### Task 5: stores/auth.ts

**Files:** Modify: `maribulka-vue/src/stores/auth.ts`

- [ ] **5.1 `userRole`** — тип `number`, дефолт `5`:

```typescript
const userRole = ref<number>(5)
```

- [ ] **5.2 `setUser`**:

```typescript
userRole.value = (user.role as number) ?? 5
isAdmin.value = userRole.value === 1 || userRole.value === 2
```

- [ ] **5.3 `clearUser`**:

```typescript
userRole.value = 5
```

---

### Task 6: UserFormModal.vue

**Files:** Modify: `maribulka-vue/src/components/users/UserFormModal.vue`

- [ ] **6.1** `form.role: number`, дефолт `5`
- [ ] **6.2** `isAdminUser = computed(() => form.value.role === 1)`
- [ ] **6.3** `isSuperUser = computed(() => form.value.role === 2)`
- [ ] **6.4** `isAdminRoleDisabled = computed(() => isAdminUser.value || [2,3,4,5].includes(form.value.role))`
- [ ] **6.5** `watch` на role по числам:

```typescript
watch(() => form.value.role, (role) => {
  if ([2, 3, 4].includes(role)) form.value.is_admin_role = true
  else if (role === 5) form.value.is_admin_role = false
})
```

- [ ] **6.6** Убрать computed `roleOptions`, добавить `ref` и грузить из `action=permissions`:

```typescript
const roleOptions = ref<{ value: number; label: string }[]>([])
const allRoleOptions = ref<{ value: number; label: string }[]>([])

const availableRoleOptions = computed(() => {
  if (isAdminUser.value || isSuperUser.value) {
    return allRoleOptions.value.filter(r => r.value === form.value.role)
  }
  // superuser(2) и superuser1(3) — только 1 экземпляр
  return roleOptions.value.filter(r => {
    if ([2, 3].includes(r.value) && takenRoles.value.includes(r.value)) return false
    return true
  })
})
```

- [ ] **6.7** В `onMounted` шаг загрузки permissions:

```typescript
const res = await fetch('/api/users.php?action=permissions', { credentials: 'include' })
const data = await res.json()
if (data.success) {
  allRoleOptions.value = data.data.map((r: any) => ({ value: r.id, label: r.name }))
  roleOptions.value = data.data.filter((r: any) => r.id !== 1).map((r: any) => ({ value: r.id, label: r.name }))
}
```

- [ ] **6.8** `takenRoles: number[]`, заполнять `others.map((u: any) => u.role as number)`
- [ ] **6.9** При загрузке данных пользователя: `role: user.role as number ?? 5`
- [ ] **6.10** В `save()` — `payload.role` уже `number`, конвертация не нужна
- [ ] **6.11** Шаблон: `:options="availableRoleOptions"`

---

### Task 7: UsersTable.vue

**Files:** Modify: `maribulka-vue/src/components/users/UsersTable.vue`

- [ ] **7.1** Интерфейс `User` — `role: number`, добавить `permission_name: string | null`
- [ ] **7.2** Удалить `ROLE_LABELS`
- [ ] **7.3** Шаблон: `{{ user.permission_name ?? user.role }}`

---

### Task 8: ViewUserModal.vue

**Files:** Modify: `maribulka-vue/src/components/users/ViewUserModal.vue`

- [ ] **8.1** Интерфейс `User` — `role: number`, добавить `permission_name: string | null`
- [ ] **8.2** Удалить `ROLE_LABELS`
- [ ] **8.3** Шаблон: `{{ user.permission_name ?? user.role }}`

---

### Task 9: UserActionsModal.vue

**Files:** Modify: `maribulka-vue/src/components/users/UserActionsModal.vue`

- [ ] **9.1** `currentRole: number`, `user.role: number`
- [ ] **9.2** Все сравнения по числам:

```typescript
const canCreate = computed(() => [1, 2, 3].includes(props.currentRole))

const canEdit = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if (props.currentRole === 1) return ![1, 2, 3].includes(props.user.role)
  if (props.currentRole === 2) return true
  if (props.currentRole === 3) return ![1, 2].includes(props.user.role)
  return false
})

const canFire = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if (props.currentRole === 1) return false
  if ([1, 2].includes(props.user.role)) return false
  if (props.currentRole === 2) return true
  if (props.currentRole === 3) return ![1, 2, 3].includes(props.user.role)
  return false
})

const canPermissions = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if ([1, 2].includes(props.user.role)) return false
  if (props.currentRole === 1) return ![1, 2, 3].includes(props.user.role)
  if (props.currentRole === 2) return true
  return false
})
```

---

### Task 10: UserPermissionsModal.vue

**Files:** Modify: `maribulka-vue/src/components/users/UserPermissionsModal.vue`

- [ ] **10.1** `user.role: number`
- [ ] **10.2** `isReadOnly = computed(() => [1, 2].includes(props.user.role))`
- [ ] **10.3** `hasPermission(props.user.role, section, action, [])` — role уже number, cast убрать

---

### Task 11: BookingActionsModal.vue

**Files:** Modify: `maribulka-vue/src/components/calendar/BookingActionsModal.vue`

- [ ] **11.1** `const isProUser = computed(() => auth.userRole === 5)`

---

### Task 12: Сборка и проверка

- [ ] `cd maribulka-vue && npm run build` — без ошибок
- [ ] Логин admin (role=1) работает, isAdmin = true
- [ ] Дропдаун ролей в форме показывает кириллицу из БД
- [ ] Таблица пользователей показывает `permission_name` на кириллице
- [ ] Кнопки действий (редактировать/уволить) работают по матрице прав
