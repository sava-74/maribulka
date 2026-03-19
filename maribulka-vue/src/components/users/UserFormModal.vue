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

async function save() {
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
          <label class="input-label">ФИО</label>
          <input class="modal-input" v-model="form.full_name" type="text" placeholder="Полное имя" />
        </div>
        <div class="input-group">
          <label class="input-label">Логин</label>
          <input class="modal-input" v-model="form.login" type="text" placeholder="Логин" required :disabled="isAdminUser" />
        </div>
        <div class="input-group">
          <label class="input-label">Пароль</label>
          <input class="modal-input" v-model="form.password" type="password"
            :placeholder="isCreating ? 'Придумайте пароль' : 'Оставьте пустым, чтобы не менять'" :required="isCreating" />
        </div>
        <div class="input-group">
          <label class="input-label">Роль</label>
          <SelectBox v-model="form.role" :options="roleOptions" placeholder="Выберите роль" :disabled="isAdminUser" />
        </div>
        <div class="input-group">
          <label class="input-label">Специализации</label>
          <div>
            <label><input type="checkbox" v-model="form.is_admin_role" :disabled="isAdminUser" /> Администратор</label>
            <label><input type="checkbox" v-model="form.is_photographer" :disabled="isAdminUser" /> Фотограф</label>
            <label><input type="checkbox" v-model="form.is_hairdresser" :disabled="isAdminUser" /> Парикмахер</label>
          </div>
        </div>
        <div class="input-group">
          <label class="input-label">Тип зарплаты</label>
          <SelectBox v-model="form.salary_type" :options="salaryOptions" placeholder="Тип зарплаты" :disabled="isAdminUser" />
        </div>
        <div v-if="!isAdminUser" class="input-group">
          <label class="input-label">Дата приёма</label>
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
