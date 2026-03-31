<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheck, mdiClose, mdiEye, mdiEyeOff } from '@mdi/js'
import SelectBox from '../ui/selectBox/SelectBox.vue'
import DatePicker from '../ui/datePicker/DatePicker.vue'
import AlertModal from '../AlertModal.vue'
import ValidAlertModal from '../ValidAlertModal.vue'
import PadLoader from '../ui/padLoader/PadLoader.vue'

const props = defineProps<{ userId: number | null; forceEdit?: boolean }>()
const emit = defineEmits(['close', 'save'])

const isCreating = computed(() => !props.userId)

const alertMessage = ref('')
const validErrors = ref<string[]>([])
const showValidAlert = ref(false)

// Дата рождения: max = сегодня, min = -65 лет
const today = new Date()
const maxBirthDate = today.toISOString().slice(0, 10)
const minBirthDate = new Date(today.getFullYear() - 65, today.getMonth(), today.getDate()).toISOString().slice(0, 10)

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

const isAdminUser = computed(() => form.value.role === 'admin')
const isSuperUser = computed(() => form.value.role === 'superuser')
const isProfessionFixed = computed(() => isAdminUser.value || isSuperUser.value)

// Список профессий и типов зарплаты
const professionOptions = ref<{ value: string; label: string }[]>([])
const salaryTypeOptions = ref<{ value: string; label: string }[]>([])
const takenRoles = ref<string[]>([])
const isLoading = ref(true)
const loadProgress = ref(0)

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

// Галка «Администратор» — управляется автоматически по роли
const isAdminRoleDisabled = computed(() => isAdminUser.value || ['superuser', 'superuser1', 'auser', 'prouser'].includes(form.value.role))

watch(() => form.value.role, (role) => {
  if (role === 'superuser' || role === 'superuser1' || role === 'auser') {
    form.value.is_admin_role = true
  } else if (role === 'prouser') {
    form.value.is_admin_role = false
  }
})

const roleOptions = computed(() => {
  const all = [
    { value: 'admin', label: 'Admin' },
    { value: 'superuser', label: 'Руководитель' },
    { value: 'superuser1', label: 'Руководитель 2' },
    { value: 'auser', label: 'Администратор' },
    { value: 'prouser', label: 'Работник' },
  ]
  // Для вечных — только их роль
  if (isAdminUser.value || isSuperUser.value) {
    return all.filter(r => r.value === form.value.role)
  }
  // Для остальных — все кроме admin и занятых ролей
  return all.filter(r => r.value !== 'admin' && !takenRoles.value.includes(r.value))
})

function formatPhone(event: Event) {
  const input = event.target as HTMLInputElement
  let value = input.value.replace(/\D/g, '')
  if (value.length > 0 && value[0] !== '7') value = '7' + value
  if (value.length > 11) value = value.substring(0, 11)
  let formatted = '+7'
  if (value.length > 1) formatted += '(' + value.substring(1, 4)
  if (value.length >= 5) formatted += ')' + value.substring(4, 7)
  if (value.length >= 8) formatted += '-' + value.substring(7, 9)
  if (value.length >= 10) formatted += '-' + value.substring(9, 11)
  form.value.phone_user = formatted
}

// ФИО: только кириллица, пробел, дефис
function filterFio(event: Event) {
  const input = event.target as HTMLInputElement
  input.value = input.value.replace(/[^а-яёА-ЯЁ\s-]/g, '')
  form.value.full_name = input.value
}

// Логин: только латиница и цифры
function filterLogin(event: Event) {
  const input = event.target as HTMLInputElement
  input.value = input.value.replace(/[^a-zA-Z0-9]/g, '')
  form.value.login = input.value
}

// Адрес (область/город/улица): кириллица, цифры, пробел
function filterAddress(event: Event, field: 'region' | 'city' | 'street') {
  const input = event.target as HTMLInputElement
  input.value = input.value.replace(/[^а-яёА-ЯЁ0-9\s]/g, '')
  form.value[field] = input.value
}

// Дом: цифры, кириллица, пробел, /, -
function filterHouse(event: Event) {
  const input = event.target as HTMLInputElement
  input.value = input.value.replace(/[^а-яёА-ЯЁ0-9\s\/-]/g, '')
  form.value.house_building = input.value
}

// Квартира: только цифры
function filterDigits(event: Event) {
  const input = event.target as HTMLInputElement
  input.value = input.value.replace(/[^0-9]/g, '')
  form.value.flat = input.value
}

// Email: без кириллицы
function filterEmail(event: Event) {
  const input = event.target as HTMLInputElement
  input.value = input.value.replace(/[а-яёА-ЯЁ\s]/g, '')
  form.value.email_user = input.value
}

const showPassword = ref(false)

function filterPassword(event: Event) {
  const input = event.target as HTMLInputElement
  input.value = input.value.replace(/[а-яёА-ЯЁ]/g, '')
  form.value.password = input.value
}

const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/

async function save() {
  const errors: string[] = []

  if (!form.value.full_name.trim()) errors.push('Укажите ФИО')
  if (!isAdminUser.value && !form.value.login.trim()) errors.push('Укажите логин')
  if (isCreating.value && !form.value.password.trim()) {
    errors.push('Укажите пароль')
  } else if (form.value.password && !passwordRegex.test(form.value.password)) {
    errors.push('Пароль должен содержать минимум 8 символов, заглавную и строчную букву, цифру и спецсимвол')
  }
  if (!form.value.id_profession) errors.push('Укажите профессию')
  if (!isAdminUser.value && !form.value.role) errors.push('Выберите права')
  if (!isAdminUser.value && !form.value.is_admin_role && !form.value.is_photographer && !form.value.is_hairdresser) errors.push('Выберите хотя бы одну специализацию')
  if (!form.value.region.trim()) errors.push('Укажите область')
  if (!form.value.city.trim()) errors.push('Укажите город')
  if (!form.value.street.trim()) errors.push('Укажите улицу')
  if (!form.value.house_building.trim()) errors.push('Укажите дом/строение')
  if (!form.value.phone_user.trim()) errors.push('Укажите телефон')
  if (!form.value.date_of_birth) errors.push('Укажите дату рождения')
  if (!form.value.id_salary_type) errors.push('Выберите тип зарплаты')
  if (!isAdminUser.value && !form.value.hired_at) errors.push('Укажите дату приёма')

  if (errors.length > 0) {
    validErrors.value = errors
    showValidAlert.value = true
    return
  }

  const payload: any = { ...form.value }
  payload.id_profession = form.value.id_profession ? Number(form.value.id_profession) : null
  payload.id_salary_type = form.value.id_salary_type ? Number(form.value.id_salary_type) : null
  if (!isCreating.value) {
    payload.id = props.userId!
    if (!payload.password) delete payload.password
  }
  const action = isCreating.value ? 'create' : 'update'
  const res = await fetch(`/api/users.php?action=${action}`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
  const data = await res.json()
  if (data.success) {
    emit('save')
  } else {
    alertMessage.value = data.message ?? 'Ошибка при сохранении'
  }
}
</script>

<template>
  <PadLoader v-if="isLoading" :progress="loadProgress" />
  <ValidAlertModal :isVisible="showValidAlert" :messages="validErrors" @close="showValidAlert = false" />
  <AlertModal :isVisible="!!alertMessage" :message="alertMessage" @close="alertMessage = ''" />
  <Teleport v-if="!isLoading" to="body">
    <div class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ isCreating ? 'Новый пользователь' : 'Редактировать пользователя' }}</div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">ФИО *</label>
            <input class="modal-input" v-model="form.full_name" type="text" placeholder="Полное имя" @input="filterFio" />
          </div>
          <div class="input-field">
            <label class="input-label">Дата рождения *</label>
            <DatePicker v-model="form.date_of_birth" mode="single" placeholder="Дата рождения"
              :maxDate="maxBirthDate" :minDate="minBirthDate" :showToday="false" />
          </div>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Логин *</label>
            <input class="modal-input" v-model="form.login" type="text" placeholder="Логин" :disabled="isAdminUser" @input="filterLogin" />
          </div>
          <div class="input-field">
            <label class="input-label">Пароль *</label>
            <div class="input-with-icon">
              <span class="input-eye-left" @click="showPassword = !showPassword">
                <svg-icon type="mdi" :path="showPassword ? mdiEye : mdiEyeOff" class="btn-icon" />
              </span>
              <input class="modal-input input-with-icon-left" v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                :placeholder="isCreating ? 'Придумайте пароль' : 'Оставьте пустым, чтобы не менять'"
                @input="filterPassword" />
            </div>
          </div>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Область *</label>
            <input class="modal-input" v-model="form.region" type="text" placeholder="Область" @input="filterAddress($event, 'region')" />
          </div>
          <div class="input-field">
            <label class="input-label">Город *</label>
            <input class="modal-input" v-model="form.city" type="text" placeholder="Город" @input="filterAddress($event, 'city')" />
          </div>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Улица *</label>
            <input class="modal-input" v-model="form.street" type="text" placeholder="Улица" @input="filterAddress($event, 'street')" />
          </div>
          <div class="input-field">
            <label class="input-label">Дом/строение *</label>
            <input class="modal-input" v-model="form.house_building" type="text" placeholder="Дом" @input="filterHouse" />
          </div>
          <div class="input-field-narrow">
            <label class="input-label">Кв.</label>
            <input class="modal-input" v-model="form.flat" type="text" placeholder="№" @input="filterDigits" />
          </div>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Телефон *</label>
            <input class="modal-input" v-model="form.phone_user" type="tel"
              placeholder="+7(999)999-99-99" maxlength="16" @input="formatPhone" />
          </div>
          <div class="input-field">
            <label class="input-label">Email</label>
            <input class="modal-input" v-model="form.email_user" type="email" placeholder="email@example.com" @input="filterEmail" />
          </div>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Профессия *</label>
            <SelectBox v-model="form.id_profession" :options="professionOptions"
              placeholder="Выберите профессию" :disabled="isProfessionFixed" />
          </div>
          <div class="input-field">
            <label class="input-label">Права *</label>
            <SelectBox v-model="form.role" :options="roleOptions" placeholder="Выберите права" :disabled="isAdminUser || isSuperUser" />
          </div>
          <div v-if="!isAdminUser" class="input-field">
            <label class="input-label">Дата приёма *</label>
            <DatePicker v-model="form.hired_at" mode="single" placeholder="Дата приёма" :showToday="false" />
          </div>
        </div>

        <div v-if="!isAdminUser" class="input-group">
          <label class="input-label">Специализации *</label>
          <div>
            <label><input type="checkbox" v-model="form.is_admin_role" :disabled="isAdminRoleDisabled" /> Администратор</label>
            <label><input type="checkbox" v-model="form.is_photographer" /> Фотограф</label>
            <label><input type="checkbox" v-model="form.is_hairdresser" /> Парикмахер</label>
          </div>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Тип зарплаты *</label>
            <SelectBox v-model="form.id_salary_type" :options="salaryTypeOptions" placeholder="Тип зарплаты" :disabled="isAdminUser" />
          </div>
        </div>

        <div class="input-group">
          <label class="input-label">Примечания</label>
          <textarea class="modal-input" v-model="form.notes" rows="3" placeholder="Примечания"></textarea>
        </div>

        <div :class="forceEdit ? 'ButtonFooter PosCenter' : 'ButtonFooter PosSpace'">
          <button v-if="!forceEdit" class="btnGlass iconText" @click="$emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiClose" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="save">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheck" class="btn-icon" />
            <span>Сохранить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
