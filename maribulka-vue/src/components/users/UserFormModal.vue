<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheck, mdiClose } from '@mdi/js'
import SelectBox from '../ui/selectBox/SelectBox.vue'
import DatePicker from '../ui/datePicker/DatePicker.vue'
import AlertModal from '../AlertModal.vue'

interface User {
  id: number
  full_name: string | null
  login: string
  role: string
  is_photographer: boolean
  is_hairdresser: boolean
  is_admin_role: boolean
  salary_type: string | null
  hired_at: string | null
  notes: string | null
  region: string | null
  city: string | null
  house_building: string | null
  flat: number | null
  phone_user: string | null
  email_user: string | null
  date_of_birth: string | null
}

const props = defineProps<{ user: User | null }>()
const emit = defineEmits(['close', 'save'])

const isCreating = computed(() => !props.user)
const isAdminUser = computed(() => props.user?.role === 'admin')
const alertMessage = ref('')

const form = ref({
  full_name: '',
  login: '',
  password: '',
  role: 'prouser',
  is_photographer: false,
  is_hairdresser: false,
  is_admin_role: false,
  salary_type: '',
  hired_at: '',
  notes: '',
  region: '',
  city: '',
  house_building: '',
  flat: '',
  phone_user: '',
  email_user: '',
  date_of_birth: '',
})

watch(() => props.user, (user) => {
  if (user) {
    form.value = {
      full_name: user.full_name ?? '',
      login: user.login,
      password: '',
      role: user.role,
      is_photographer: user.is_photographer,
      is_hairdresser: user.is_hairdresser,
      is_admin_role: user.is_admin_role,
      salary_type: user.salary_type ?? '',
      hired_at: user.hired_at ?? '',
      notes: user.notes ?? '',
      region: user.region ?? '',
      city: user.city ?? '',
      house_building: user.house_building ?? '',
      flat: user.flat !== null ? String(user.flat) : '',
      phone_user: user.phone_user ?? '',
      email_user: user.email_user ?? '',
      date_of_birth: user.date_of_birth ?? '',
    }
  }
}, { immediate: true })

const roleOptions = [
  { value: 'admin', label: 'Admin' },
  { value: 'superuser', label: 'SuperUser' },
  { value: 'auser', label: 'AUser' },
  { value: 'prouser', label: 'ProUser' },
]

const salaryOptions = [
  { value: 'fixed', label: 'Оклад' },
  { value: 'percent', label: 'Процент' },
  { value: 'fixed_percent', label: 'Оклад + %' },
]

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

async function save() {
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
  if (!form.value.is_admin_role && !form.value.is_photographer && !form.value.is_hairdresser) { alertMessage.value = 'Выберите хотя бы одну специализацию'; return }
  if (!form.value.salary_type) { alertMessage.value = 'Выберите тип зарплаты'; return }
  if (!isAdminUser.value && !form.value.hired_at) { alertMessage.value = 'Укажите дату приёма'; return }

  const payload: any = { ...form.value }
  if (!isCreating.value) {
    payload.id = props.user!.id
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
  <AlertModal :isVisible="!!alertMessage" :message="alertMessage" @close="alertMessage = ''" />
  <Teleport to="body">
    <div class="modal-overlay-main" @click.self="$emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ isCreating ? 'Новый пользователь' : 'Редактировать пользователя' }}</div>

        <div class="input-group">
          <label class="input-label">ФИО *</label>
          <input class="modal-input" v-model="form.full_name" type="text" placeholder="Полное имя" />
        </div>

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

        <div class="input-group">
          <label class="input-label">Дата рождения *</label>
          <DatePicker v-model="form.date_of_birth" mode="single" placeholder="Дата рождения" />
        </div>

        <div class="input-group">
          <label class="input-label">Роль *</label>
          <SelectBox v-model="form.role" :options="roleOptions" placeholder="Выберите роль" :disabled="isAdminUser" />
        </div>

        <div class="input-group">
          <label class="input-label">Специализации *</label>
          <div>
            <label><input type="checkbox" v-model="form.is_admin_role" :disabled="isAdminUser" /> Администратор</label>
            <label><input type="checkbox" v-model="form.is_photographer" :disabled="isAdminUser" /> Фотограф</label>
            <label><input type="checkbox" v-model="form.is_hairdresser" :disabled="isAdminUser" /> Парикмахер</label>
          </div>
        </div>

        <div class="input-group">
          <label class="input-label">Тип зарплаты *</label>
          <SelectBox v-model="form.salary_type" :options="salaryOptions" placeholder="Тип зарплаты" :disabled="isAdminUser" />
        </div>

        <div v-if="!isAdminUser" class="input-group">
          <label class="input-label">Дата приёма *</label>
          <DatePicker v-model="form.hired_at" mode="single" placeholder="Дата приёма" />
        </div>

        <div class="input-group">
          <label class="input-label">Примечания</label>
          <textarea class="modal-input" v-model="form.notes" rows="3" placeholder="Примечания"></textarea>
        </div>

        <div class="ButtonFooter PosSpace">
          <button class="btnGlass iconText" @click="$emit('close')">
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
