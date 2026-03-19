# UserFormModal — Контактные и адресные поля

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Добавить в форму пользователя контактные и адресные поля: регион, город, дом/строение, квартира, телефон (с маской), email, дата рождения. Добавить клиентскую валидацию всех обязательных полей.

**Design:**
```
┌─────────────────────────────────────────┐
│ Новый пользователь                      │
├─────────────────────────────────────────┤
│ ФИО *                                   │
│ [                                     ] │
├─────────────────────────────────────────┤
│ Логин *          │ Пароль *             │
│ [              ] │ [                  ] │
├─────────────────────────────────────────┤
│ Область *        │ Город *              │
│ [              ] │ [                  ] │
├─────────────────────────────────────────┤
│ Дом/строение *   │ Кв.                  │
│ [            ]   │ [    ]               │
├─────────────────────────────────────────┤
│ Телефон *        │ Email *              │
│ [              ] │ [                  ] │
├─────────────────────────────────────────┤
│ Дата рождения *                         │
│ [                                     ] │
├─────────────────────────────────────────┤
│ Роль *                                  │
│ [SelectBox                            ] │
├─────────────────────────────────────────┤
│ Специализации *                         │
│ □ Администратор □ Фотограф □ Парикмахер │
├─────────────────────────────────────────┤
│ Тип зарплаты *                          │
│ [SelectBox                            ] │
├─────────────────────────────────────────┤
│ Дата приёма *  (скрыта для admin)       │
│ [                                     ] │
├─────────────────────────────────────────┤
│ Примечания                              │
│ [                                     ] │
├─────────────────────────────────────────┤
│ [Отмена]                   [Сохранить] │
└─────────────────────────────────────────┘
```

**CSS классы для пар полей (существующие в modal.css):**
- `.input-row` — flex строка для пары полей
- `.input-field` — flex: 1 колонка label+input
- `.input-field-narrow` — узкое поле (для квартиры)

**Новый CSS:** не нужен — все классы уже существуют.

**БД:** поля уже добавлены вручную на сервере:
- `region` TEXT NOT NULL
- `city` TEXT NOT NULL
- `house_building` TEXT NOT NULL
- `flat` INT NULL
- `phone_user` VARCHAR(20) NOT NULL
- `email_user` VARCHAR(100) NULL
- `date_of_birth` DATE NOT NULL

---

## Task 1: Обновить `api/users.php`

**Files:**
- Modify: `api/users.php`

- [ ] **Step 1: Добавить новые поля в SELECT (`action=list`)**

Заменить:
```sql
SELECT id, full_name, login, role,
       is_photographer, is_hairdresser, is_admin_role,
       salary_type, hired_at, fired_at, notes, created_at
FROM users
```
На:
```sql
SELECT id, full_name, login, role,
       is_photographer, is_hairdresser, is_admin_role,
       salary_type, hired_at, fired_at, notes, created_at,
       region, city, house_building, flat,
       phone_user, email_user, date_of_birth
FROM users
```

- [ ] **Step 2: Добавить новые поля в INSERT (`action=create`)**

Заменить INSERT:
```sql
INSERT INTO users (full_name, login, password, role,
    is_photographer, is_hairdresser, is_admin_role,
    salary_type, hired_at, notes, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
```
На:
```sql
INSERT INTO users (full_name, login, password, role,
    is_photographer, is_hairdresser, is_admin_role,
    salary_type, hired_at, notes,
    region, city, house_building, flat,
    phone_user, email_user, date_of_birth, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
```

Добавить в массив параметров `execute([...])` перед закрывающей скобкой:
```php
$input['region'] ?? null,
$input['city'] ?? null,
$input['house_building'] ?? null,
!empty($input['flat']) ? (int)$input['flat'] : null,
$input['phone_user'] ?? null,
$input['email_user'] ?? null,
$input['date_of_birth'] ?? null,
```

- [ ] **Step 3: Добавить новые поля в UPDATE (`action=update`)**

В строку `$fields` добавить в конец (перед `notes=?`):
```php
$fields = "full_name=?, login=?, role=?,
           is_photographer=?, is_hairdresser=?, is_admin_role=?,
           salary_type=?, hired_at=?, notes=?,
           region=?, city=?, house_building=?, flat=?,
           phone_user=?, email_user=?, date_of_birth=?";
```

В массив `$params` добавить после `$input['notes'] ?? null`:
```php
$input['region'] ?? null,
$input['city'] ?? null,
$input['house_building'] ?? null,
!empty($input['flat']) ? (int)$input['flat'] : null,
$input['phone_user'] ?? null,
$input['email_user'] ?? null,
$input['date_of_birth'] ?? null,
```

---

## Task 2: Обновить Interface User в трёх компонентах

**Files:**
- Modify: `maribulka-vue/src/components/users/UserFormModal.vue`
- Modify: `maribulka-vue/src/components/users/UsersTable.vue`
- Modify: `maribulka-vue/src/components/users/ViewUserModal.vue`

- [ ] **Step 1: Добавить поля в interface User во всех трёх файлах**

```typescript
region: string | null
city: string | null
house_building: string | null
flat: number | null
phone_user: string | null
email_user: string | null
date_of_birth: string | null
```

---

## Task 3: Обновить `UserFormModal.vue`

**Files:**
- Modify: `maribulka-vue/src/components/users/UserFormModal.vue`

- [ ] **Step 1: Добавить новые поля в `form` ref**

```typescript
region: '',
city: '',
house_building: '',
flat: '',
phone_user: '',
email_user: '',
date_of_birth: '',
```

- [ ] **Step 2: Добавить маппинг новых полей в `watch`**

```typescript
region: user.region ?? '',
city: user.city ?? '',
house_building: user.house_building ?? '',
flat: user.flat !== null ? String(user.flat) : '',
phone_user: user.phone_user ?? '',
email_user: user.email_user ?? '',
date_of_birth: user.date_of_birth ?? '',
```

- [ ] **Step 3: Добавить функцию `formatPhone` (маска телефона)**

```typescript
function formatPhone(event: Event) {
  const input = event.target as HTMLInputElement
  let value = input.value.replace(/\D/g, '')
  if (value.length > 0 && value[0] !== '7') {
    value = '7' + value
  }
  if (value.length > 11) {
    value = value.substring(0, 11)
  }
  let formatted = '+7'
  if (value.length > 1) formatted += '(' + value.substring(1, 4)
  if (value.length >= 5) formatted += ')' + value.substring(4, 7)
  if (value.length >= 8) formatted += '-' + value.substring(7, 9)
  if (value.length >= 10) formatted += '-' + value.substring(9, 11)
  form.value.phone_user = formatted
}
```

- [ ] **Step 4: Добавить валидацию в функцию `save()` перед fetch**

```typescript
if (!form.value.full_name.trim()) { alertMessage.value = 'Укажите ФИО'; return }
if (!isAdminUser.value && !form.value.login.trim()) { alertMessage.value = 'Укажите логин'; return }
if (isCreating.value && !form.value.password.trim()) { alertMessage.value = 'Укажите пароль'; return }
if (!form.value.region.trim()) { alertMessage.value = 'Укажите область'; return }
if (!form.value.city.trim()) { alertMessage.value = 'Укажите город'; return }
if (!form.value.house_building.trim()) { alertMessage.value = 'Укажите дом/строение'; return }
if (!form.value.phone_user.trim()) { alertMessage.value = 'Укажите телефон'; return }
if (!form.value.email_user.trim()) { alertMessage.value = 'Укажите email'; return }
if (!form.value.date_of_birth) { alertMessage.value = 'Укажите дату рождения'; return }
if (!form.value.role) { alertMessage.value = 'Выберите роль'; return }
if (!form.value.is_admin_role && !form.value.is_photographer && !form.value.is_hairdresser) {
  alertMessage.value = 'Выберите хотя бы одну специализацию'; return
}
if (!form.value.salary_type) { alertMessage.value = 'Выберите тип зарплаты'; return }
if (!isAdminUser.value && !form.value.hired_at) { alertMessage.value = 'Укажите дату приёма'; return }
```

- [ ] **Step 5: Обновить шаблон формы**

Новый порядок полей в `<div class="padGlass modal-sm">`:

```html
<!-- ФИО -->
<div class="input-group">
  <label class="input-label">ФИО *</label>
  <input class="modal-input" v-model="form.full_name" type="text" placeholder="Полное имя" />
</div>

<!-- Логин + Пароль -->
<div class="input-row">
  <div class="input-field">
    <label class="input-label">Логин *</label>
    <input class="modal-input" v-model="form.login" type="text" placeholder="Логин" :disabled="isAdminUser" />
  </div>
  <div class="input-field">
    <label class="input-label">Пароль *</label>
    <input class="modal-input" v-model="form.password" type="password"
      :placeholder="isCreating ? 'Придумайте пароль' : 'Оставьте пустым, чтобы не менять'" />
  </div>
</div>

<!-- Область + Город -->
<div class="input-row">
  <div class="input-field">
    <label class="input-label">Область *</label>
    <input class="modal-input" v-model="form.region" type="text" placeholder="Область" />
  </div>
  <div class="input-field">
    <label class="input-label">Город *</label>
    <input class="modal-input" v-model="form.city" type="text" placeholder="Город" />
  </div>
</div>

<!-- Дом/строение + Квартира -->
<div class="input-row">
  <div class="input-field">
    <label class="input-label">Дом/строение *</label>
    <input class="modal-input" v-model="form.house_building" type="text" placeholder="Дом, строение" />
  </div>
  <div class="input-field-narrow">
    <label class="input-label">Кв.</label>
    <input class="modal-input" v-model="form.flat" type="number" placeholder="№" />
  </div>
</div>

<!-- Телефон + Email -->
<div class="input-row">
  <div class="input-field">
    <label class="input-label">Телефон *</label>
    <input class="modal-input" v-model="form.phone_user" type="tel"
      placeholder="+7(999)999-99-99" maxlength="16" @input="formatPhone" />
  </div>
  <div class="input-field">
    <label class="input-label">Email *</label>
    <input class="modal-input" v-model="form.email_user" type="email" placeholder="email@example.com" />
  </div>
</div>

<!-- Дата рождения -->
<div class="input-group">
  <label class="input-label">Дата рождения *</label>
  <DatePicker v-model="form.date_of_birth" mode="single" placeholder="Дата рождения" />
</div>

<!-- Роль -->
<div class="input-group">
  <label class="input-label">Роль *</label>
  <SelectBox v-model="form.role" :options="roleOptions" placeholder="Выберите роль" :disabled="isAdminUser" />
</div>

<!-- Специализации -->
<div class="input-group">
  <label class="input-label">Специализации *</label>
  <div>
    <label><input type="checkbox" v-model="form.is_admin_role" :disabled="isAdminUser" /> Администратор</label>
    <label><input type="checkbox" v-model="form.is_photographer" :disabled="isAdminUser" /> Фотограф</label>
    <label><input type="checkbox" v-model="form.is_hairdresser" :disabled="isAdminUser" /> Парикмахер</label>
  </div>
</div>

<!-- Тип зарплаты -->
<div class="input-group">
  <label class="input-label">Тип зарплаты *</label>
  <SelectBox v-model="form.salary_type" :options="salaryOptions" placeholder="Тип зарплаты" :disabled="isAdminUser" />
</div>

<!-- Дата приёма (скрыта для admin) -->
<div v-if="!isAdminUser" class="input-group">
  <label class="input-label">Дата приёма *</label>
  <DatePicker v-model="form.hired_at" mode="single" placeholder="Дата приёма" />
</div>

<!-- Примечания -->
<div class="input-group">
  <label class="input-label">Примечания</label>
  <textarea class="modal-input" v-model="form.notes" rows="3" placeholder="Примечания"></textarea>
</div>
```

---

## Task 4: Обновить `ViewUserModal.vue`

**Files:**
- Modify: `maribulka-vue/src/components/users/ViewUserModal.vue`

- [ ] **Step 1: Добавить новые info-row блоки**

После строки "Логин" добавить блоки контактов и адреса:

```html
<div class="info-row">
  <span class="info-label">Телефон:</span>
  <span class="info-value">
    <a v-if="user.phone_user" :href="`tel:${user.phone_user}`">{{ user.phone_user }}</a>
    <template v-else>—</template>
  </span>
</div>
<div class="info-row">
  <span class="info-label">Email:</span>
  <span class="info-value">
    <a v-if="user.email_user" :href="`mailto:${user.email_user}`">{{ user.email_user }}</a>
    <template v-else>—</template>
  </span>
</div>
<div class="info-row">
  <span class="info-label">Дата рождения:</span>
  <span class="info-value">{{ user.date_of_birth ?? '—' }}</span>
</div>
<div class="info-row">
  <span class="info-label">Область:</span>
  <span class="info-value">{{ user.region || '—' }}</span>
</div>
<div class="info-row">
  <span class="info-label">Город:</span>
  <span class="info-value">{{ user.city || '—' }}</span>
</div>
<div class="info-row">
  <span class="info-label">Дом/строение:</span>
  <span class="info-value">{{ user.house_building || '—' }}</span>
</div>
<div class="info-row" v-if="user.flat">
  <span class="info-label">Квартира:</span>
  <span class="info-value">{{ user.flat }}</span>
</div>
```
