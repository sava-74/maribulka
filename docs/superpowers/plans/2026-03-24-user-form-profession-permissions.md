# UserFormModal — Профессия, Права, Матрица доступа

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:**
1. Добавить поле "Профессия" в форму пользователя (из таблицы `profession`)
2. Переименовать "Роль" → "Права", обновить список ролей
3. Исправить видимость полей для admin
4. Email — необязательное
5. Реализовать матрицу прав доступа к операциям над пользователями
6. Запретить изменение прав для admin и superuser

---

## Матрица прав (эталон)

| Кто \ Чья форма | admin | superuser | superuser1 | auser/prouser/user |
|---|---|---|---|---|
| **admin** | Просмотр | Просмотр | Просмотр | Создать + Просмотр + Редактировать |
| **superuser** | Просмотр + Редактировать | Просмотр + Редактировать | Создать + Просмотр + Редактировать + Уволить | Создать + Просмотр + Редактировать + Уволить |
| **superuser1** | — | — | Просмотр | Создать + Просмотр + Редактировать + Уволить |

---

## БД справка

**Таблица `profession`:** id, title, active, notes
- 1 | СисАдмин | — для admin (фиксировано)
- 2 | Директор | — для superuser (фиксировано)
- 3 | Руководитель
- 4 | Администратор
- 5 | Фотограф
- 6 | Парикмахер

**Колонка в users:** `id_profession INT` (FK → profession.id)

**Роли enum:** `admin`, `superuser`, `superuser1`, `auser`, `prouser`, `user`

---

## Task 1: Обновить `api/users.php`

**Files:**
- Modify: `api/users.php`

- [ ] **Step 1: Добавить `id_profession` в SELECT**

```sql
SELECT id, full_name, login, role, id_profession,
       is_photographer, is_hairdresser, is_admin_role,
       salary_type, hired_at, fired_at, notes, created_at,
       region, city, street, house_building, flat,
       phone_user, email_user, date_of_birth
FROM users
ORDER BY fired_at IS NOT NULL, full_name
```

- [ ] **Step 2: Добавить `id_profession` в INSERT**

В список колонок добавить `id_profession`, в VALUES добавить `?`, в параметры:
```php
!empty($input['id_profession']) ? (int)$input['id_profession'] : null,
```

- [ ] **Step 3: Добавить `id_profession` в UPDATE `$fields` и `$params`**

```php
$fields = "full_name=?, login=?, role=?, id_profession=?, ...";
// в params:
!empty($input['id_profession']) ? (int)$input['id_profession'] : null,
```

- [ ] **Step 4: Обновить логику `action=create` — защита по матрице**

```php
// Получить кол-во активных superuser и superuser1
$countSU = $pdo->query("SELECT COUNT(*) FROM users WHERE role='superuser' AND fired_at IS NULL")->fetchColumn();
$countSU1 = $pdo->query("SELECT COUNT(*) FROM users WHERE role='superuser1' AND fired_at IS NULL")->fetchColumn();

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
// Нельзя создать superuser если уже есть 1
if (($input['role'] ?? '') === 'superuser' && $countSU >= 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Пользователь с ролью Руководитель уже существует']);
    exit;
}
// Нельзя создать superuser1 если уже есть 1
if (($input['role'] ?? '') === 'superuser1' && $countSU1 >= 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Пользователь с ролью Руководитель 2 уже существует']);
    exit;
}
```

- [ ] **Step 5: Обновить логику `action=update` — защита по матрице**

```php
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
// superuser не может назначить роль admin
if ($currentRole === 'superuser' && ($input['role'] ?? '') === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Нельзя назначить роль admin']);
    exit;
}
```

- [ ] **Step 6: Обновить логику `action=fire` — защита по матрице**

```php
$check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$check->execute([$id]);
$target = $check->fetch(PDO::FETCH_ASSOC);
$targetRole = $target['role'] ?? '';

// admin не может уволить никого
if ($currentRole === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}
// никто не может уволить admin или superuser
if (in_array($targetRole, ['admin', 'superuser'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Нельзя уволить этого пользователя']);
    exit;
}
```

- [ ] **Step 7: Добавить endpoint `action=professions` — список профессий**

```php
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'professions') {
    $stmt = $pdo->query("SELECT id, title FROM profession WHERE active = 1 ORDER BY id");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
```

- [ ] **Step 8: Запретить изменение прав для admin и superuser в `permissions.php`**

В начало action=set и action=delete добавить:
```php
$check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$check->execute([$userId]);
$target = $check->fetch(PDO::FETCH_ASSOC);
if (in_array($target['role'] ?? '', ['admin', 'superuser'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Права этого пользователя не изменяемы']);
    exit;
}
```

---

## Task 2: Обновить `UserFormModal.vue`

**Files:**
- Modify: `maribulka-vue/src/components/users/UserFormModal.vue`

- [ ] **Step 1: Добавить `id_profession` в interface User и form**

```typescript
// interface:
id_profession: number | null

// form ref:
id_profession: null as number | null,
```

- [ ] **Step 2: Добавить маппинг в watch**

```typescript
id_profession: user.id_profession ?? null,
```

- [ ] **Step 3: Загрузить список профессий при монтировании**

```typescript
const professionOptions = ref<{ value: string; label: string }[]>([])

onMounted(async () => {
  const res = await fetch('/api/users.php?action=professions', { credentials: 'include' })
  const data = await res.json()
  if (data.success) {
    professionOptions.value = data.data.map((p: any) => ({ value: String(p.id), label: p.title }))
  }
})
```

- [ ] **Step 4: Обновить список ролей с учётом лимитов**

Загружать занятые роли и убирать из списка:
```typescript
const takenRoles = ref<string[]>([])

onMounted(async () => {
  // ... загрузка профессий ...
  const res2 = await fetch('/api/users.php?action=list', { credentials: 'include' })
  const data2 = await res2.json()
  if (data2.success) {
    const active = data2.data.filter((u: any) => !u.fired_at)
    // если редактируем — исключаем текущего пользователя
    const others = props.user ? active.filter((u: any) => u.id !== props.user!.id) : active
    takenRoles.value = others.map((u: any) => u.role)
  }
})

const roleOptions = computed(() => {
  const all = [
    { value: 'superuser', label: 'Руководитель' },
    { value: 'superuser1', label: 'Руководитель 2' },
    { value: 'auser', label: 'Администратор' },
    { value: 'prouser', label: 'Работник' },
  ]
  return all.filter(r => !takenRoles.value.includes(r.value))
})
```

- [ ] **Step 5: Обновить вычисляемые свойства для admin**

```typescript
// Для admin: профессия и зарплата фиксированы, роль/специализации скрыты
const isAdminUser = computed(() => props.user?.role === 'admin')
const isSuperUser = computed(() => props.user?.role === 'superuser')

// Профессия фиксирована для admin (id=1) и superuser (id=2)
const isProfessionFixed = computed(() => isAdminUser.value || isSuperUser.value)
```

- [ ] **Step 6: Обновить валидацию в save()**

- Убрать проверку `email_user` (необязательное)
- Добавить проверку `id_profession`

```typescript
if (!form.value.id_profession) { alertMessage.value = 'Укажите профессию'; return }
// убрать: if (!form.value.email_user.trim()) { ... }
```

- [ ] **Step 7: Обновить шаблон**

Добавить поле "Профессия" перед полем "Права". Переименовать label "Роль *" → "Права *".
Скрыть для admin: поле "Права", "Специализации". Тип зарплаты для admin — disabled, значение 'fixed'.

```html
<!-- Профессия * — перед Правами -->
<div class="input-group">
  <label class="input-label">Профессия *</label>
  <SelectBox v-model="form.id_profession" :options="professionOptions"
    placeholder="Выберите профессию" :disabled="isProfessionFixed" />
</div>

<!-- Права * — скрыто для admin -->
<div v-if="!isAdminUser" class="input-field">
  <label class="input-label">Права *</label>
  <SelectBox v-model="form.role" :options="roleOptions" placeholder="Выберите права" :disabled="isSuperUser" />
</div>

<!-- Специализации * — скрыто для admin -->
<div v-if="!isAdminUser" class="input-group">
  <label class="input-label">Специализации *</label>
  ...
</div>

<!-- Тип зарплаты — для admin disabled + значение fixed -->
<div class="input-field">
  <label class="input-label">Тип зарплаты *</label>
  <SelectBox v-model="form.salary_type" :options="salaryOptions"
    placeholder="Тип зарплаты" :disabled="isAdminUser" />
</div>
```

---

## Task 3: Обновить `UserActionsModal.vue`

**Files:**
- Modify: `maribulka-vue/src/components/users/UserActionsModal.vue`

- [ ] **Step 1: Заменить prop `isAdmin` на `currentRole`**

```typescript
const props = defineProps<{ user: User; currentRole: string; isEmpty?: boolean }>()
```

- [ ] **Step 2: Добавить computed флаги**

```typescript
import { computed } from 'vue'

const canCreate = computed(() => ['admin', 'superuser', 'superuser1'].includes(props.currentRole))

const canEdit = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if (props.currentRole === 'admin') return !['admin', 'superuser', 'superuser1'].includes(props.user.role)
  if (props.currentRole === 'superuser') return true
  if (props.currentRole === 'superuser1') return !['admin', 'superuser'].includes(props.user.role)
  return false
})

const canFire = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if (props.currentRole === 'admin') return false
  if (['admin', 'superuser'].includes(props.user.role)) return false
  if (props.currentRole === 'superuser') return true
  if (props.currentRole === 'superuser1') return !['admin', 'superuser', 'superuser1'].includes(props.user.role)
  return false
})

const canPermissions = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if (['admin', 'superuser'].includes(props.user.role)) return false
  if (props.currentRole === 'admin') return !['admin', 'superuser', 'superuser1'].includes(props.user.role)
  if (props.currentRole === 'superuser') return true
  return false
})
```

- [ ] **Step 3: Обновить шаблон**

```html
<button v-if="canCreate || props.isEmpty" ...>Добавить пользователя</button>
<button v-if="canEdit" ...>Редактировать</button>
<button v-if="canPermissions" ...>Изменить права</button>
<button v-if="canFire" ...>Уволить</button>
```

---

## Task 4: Обновить `UsersTable.vue`

**Files:**
- Modify: `maribulka-vue/src/components/users/UsersTable.vue`

- [ ] **Step 1: Добавить `id_profession` в interface User**

- [ ] **Step 2: Передавать `currentRole` вместо `isAdmin`**

```html
<UserActionsModal :current-role="auth.userRole ?? ''" ... />
```

---

## Task 5: Обновить `ViewUserModal.vue`

**Files:**
- Modify: `maribulka-vue/src/components/users/ViewUserModal.vue`

- [ ] **Step 1: Добавить `id_profession` в interface User**

- [ ] **Step 2: Показать профессию в просмотре**

Загрузить список профессий или принять как prop. Проще — передавать `profession_title` из API (JOIN в SELECT).

В `users.php` SELECT добавить:
```sql
LEFT JOIN profession p ON u.id_profession = p.id
```
и поле `p.title AS profession_title`.

Показать в `ViewUserModal`:
```html
<div class="info-row">
  <span class="info-label">Профессия:</span>
  <span class="info-value">{{ user.profession_title || '—' }}</span>
</div>
```

---

## Task 6: Обновить `UserPermissionsModal.vue`

**Files:**
- Modify: `maribulka-vue/src/components/users/UserPermissionsModal.vue`

- [ ] **Step 1: Заблокировать все чекбоксы для admin и superuser**

```typescript
const isReadOnly = computed(() => ['admin', 'superuser'].includes(props.user.role))
```

Добавить в шаблон сообщение и `disabled` на все чекбоксы:
```html
<div v-if="isReadOnly" class="modal-message">Права этого пользователя не изменяемы</div>
<input type="checkbox" :disabled="isReadOnly || ..." />
```

---

## Порядок применения

1. `api/users.php` — SELECT с JOIN, professions endpoint, create/update/fire защита
2. `api/permissions.php` — защита от изменения прав admin/superuser
3. `UserFormModal.vue` — профессия, переименование роли, скрытие полей для admin
4. `UserActionsModal.vue` — currentRole, computed флаги
5. `UsersTable.vue` — interface, currentRole prop
6. `ViewUserModal.vue` — profession_title
7. `UserPermissionsModal.vue` — readonly для admin/superuser
8. Сборка + деплой
