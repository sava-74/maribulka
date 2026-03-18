# User Roles Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Реализовать полноценную систему ролей и прав пользователей для салона (фотостудия + парикмахерская).

**Architecture:** Роли (admin/superuser/auser/prouser) как шаблон прав по умолчанию + таблица `user_permissions` для индивидуальных переопределений. Права загружаются один раз при логине в сессию. Frontend проверяет права через `hasPermission()` в auth store.

**Tech Stack:** Vue 3 + TypeScript + Pinia (frontend), PHP 8.4 + MySQL (backend), существующие компоненты padGlass/btnGlass/SelectBox/DatePicker.

**Spec:** `docs/superpowers/specs/2026-03-19-user-roles-design.md`

---

## Файловая карта

### Новые файлы
| Файл | Назначение |
| ---- | ---------- |
| `api/users.php` | CRUD пользователей + увольнение |
| `api/permissions.php` | Получение и изменение индивидуальных прав |
| `api/migrations/005_user_roles.sql` | Миграция БД: расширение users, создание user_permissions |
| `maribulka-vue/src/stores/permissions.ts` | Дефолтные права по ролям + hasPermission() |
| `maribulka-vue/src/components/users/UsersTable.vue` | Таблица пользователей |
| `maribulka-vue/src/components/users/UserFormModal.vue` | Модалка создания/редактирования пользователя |
| `maribulka-vue/src/components/users/UserActionsModal.vue` | Меню действий пользователя |
| `maribulka-vue/src/components/users/UserPermissionsModal.vue` | Матрица прав (только admin) |
| `maribulka-vue/src/components/users/FireUserModal.vue` | Модалка подтверждения увольнения |

### Модифицируемые файлы
| Файл | Что меняется |
| ---- | ------------ |
| `api/auth.php` | Возвращать role, specializations, permissions при check/login |
| `maribulka-vue/src/stores/auth.ts` | Добавить userRole, specializations, userPermissions; импортировать hasPermission |
| `maribulka-vue/src/components/launchpad/LaunchPad.vue` | Скрывать пункты по hasPermission(), добавить кнопку Пользователи |
| `maribulka-vue/src/components/calendar/BookingActionsModal.vue` | Скрывать кнопки по роли для ProUser |
| `maribulka-vue/src/stores/navigation.ts` | Добавить маршрут 'users' |
| `maribulka-vue/src/App.vue` | Подключить UsersTable по маршруту |

---

## Task 1: Миграция БД

**Files:**
- Create: `api/migrations/005_user_roles.sql`

- [ ] **Step 1: Написать SQL миграцию**

```sql
-- 005_user_roles.sql

-- 1. Расширяем таблицу users
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS role ENUM('admin','superuser','auser','prouser','user') NOT NULL DEFAULT 'prouser',
  ADD COLUMN IF NOT EXISTS full_name VARCHAR(255),
  ADD COLUMN IF NOT EXISTS is_photographer BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS is_hairdresser BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS is_admin_role BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS hired_at DATE,
  ADD COLUMN IF NOT EXISTS fired_at DATE,
  ADD COLUMN IF NOT EXISTS salary_type ENUM('fixed','percent','fixed_percent'),
  ADD COLUMN IF NOT EXISTS notes TEXT,
  ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT NOW();

-- 2. Существующий admin получает роль admin
UPDATE users SET role = 'admin' WHERE login = 'admin';

-- 3. Таблица индивидуальных прав
CREATE TABLE IF NOT EXISTS user_permissions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  section VARCHAR(64) NOT NULL,
  action VARCHAR(32) NOT NULL,
  allowed BOOLEAN NOT NULL,
  granted_by INT,
  created_at DATETIME DEFAULT NOW(),
  UNIQUE KEY uq_user_section_action (user_id, section, action),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
);
```

- [ ] **Step 2: Применить миграцию на сервере**

```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
mysql -u sava7424_mari -p sava7424_mari < migrations/005_user_roles.sql
```

Проверить: `DESCRIBE users;` — должны появиться новые колонки.

- [ ] **Step 3: Commit**

```bash
git add api/migrations/005_user_roles.sql
git commit -m "db: add user roles migration"
```

---

## Task 2: Backend — permissions.ts (дефолтные права)

**Files:**
- Create: `maribulka-vue/src/stores/permissions.ts`

- [ ] **Step 1: Создать файл дефолтных прав**

```typescript
// src/stores/permissions.ts
// Дефолтные права по ролям. Секции и actions совпадают с api/permissions.php

export type Role = 'admin' | 'superuser' | 'auser' | 'prouser' | 'user'
export type Section =
  | 'calendar' | 'bookings' | 'income' | 'expenses' | 'reports'
  | 'clients' | 'shooting_types' | 'promotions' | 'expense_categories'
  | 'users' | 'settings' | 'home' | 'news' | 'portfolio' | 'logs'
export type Action = 'view' | 'create' | 'edit' | 'delete'

// FULL = все 4 действия разрешены
// VIEW = только view
// NO   = ничего
// PART = задаётся вручную ниже
type AccessLevel = 'FULL' | 'VIEW' | 'NO'

const ROLE_DEFAULTS: Record<Role, Record<Section, AccessLevel>> = {
  admin: {
    calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL',
    reports: 'FULL', clients: 'FULL', shooting_types: 'FULL', promotions: 'FULL',
    expense_categories: 'FULL', users: 'FULL', settings: 'FULL',
    home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'FULL',
  },
  superuser: {
    calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL',
    reports: 'FULL', clients: 'FULL', shooting_types: 'FULL', promotions: 'FULL',
    expense_categories: 'FULL', users: 'FULL', settings: 'FULL',
    home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'NO',
  },
  auser: {
    calendar: 'FULL', bookings: 'FULL', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'FULL', shooting_types: 'VIEW', promotions: 'VIEW',
    expense_categories: 'NO', users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'FULL', portfolio: 'VIEW', logs: 'NO',
  },
  prouser: {
    calendar: 'VIEW', bookings: 'VIEW', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'NO', shooting_types: 'NO', promotions: 'NO',
    expense_categories: 'NO', users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO',
  },
  user: {
    calendar: 'NO', bookings: 'NO', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'NO', shooting_types: 'NO', promotions: 'NO',
    expense_categories: 'NO', users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO',
  },
}

// Проверка права с учётом индивидуальных переопределений
export function hasPermission(
  role: Role,
  section: Section,
  action: Action,
  overrides: Array<{ section: string; action: string; allowed: boolean }>
): boolean {
  // 1. Индивидуальное переопределение
  const override = overrides.find(p => p.section === section && p.action === action)
  if (override !== undefined) return override.allowed

  // 2. Дефолт роли
  const level = ROLE_DEFAULTS[role]?.[section] ?? 'NO'
  if (level === 'FULL') return true
  if (level === 'NO') return false
  // VIEW = только просмотр
  return action === 'view'
}
```

- [ ] **Step 2: Commit**

```bash
git add maribulka-vue/src/stores/permissions.ts
git commit -m "feat: add role permissions defaults"
```

---

## Task 3: Backend — auth.php расширение

**Files:**
- Modify: `api/auth.php`

- [ ] **Step 1: Добавить загрузку specializations и permissions в check и login**

В обоих местах где возвращается `user` (action=check и action=login) добавить загрузку из БД:

```php
// Добавить эту функцию перед if ($action === 'check'):
function buildUserResponse(array $user, PDO $pdo): array {
    // Специализации
    $specializations = [
        'photographer' => (bool)($user['is_photographer'] ?? false),
        'hairdresser'  => (bool)($user['is_hairdresser'] ?? false),
        'admin_role'   => (bool)($user['is_admin_role'] ?? false),
    ];

    // Индивидуальные переопределения прав
    $stmt = $pdo->prepare(
        "SELECT section, action, allowed FROM user_permissions WHERE user_id = ?"
    );
    $stmt->execute([$user['id']]);
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $perms = array_map(fn($p) => [
        'section' => $p['section'],
        'action'  => $p['action'],
        'allowed' => (bool)$p['allowed'],
    ], $permissions);

    return [
        'id'              => $user['id'],
        'login'           => $user['login'],
        'name'            => $user['name'] ?? $user['full_name'] ?? '',
        'role'            => $user['role'] ?? 'prouser',
        'specializations' => $specializations,
        'permissions'     => $perms,
    ];
}
```

В action=check заменить возврат user:
```php
// Было:
'user' => ['id' => ..., 'login' => ..., 'role' => ..., 'name' => ...]
// Стало:
'user' => buildUserResponse($_SESSION['user_full'], $pdo)
```

В action=login после успешной аутентификации сохранять полный объект в сессию:
```php
$_SESSION['user'] = [
    'id'    => $user['id'],
    'login' => $user['login'],
    'role'  => $user['role'],
    'name'  => $user['full_name'] ?? $user['name'] ?? '',
];
$_SESSION['user_full'] = $user; // полная запись из БД для buildUserResponse
```

- [ ] **Step 2: Commit**

```bash
git add api/auth.php
git commit -m "feat: auth returns role, specializations and permissions"
```

---

## Task 4: Frontend — auth store расширение

**Files:**
- Modify: `maribulka-vue/src/stores/auth.ts`

- [ ] **Step 1: Расширить auth store**

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useNavigationStore } from './navigation'
import { hasPermission, type Role, type Section, type Action } from './permissions'

export const useAuthStore = defineStore('auth', () => {
  const isAdmin = ref(false)  // оставляем для совместимости
  const isLoading = ref(false)
  const userName = ref('')
  const userId = ref<number | null>(null)
  const userRole = ref<Role>('prouser')
  const userSpecializations = ref({ photographer: false, hairdresser: false, admin_role: false })
  const userPermissions = ref<Array<{ section: string; action: string; allowed: boolean }>>([])
  let heartbeatTimer: ReturnType<typeof setInterval> | null = null

  // Проверка права
  function can(section: Section, action: Action): boolean {
    return hasPermission(userRole.value, section, action, userPermissions.value)
  }

  function setUser(user: any) {
    userRole.value = user.role ?? 'prouser'
    userName.value = user.name ?? ''
    userId.value = user.id ?? null
    userSpecializations.value = user.specializations ?? { photographer: false, hairdresser: false, admin_role: false }
    userPermissions.value = user.permissions ?? []
    isAdmin.value = user.role === 'admin' || user.role === 'superuser'
    localStorage.setItem('isAdmin', isAdmin.value ? 'true' : 'false')
  }

  function clearUser() {
    userRole.value = 'prouser'
    userName.value = ''
    userId.value = null
    userSpecializations.value = { photographer: false, hairdresser: false, admin_role: false }
    userPermissions.value = []
    isAdmin.value = false
    localStorage.removeItem('isAdmin')
  }

  // ... остальные методы (startHeartbeat, stopHeartbeat, checkSession, login, logout) —
  // заменить прямые присвоения на setUser(data.user) / clearUser()

  return {
    isAdmin,
    isAuthenticated: isAdmin,
    isLoading,
    userName,
    userId,
    userRole,
    userSpecializations,
    userPermissions,
    can,
    login,
    logout,
    checkSession,
  }
})
```

- [ ] **Step 2: Commit**

```bash
git add maribulka-vue/src/stores/auth.ts maribulka-vue/src/stores/permissions.ts
git commit -m "feat: auth store with role and hasPermission"
```

---

## Task 5: LaunchPad — скрытие пунктов по правам

**Files:**
- Modify: `maribulka-vue/src/components/launchpad/LaunchPad.vue`

- [ ] **Step 1: Добавить v-if по can() для каждого пункта лаунча**

Импортировать auth store, добавить `v-if` к каждой ячейке лаунча:

```vue
<script setup lang="ts">
import { useAuthStore } from '../../stores/auth'
const auth = useAuthStore()
</script>

<!-- Примеры: -->
<!-- Приход -->
<div class="pad-icon-cell" v-if="auth.can('income', 'view')">...</div>

<!-- Расходы -->
<div class="pad-icon-cell" v-if="auth.can('expenses', 'view')">...</div>

<!-- Клиенты -->
<div class="pad-icon-cell" v-if="auth.can('clients', 'view')">...</div>

<!-- Пользователи (новый пункт) -->
<div class="pad-icon-cell" v-if="auth.can('users', 'view')" @click="openUsers()">
  <button class="btnGlass bigIcon" @click="onRipple($event)">
    <span class="inner-glow"></span>
    <span class="top-shine"></span>
    <svg-icon type="mdi" :path="mdiAccountMultiple" class="btn-icon-big" />
  </button>
  <p class="pad-icon-label">Пользователи</p>
</div>

<!-- Настройки -->
<div class="pad-icon-cell" v-if="auth.can('settings', 'view')">...</div>
```

Добавить функцию навигации:
```typescript
function openUsers() {
  navStore.navigateTo('users')
  close()
}
```

- [ ] **Step 2: Добавить маршрут users в navigation store**

В `src/stores/navigation.ts` добавить `'users'` в список допустимых страниц.

- [ ] **Step 3: Подключить UsersTable в App.vue**

```vue
<!-- App.vue — добавить рядом с другими страницами -->
<UsersTable v-if="navStore.currentPage === 'users'" />
```

- [ ] **Step 4: Commit**

```bash
git add maribulka-vue/src/components/launchpad/LaunchPad.vue
git add maribulka-vue/src/stores/navigation.ts
git add maribulka-vue/src/App.vue
git commit -m "feat: launchpad hides items by role permissions"
```

---

## Task 6: BookingActionsModal — права ProUser

**Files:**
- Modify: `maribulka-vue/src/components/calendar/BookingActionsModal.vue`

- [ ] **Step 1: Скрыть кнопки для ProUser**

ProUser видит только: Просмотр + Оплата. Все остальные кнопки скрыты.

```vue
<script setup lang="ts">
import { useAuthStore } from '../../stores/auth'
const auth = useAuthStore()
const isProUser = computed(() => auth.userRole === 'prouser')
</script>

<!-- Редактировать — скрыть для ProUser -->
<button v-if="!isProUser" class="btnGlass iconTextStart" :disabled="!canEdit" @click="$emit('edit')">

<!-- Подтвердить съёмку — скрыть для ProUser -->
<button v-if="!isProUser" class="btnGlass iconTextStart" :disabled="!canConfirmSession" @click="$emit('confirmSession')">

<!-- Выдать заказ — скрыть для ProUser -->
<button v-if="!isProUser" class="btnGlass iconTextStart" :disabled="!canDeliver" @click="$emit('deliver')">

<!-- Отменить — скрыть для ProUser -->
<button v-if="!isProUser" class="btnGlass iconTextStart" :disabled="!canCancel" @click="$emit('cancel')">

<!-- Удалить — скрыть для ProUser -->
<button v-if="!isProUser" class="btnGlass iconTextStart" :disabled="!canDelete" @click="$emit('delete')">

<!-- Возврат — скрыть для ProUser -->
<button v-if="!isProUser && canRefund" class="btnGlass iconTextStart" @click="$emit('refund')">
```

- [ ] **Step 2: Commit**

```bash
git add maribulka-vue/src/components/calendar/BookingActionsModal.vue
git commit -m "feat: hide booking actions for prouser role"
```

---

## Task 7: Backend — api/users.php

**Files:**
- Create: `api/users.php`

- [ ] **Step 1: Создать endpoint пользователей**

```php
<?php
// api/users.php
header('Content-Type: application/json; charset=utf-8');
require_once 'session.php';
require_once 'database.php';
initSession();

// CORS (скопировать из auth.php)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:5173', 'https://xn--80aac1alfd7a3a5g.xn--p1ai'];
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(0); }

// Проверка авторизации и прав
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$currentRole = $_SESSION['user']['role'] ?? '';
if (!in_array($currentRole, ['admin', 'superuser'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$pdo = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? 'list';

// GET /api/users.php — список активных пользователей
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $stmt = $pdo->query(
        "SELECT id, full_name, login, role,
                is_photographer, is_hairdresser, is_admin_role,
                salary_type, hired_at, fired_at, notes, created_at
         FROM users
         WHERE fired_at IS NULL
         ORDER BY full_name"
    );
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// POST /api/users.php?action=create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);

    // SuperUser не может создавать admin/superuser
    if ($currentRole === 'superuser' && in_array($input['role'] ?? '', ['admin', 'superuser'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, login, password, role,
            is_photographer, is_hairdresser, is_admin_role,
            salary_type, hired_at, notes, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->execute([
        $input['full_name'], $input['login'], $input['password'],
        $input['role'] ?? 'prouser',
        (int)($input['is_photographer'] ?? 0),
        (int)($input['is_hairdresser'] ?? 0),
        (int)($input['is_admin_role'] ?? 0),
        $input['salary_type'] ?? null,
        $input['hired_at'] ?? null,
        $input['notes'] ?? null,
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// POST /api/users.php?action=update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);

    // SuperUser не может редактировать admin/superuser
    if ($currentRole === 'superuser') {
        $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $check->execute([$id]);
        $target = $check->fetch(PDO::FETCH_ASSOC);
        if ($target && in_array($target['role'], ['admin', 'superuser'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
            exit;
        }
    }

    $fields = "full_name=?, login=?, role=?,
               is_photographer=?, is_hairdresser=?, is_admin_role=?,
               salary_type=?, hired_at=?, notes=?";
    $params = [
        $input['full_name'], $input['login'], $input['role'] ?? 'prouser',
        (int)($input['is_photographer'] ?? 0),
        (int)($input['is_hairdresser'] ?? 0),
        (int)($input['is_admin_role'] ?? 0),
        $input['salary_type'] ?? null,
        $input['hired_at'] ?? null,
        $input['notes'] ?? null,
    ];
    // Обновить пароль только если передан
    if (!empty($input['password'])) {
        $fields .= ", password=?";
        $params[] = $input['password'];
    }
    $params[] = $id;
    $pdo->prepare("UPDATE users SET $fields WHERE id = ?")->execute($params);
    echo json_encode(['success' => true]);
    exit;
}

// POST /api/users.php?action=fire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'fire') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    $pdo->prepare("UPDATE users SET fired_at = CURDATE() WHERE id = ? AND fired_at IS NULL")
        ->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);
```

- [ ] **Step 2: Commit**

```bash
git add api/users.php
git commit -m "feat: users API endpoint"
```

---

## Task 8: Backend — api/permissions.php

**Files:**
- Create: `api/permissions.php`

- [ ] **Step 1: Создать endpoint управления правами**

```php
<?php
// api/permissions.php — только для admin
header('Content-Type: application/json; charset=utf-8');
require_once 'session.php';
require_once 'database.php';
initSession();

// CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:5173', 'https://xn--80aac1alfd7a3a5g.xn--p1ai'];
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(0); }

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Только admin']);
    exit;
}

$pdo = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';

// GET ?action=get&user_id=5 — получить права пользователя
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $userId = (int)($_GET['user_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT section, action, allowed FROM user_permissions WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// POST ?action=set — установить/обновить право
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'set') {
    $input = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare(
        "INSERT INTO user_permissions (user_id, section, action, allowed, granted_by)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE allowed = VALUES(allowed), granted_by = VALUES(granted_by)"
    );
    $stmt->execute([
        (int)$input['user_id'],
        $input['section'],
        $input['action'],
        (int)$input['allowed'],
        $_SESSION['user']['id'],
    ]);
    echo json_encode(['success' => true]);
    exit;
}

// POST ?action=reset — сбросить все права пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reset') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?")
        ->execute([(int)$input['user_id']]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);
```

- [ ] **Step 2: Commit**

```bash
git add api/permissions.php
git commit -m "feat: permissions API endpoint"
```

---

## Task 9: Frontend — UsersTable.vue

**Files:**
- Create: `maribulka-vue/src/components/users/UsersTable.vue`

- [ ] **Step 1: Создать компонент таблицы пользователей**

Структура компонента по аналогии с `ExpensesTable.vue`:

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import UserActionsModal from './UserActionsModal.vue'
import UserFormModal from './UserFormModal.vue'
import FireUserModal from './FireUserModal.vue'
import UserPermissionsModal from './UserPermissionsModal.vue'

const auth = useAuthStore()
const users = ref<any[]>([])
const selectedUser = ref<any>(null)
const showActions = ref(false)
const showForm = ref(false)
const showFire = ref(false)
const showPermissions = ref(false)
const isCreating = ref(false)

async function loadUsers() {
  const res = await fetch('/api/users.php?action=list', { credentials: 'include' })
  const data = await res.json()
  if (data.success) users.value = data.data
}

onMounted(loadUsers)

function onRowClick(user: any) {
  selectedUser.value = user
  showActions.value = true
}

function onAdd() {
  selectedUser.value = null
  isCreating.value = true
  showForm.value = true
}

function onEdit() {
  showActions.value = false
  isCreating.value = false
  showForm.value = true
}

function onFire() {
  showActions.value = false
  showFire.value = true
}

function onPermissions() {
  showActions.value = false
  showPermissions.value = true
}

async function onFormSave() {
  showForm.value = false
  await loadUsers()
}

async function onFireConfirm() {
  await fetch('/api/users.php?action=fire', {
    method: 'POST', credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: selectedUser.value.id })
  })
  showFire.value = false
  await loadUsers()
}
</script>

<template>
  <div class="padGlass padGlass-work">
    <!-- Toolbar -->
    <div class="table-toolbar">
      <span class="table-title">Пользователи</span>
      <button class="btnGlass iconText" @click="onAdd">
        <span class="inner-glow"></span><span class="top-shine"></span>
        <svg-icon type="mdi" :path="mdiPlus" class="btn-icon" />
        <span>Добавить</span>
      </button>
    </div>

    <!-- Таблица -->
    <table class="data-table">
      <thead>
        <tr>
          <th>ФИО</th>
          <th>Логин</th>
          <th>Тип</th>
          <th>Адм</th>
          <th>Фото</th>
          <th>Парик</th>
          <th>Зарплата</th>
          <th>Принят</th>
          <th>Примечания</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="users.length === 0">
          <td colspan="9" class="table-empty">+ Добавить пользователя</td>
        </tr>
        <tr v-for="user in users" :key="user.id" @click="onRowClick(user)" class="table-row-clickable">
          <td>{{ user.full_name }}</td>
          <td>{{ user.login }}</td>
          <td>{{ user.role }}</td>
          <td><span v-if="user.is_admin_role">✓</span></td>
          <td><span v-if="user.is_photographer">✓</span></td>
          <td><span v-if="user.is_hairdresser">✓</span></td>
          <td>{{ user.salary_type }}</td>
          <td>{{ user.hired_at }}</td>
          <td>{{ user.notes }}</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Модалки -->
  <UserActionsModal
    v-if="showActions"
    :user="selectedUser"
    :is-admin="auth.userRole === 'admin'"
    @close="showActions = false"
    @edit="onEdit"
    @fire="onFire"
    @permissions="onPermissions"
  />
  <UserFormModal
    v-if="showForm"
    :user="isCreating ? null : selectedUser"
    @close="showForm = false"
    @save="onFormSave"
  />
  <FireUserModal
    v-if="showFire"
    :user="selectedUser"
    @close="showFire = false"
    @confirm="onFireConfirm"
  />
  <UserPermissionsModal
    v-if="showPermissions"
    :user="selectedUser"
    @close="showPermissions = false"
  />
</template>
```

- [ ] **Step 2: Commit**

```bash
git add maribulka-vue/src/components/users/UsersTable.vue
git commit -m "feat: users table component"
```

---

## Task 10: Frontend — UserActionsModal, UserFormModal, FireUserModal

**Files:**
- Create: `maribulka-vue/src/components/users/UserActionsModal.vue`
- Create: `maribulka-vue/src/components/users/UserFormModal.vue`
- Create: `maribulka-vue/src/components/users/FireUserModal.vue`

- [ ] **Step 1: UserActionsModal — меню действий**

По аналогии с `BookingActionsModal.vue`:

```vue
<!-- UserActionsModal.vue -->
<script setup lang="ts">
const props = defineProps<{ user: any; isAdmin: boolean }>()
const emit = defineEmits(['close', 'edit', 'fire', 'permissions'])
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay" @click.self="$emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ user?.full_name }}</div>
        <div class="ButtonFooter PosCenter" style="flex-direction: column; gap: 8px;">
          <button class="btnGlass iconTextStart" @click="$emit('edit')">
            <span class="inner-glow"></span><span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiFileEditOutline" class="btn-icon" />
            <span>Редактировать</span>
          </button>
          <button v-if="isAdmin" class="btnGlass iconTextStart" @click="$emit('permissions')">
            <span class="inner-glow"></span><span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiShieldAccountOutline" class="btn-icon" />
            <span>Изменить права</span>
          </button>
          <button class="btnGlass iconTextStart" @click="$emit('fire')">
            <span class="inner-glow"></span><span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiAccountOffOutline" class="btn-icon" />
            <span>Уволить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
```

- [ ] **Step 2: UserFormModal — создание/редактирование**

Поля: ФИО, Логин, Пароль, Роль (SelectBox), Специализации (3 чекбокса), Тип зарплаты (SelectBox), Дата приёма (DatePicker), Примечания.

При редактировании поле пароля необязательно — если пустое, не отправлять в API.

Галочка "Уволен" показывается только при редактировании существующего пользователя. При установке — показать предупреждение: "Это необратимое действие. Уволенный сотрудник не сможет войти в систему." Подтверждение через `ConfirmModal`.

- [ ] **Step 3: FireUserModal — подтверждение увольнения**

```vue
<!-- FireUserModal.vue — простая модалка подтверждения -->
<template>
  <Teleport to="body">
    <div class="modal-overlay">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Уволить сотрудника</div>
        <p class="modal-message">
          Уволить <strong>{{ user?.full_name }}</strong>?
          Это действие необратимо. Сотрудник не сможет войти в систему.
          При повторном приёме создаётся новая запись.
        </p>
        <div class="ButtonFooter PosCenter">
          <button class="btnGlass iconText" @click="$emit('cancel')">
            <span class="inner-glow"></span><span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiClose" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="$emit('confirm')">
            <span class="inner-glow"></span><span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiAccountOffOutline" class="btn-icon" />
            <span>Уволить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
```

- [ ] **Step 4: Commit**

```bash
git add maribulka-vue/src/components/users/
git commit -m "feat: user modals (actions, form, fire)"
```

---

## Task 11: Frontend — UserPermissionsModal.vue

**Files:**
- Create: `maribulka-vue/src/components/users/UserPermissionsModal.vue`

- [ ] **Step 1: Создать матрицу прав**

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ROLE_DEFAULTS } from '../../stores/permissions'

const props = defineProps<{ user: any }>()
const emit = defineEmits(['close'])

const SECTIONS = [
  { key: 'calendar', label: 'Календарь' },
  { key: 'bookings', label: 'Записи' },
  { key: 'income', label: 'Доход' },
  { key: 'expenses', label: 'Расход' },
  { key: 'reports', label: 'Отчёты' },
  { key: 'clients', label: 'Клиенты' },
  { key: 'shooting_types', label: 'Типы съёмок' },
  { key: 'promotions', label: 'Акции' },
  { key: 'expense_categories', label: 'Категории расходов' },
  { key: 'settings', label: 'Настройки' },
]
const ACTIONS = ['view', 'create', 'edit', 'delete']

const overrides = ref<Record<string, Record<string, boolean | null>>>({})

onMounted(async () => {
  const res = await fetch(`/api/permissions.php?action=get&user_id=${props.user.id}`, { credentials: 'include' })
  const data = await res.json()
  // Заполнить overrides из data.data
  for (const p of data.data ?? []) {
    if (!overrides.value[p.section]) overrides.value[p.section] = {}
    overrides.value[p.section][p.action] = p.allowed
  }
})

function getState(section: string, action: string): 'default' | 'allow' | 'deny' {
  const override = overrides.value[section]?.[action]
  if (override === undefined || override === null) return 'default'
  return override ? 'allow' : 'deny'
}

async function toggle(section: string, action: string) {
  const current = getState(section, action)
  const next = current === 'default' ? 'allow' : current === 'allow' ? 'deny' : 'default'

  if (next === 'default') {
    // Удалить переопределение — пока через reset одного (упрощение)
    // TODO: добавить action=delete в permissions.php
    if (!overrides.value[section]) overrides.value[section] = {}
    overrides.value[section][action] = undefined as any
  } else {
    await fetch('/api/permissions.php?action=set', {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: props.user.id, section, action, allowed: next === 'allow' })
    })
    if (!overrides.value[section]) overrides.value[section] = {}
    overrides.value[section][action] = next === 'allow'
  }
}

async function resetAll() {
  await fetch('/api/permissions.php?action=reset', {
    method: 'POST', credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user_id: props.user.id })
  })
  overrides.value = {}
}
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay-main" @click.self="$emit('close')">
      <div class="padGlass modal-sm" style="min-width: 600px;">
        <div class="modal-glassTitle">Права: {{ user?.full_name }}</div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Раздел</th>
              <th v-for="a in ACTIONS" :key="a">{{ a }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in SECTIONS" :key="s.key">
              <td>{{ s.label }}</td>
              <td v-for="a in ACTIONS" :key="a">
                <button
                  class="btnGlass icon-only"
                  :class="getState(s.key, a)"
                  @click="toggle(s.key, a)"
                >
                  <span class="inner-glow"></span><span class="top-shine"></span>
                  <!-- default=серый, allow=зелёный, deny=красный — через CSS класс -->
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="ButtonFooter PosSpace">
          <button class="btnGlass iconText" @click="resetAll">
            <span class="inner-glow"></span><span class="top-shine"></span>
            <span>Сбросить всё</span>
          </button>
          <button class="btnGlass iconText" @click="$emit('close')">
            <span class="inner-glow"></span><span class="top-shine"></span>
            <span>Закрыть</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
```

- [ ] **Step 2: Добавить в permissions.php action=delete (один override)**

```php
// POST ?action=delete — удалить одно переопределение
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pdo->prepare(
        "DELETE FROM user_permissions WHERE user_id = ? AND section = ? AND action = ?"
    )->execute([(int)$input['user_id'], $input['section'], $input['action']]);
    echo json_encode(['success' => true]);
    exit;
}
```

- [ ] **Step 3: Commit**

```bash
git add maribulka-vue/src/components/users/UserPermissionsModal.vue api/permissions.php
git commit -m "feat: user permissions matrix modal"
```

---

## Task 12: Проверка и деплой

- [ ] **Step 1: Запустить dev сервер и проверить вручную**

```bash
cd maribulka-vue
npm run dev
```

Проверить:
- [ ] Войти как admin — лаунч показывает все пункты включая "Пользователи"
- [ ] Раздел Пользователи открывается, таблица загружается
- [ ] Создать нового ProUser — проверить что права по умолчанию работают
- [ ] Войти как ProUser — лаунч показывает только Календарь и Записи
- [ ] В календаре у ProUser — только кнопки Просмотр и Оплата
- [ ] Admin может открыть "Изменить права" для пользователя
- [ ] Переопределить право — проверить что применяется

- [ ] **Step 2: Сборка**

```bash
cd maribulka-vue
npm run build
```

Убедиться что нет ошибок TypeScript.

- [ ] **Step 3: Деплой**

```bash
git add . && git commit -m "feat: user roles system complete"
git push
.\deploy.ps1
```
