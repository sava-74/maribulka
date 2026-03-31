# User Form: Salary Type + Load from DB Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Заменить строковое поле salary_type на связь с таблицей salary_type через id_salary_type, и рефакторинг UserFormModal для загрузки данных пользователя из БД по userId вместо props.

**Architecture:** UserFormModal принимает userId вместо объекта User. В onMounted загружает: список профессий, список типов зарплаты, данные пользователя — всё из БД. UsersTable и App.vue передают только id. api/users.php получает новый endpoint salary_types и обновлённые create/update.

**Tech Stack:** Vue 3 + TypeScript, PHP 8.4, MySQL, Pinia

---

## Файлы

| Файл | Действие |
|------|----------|
| `api/users.php` | Modify: добавить endpoint salary_types, убрать salary_type строку, добавить id_salary_type |
| `maribulka-vue/src/components/users/UserFormModal.vue` | Modify: prop userId вместо user, загрузка из БД, дропдаун salary_type |
| `maribulka-vue/src/components/users/UsersTable.vue` | Modify: передавать userId вместо user объекта |
| `maribulka-vue/src/App.vue` | Modify: передавать userId из authStore вместо currentUserForForm |

---

### Task 1: api/users.php — endpoint salary_types + обновить create/update

**Files:**
- Modify: `api/users.php`

- [ ] **Шаг 1: Добавить endpoint `action=salary_types`**

После блока `action=professions` добавить:

```php
// GET ?action=salary_types — список типов зарплаты
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'salary_types') {
    $stmt = $pdo->query("SELECT id, title FROM salary_type ORDER BY id");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
```

- [ ] **Шаг 2: Обновить SELECT в `action=list` и `action=get`**

В обоих SELECT заменить `u.salary_type` на `u.id_salary_type` и добавить JOIN:

```sql
SELECT u.id, u.full_name, u.login, u.role, u.id_profession,
       u.is_photographer, u.is_hairdresser, u.is_admin_role,
       u.id_salary_type, u.hired_at, u.fired_at, u.notes, u.created_at,
       u.region, u.city, u.street, u.house_building, u.flat,
       u.phone_user, u.email_user, u.date_of_birth,
       p.title AS profession_title,
       st.title AS salary_type_title
FROM users u
LEFT JOIN profession p ON u.id_profession = p.id
LEFT JOIN salary_type st ON u.id_salary_type = st.id
```

- [ ] **Шаг 3: Обновить `action=create` — заменить salary_type на id_salary_type**

В INSERT убрать `salary_type`, добавить `id_salary_type`:

```php
$stmt = $pdo->prepare(
    "INSERT INTO users (full_name, login, password, role, id_profession,
        is_photographer, is_hairdresser, is_admin_role,
        id_salary_type, hired_at, notes, created_at,
        region, city, street, house_building, flat,
        phone_user, email_user, date_of_birth)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([
    $input['full_name'] ?? null,
    $input['login'],
    password_hash($input['password'], PASSWORD_BCRYPT),
    $input['role'] ?? 'prouser',
    !empty($input['id_profession']) ? (int)$input['id_profession'] : null,
    (int)($input['is_photographer'] ?? 0),
    (int)($input['is_hairdresser'] ?? 0),
    (int)($input['is_admin_role'] ?? 0),
    !empty($input['id_salary_type']) ? (int)$input['id_salary_type'] : null,
    $input['hired_at'] ?? null,
    $input['notes'] ?? null,
    $input['region'] ?? null,
    $input['city'] ?? null,
    $input['street'] ?? null,
    $input['house_building'] ?? null,
    !empty($input['flat']) ? (int)$input['flat'] : null,
    $input['phone_user'] ?? null,
    $input['email_user'] ?? null,
    $input['date_of_birth'] ?? null,
]);
```

- [ ] **Шаг 4: Обновить `action=update` — заменить salary_type на id_salary_type**

В $fields и $params заменить `salary_type=?` на `id_salary_type=?` и соответствующее значение:

```php
$fields = "full_name=?, login=?, role=?, id_profession=?,
           is_photographer=?, is_hairdresser=?, is_admin_role=?,
           id_salary_type=?, hired_at=?, notes=?,
           region=?, city=?, street=?, house_building=?, flat=?,
           phone_user=?, email_user=?, date_of_birth=?";
$params = [
    $input['full_name'] ?? null,
    $input['login'] ?? null,
    $input['role'] ?? 'prouser',
    !empty($input['id_profession']) ? (int)$input['id_profession'] : null,
    (int)($input['is_photographer'] ?? 0),
    (int)($input['is_hairdresser'] ?? 0),
    (int)($input['is_admin_role'] ?? 0),
    !empty($input['id_salary_type']) ? (int)$input['id_salary_type'] : null,
    $input['hired_at'] ?? null,
    $input['notes'] ?? null,
    $input['region'] ?? null,
    $input['city'] ?? null,
    $input['street'] ?? null,
    $input['house_building'] ?? null,
    !empty($input['flat']) ? (int)$input['flat'] : null,
    $input['phone_user'] ?? null,
    $input['email_user'] ?? null,
    $input['date_of_birth'] ?? null,
];
```

---

### Task 2: UserFormModal.vue — рефакторинг на userId + дропдаун salary_type

**Files:**
- Modify: `maribulka-vue/src/components/users/UserFormModal.vue`

- [ ] **Шаг 1: Обновить interface User**

Заменить `salary_type: string | null` на `id_salary_type: number | null`, добавить `salary_type_title: string | null`:

```typescript
interface User {
  id: number
  full_name: string | null
  login: string
  role: string
  id_profession: number | null
  is_photographer: boolean
  is_hairdresser: boolean
  is_admin_role: boolean
  id_salary_type: number | null
  hired_at: string | null
  notes: string | null
  region: string | null
  city: string | null
  street: string | null
  house_building: string | null
  flat: number | null
  phone_user: string | null
  email_user: string | null
  date_of_birth: string | null
}
```

- [ ] **Шаг 2: Изменить props — userId вместо user**

```typescript
const props = defineProps<{ userId: number | null; forceEdit?: boolean }>()
const emit = defineEmits(['close', 'save'])

const isCreating = computed(() => !props.userId)
```

- [ ] **Шаг 3: Обновить form ref — заменить salary_type на id_salary_type**

```typescript
const form = ref({
  full_name: '',
  login: '',
  password: '',
  role: 'prouser',
  id_profession: null as string | null,
  is_photographer: false,
  is_hairdresser: false,
  is_admin_role: false,
  id_salary_type: null as string | null,
  hired_at: maxBirthDate,
  notes: '',
  region: '',
  city: '',
  street: '',
  house_building: '',
  flat: '',
  phone_user: '',
  email_user: '',
  date_of_birth: '',
})
```

- [ ] **Шаг 4: Добавить salaryTypeOptions ref**

```typescript
const salaryTypeOptions = ref<{ value: string; label: string }[]>([])
```

- [ ] **Шаг 5: Обновить onMounted — убрать watch, загружать пользователя из БД**

```typescript
onMounted(async () => {
  // Шаг 1: профессии → 25%
  const res1 = await fetch('/api/users.php?action=professions', { credentials: 'include' })
  const data1 = await res1.json()
  if (data1.success) {
    professionOptions.value = data1.data.map((p: any) => ({ value: String(p.id), label: p.title }))
  }
  loadProgress.value = 25

  // Шаг 2: типы зарплаты → 50%
  const res2 = await fetch('/api/users.php?action=salary_types', { credentials: 'include' })
  const data2 = await res2.json()
  if (data2.success) {
    salaryTypeOptions.value = data2.data.map((s: any) => ({ value: String(s.id), label: s.title }))
  }
  loadProgress.value = 50

  // Шаг 3: занятые роли → 75%
  const res3 = await fetch('/api/users.php?action=list', { credentials: 'include' })
  const data3 = await res3.json()
  if (data3.success) {
    const active = data3.data.filter((u: any) => !u.fired_at)
    const others = props.userId ? active.filter((u: any) => u.id !== props.userId) : active
    takenRoles.value = others.map((u: any) => u.role)
  }
  loadProgress.value = 75

  // Шаг 4: данные пользователя из БД → 100%
  if (props.userId) {
    const res4 = await fetch(`/api/users.php?action=get&id=${props.userId}`, { credentials: 'include' })
    const data4 = await res4.json()
    if (data4.success) {
      const user = data4.data
      form.value = {
        full_name: user.full_name ?? '',
        login: user.login ?? '',
        password: '',
        role: user.role,
        id_profession: user.id_profession !== null ? String(user.id_profession) : null,
        is_photographer: !!user.is_photographer,
        is_hairdresser: !!user.is_hairdresser,
        is_admin_role: !!user.is_admin_role,
        id_salary_type: user.id_salary_type !== null ? String(user.id_salary_type) : null,
        hired_at: user.hired_at ?? '',
        notes: user.notes ?? '',
        region: user.region ?? '',
        city: user.city ?? '',
        street: user.street ?? '',
        house_building: user.house_building ?? '',
        flat: user.flat !== null ? String(user.flat) : '',
        phone_user: user.phone_user ?? '',
        email_user: user.email_user ?? '',
        date_of_birth: user.date_of_birth ?? '',
      }
    }
  }
  loadProgress.value = 100

  await new Promise(resolve => setTimeout(resolve, 250))
  isLoading.value = false
})
```

- [ ] **Шаг 6: Убрать watch на props.user**

Удалить весь блок `watch(() => props.user, ...)`.

- [ ] **Шаг 7: Обновить isAdminUser и isSuperUser**

Вместо `props.user?.role` использовать `form.value.role`:

```typescript
const isAdminUser = computed(() => form.value.role === 'admin')
const isSuperUser = computed(() => form.value.role === 'superuser')
```

- [ ] **Шаг 8: Обновить функцию save()**

Заменить `salary_type` на `id_salary_type`:

```typescript
const payload: any = { ...form.value }
payload.id_profession = form.value.id_profession ? Number(form.value.id_profession) : null
payload.id_salary_type = form.value.id_salary_type ? Number(form.value.id_salary_type) : null
if (!isCreating.value) {
  payload.id = props.userId!
  if (!payload.password) delete payload.password
}
```

- [ ] **Шаг 9: Обновить template — дропдаун salary_type**

Заменить старый SelectBox с salaryOptions на:

```html
<div class="input-field">
  <label class="input-label">Тип зарплаты *</label>
  <SelectBox v-model="form.id_salary_type" :options="salaryTypeOptions" placeholder="Тип зарплаты" :disabled="isAdminUser" />
</div>
```

---

### Task 3: UsersTable.vue — передавать userId

**Files:**
- Modify: `maribulka-vue/src/components/users/UsersTable.vue`

- [ ] **Шаг 1: Обновить UserFormModal в template**

Заменить `:user="isCreating ? null : selectedUser"` на `:user-id="isCreating ? null : selectedUser?.id ?? null"`:

```html
<UserFormModal
  v-if="showForm"
  :user-id="isCreating ? null : selectedUser?.id ?? null"
  @close="showForm = false"
  @save="onFormSave"
/>
```

---

### Task 4: App.vue — передавать userId для mustChangePassword

**Files:**
- Modify: `maribulka-vue/src/App.vue`

- [ ] **Шаг 1: Убрать currentUserForForm computed**

Удалить весь блок `const currentUserForForm = computed(...)`.

- [ ] **Шаг 2: Обновить UserFormModal в template**

```html
<UserFormModal
  v-if="authStore.mustChangePassword && authStore.userId"
  :user-id="authStore.userId"
  :force-edit="true"
  @save="onPasswordChanged"
/>
```

---

### Task 5: Деплой и проверка

- [ ] **Шаг 1: Собрать фронтенд**

```bash
cd maribulka-vue && npm run build
```

- [ ] **Шаг 2: Деплой**

```bash
.\deploy.ps1
```

- [ ] **Шаг 3: Проверить**

- Открыть форму редактирования любого пользователя — профессия и тип зарплаты подтягиваются из БД
- Создать нового пользователя — дропдауны заполнены из БД
- Войти как admin с паролем 123 — открывается форма смены пароля с заполненными данными
