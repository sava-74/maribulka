<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiClose } from '@mdi/js'

interface User {
  id: number
  full_name: string | null
  login: string
  role: string
  id_profession: number | null
  profession_title: string | null
  is_photographer: boolean
  is_hairdresser: boolean
  is_admin_role: boolean
  id_salary_type: number | null
  salary_type_title: string | null
  hired_at: string | null
  fired_at: string | null
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

const props = defineProps<{ user: User }>()
const emit = defineEmits(['close'])

const ROLE_LABELS: Record<string, string> = {
  admin: 'Admin',
  superuser: 'Руководитель',
  auser: 'Администратор',
  prouser: 'Работник',
  user: 'Пользователь',
}


function formatDate(dateStr: string | null) {
  if (!dateStr) return '—'
  const [year, month, day] = dateStr.split('-')
  return `${day}.${month}.${year}`
}
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ user.full_name ?? user.login }}</div>

        <div>
          <div class="info-row">
            <span class="info-label">Логин:</span>
            <span class="info-value">{{ user.login }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Профессия:</span>
            <span class="info-value">{{ user.profession_title || '—' }}</span>
          </div>
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
            <span class="info-label">Улица:</span>
            <span class="info-value">{{ user.street || '—' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Дом/строение:</span>
            <span class="info-value">{{ user.house_building || '—' }}</span>
          </div>
          <div class="info-row" v-if="user.flat">
            <span class="info-label">Квартира:</span>
            <span class="info-value">{{ user.flat }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Роль:</span>
            <span class="info-value">{{ ROLE_LABELS[user.role] ?? user.role }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Администратор:</span>
            <span class="info-value">{{ user.is_admin_role ? 'Да' : 'Нет' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Фотограф:</span>
            <span class="info-value">{{ user.is_photographer ? 'Да' : 'Нет' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Парикмахер:</span>
            <span class="info-value">{{ user.is_hairdresser ? 'Да' : 'Нет' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Тип зарплаты:</span>
            <span class="info-value">{{ user.salary_type_title || '—' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Дата приёма:</span>
            <span class="info-value">{{ formatDate(user.hired_at) }}</span>
          </div>
          <div v-if="user.fired_at" class="info-row">
            <span class="info-label">Дата увольнения:</span>
            <span class="info-value">{{ formatDate(user.fired_at) }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Примечания:</span>
            <span class="info-value">{{ user.notes || '—' }}</span>
          </div>
        </div>

        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiClose" class="btn-icon" />
            <span>Закрыть</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
