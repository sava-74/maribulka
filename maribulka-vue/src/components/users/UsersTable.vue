<script setup lang="ts">
import { ref, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiPlus } from '@mdi/js'
import { useAuthStore } from '../../stores/auth'
import UserActionsModal from './UserActionsModal.vue'
import UserFormModal from './UserFormModal.vue'
import FireUserModal from './FireUserModal.vue'
import UserPermissionsModal from './UserPermissionsModal.vue'

const auth = useAuthStore()

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

const users = ref<User[]>([])
const selectedUser = ref<User | null>(null)
const showActions = ref(false)
const showForm = ref(false)
const showFire = ref(false)
const showPermissions = ref(false)
const isCreating = ref(false)

const ROLE_LABELS: Record<string, string> = {
  admin: 'Admin',
  superuser: 'SuperUser',
  auser: 'AUser',
  prouser: 'ProUser',
  user: 'User',
}

const SALARY_LABELS: Record<string, string> = {
  fixed: 'Оклад',
  percent: 'Процент',
  fixed_percent: 'Оклад + %',
}

async function loadUsers() {
  const res = await fetch('/api/users.php?action=list', { credentials: 'include' })
  const data = await res.json()
  if (data.success) users.value = data.data
}

onMounted(loadUsers)

function onRowClick(user: User) {
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
  if (!selectedUser.value) return
  await fetch('/api/users.php?action=fire', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: selectedUser.value.id }),
  })
  showFire.value = false
  await loadUsers()
}
</script>

<template>
  <div class="padGlass padGlass-work">
    <div class="table-toolbar">
      <span class="table-title">Пользователи</span>
      <button class="btnGlass iconText" @click="onAdd">
        <span class="inner-glow"></span>
        <span class="top-shine"></span>
        <svg-icon type="mdi" :path="mdiPlus" class="btn-icon" />
        <span>Добавить</span>
      </button>
    </div>

    <table class="data-table">
      <thead>
        <tr>
          <th>ФИО</th>
          <th>Логин</th>
          <th>Тип</th>
          <th>Адм</th>
          <th>Фотограф</th>
          <th>Парикмахер</th>
          <th>Тип зарплаты</th>
          <th>Дата приёма</th>
          <th>Примечания</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="users.length === 0">
          <td colspan="9" class="table-empty">+ Добавить пользователя</td>
        </tr>
        <tr
          v-for="user in users"
          :key="user.id"
          class="table-row-clickable"
          @click="onRowClick(user)"
        >
          <td>{{ user.full_name }}</td>
          <td>{{ user.login }}</td>
          <td>{{ ROLE_LABELS[user.role] ?? user.role }}</td>
          <td>{{ user.is_admin_role ? '✓' : '' }}</td>
          <td>{{ user.is_photographer ? '✓' : '' }}</td>
          <td>{{ user.is_hairdresser ? '✓' : '' }}</td>
          <td>{{ user.salary_type ? SALARY_LABELS[user.salary_type] ?? user.salary_type : '' }}</td>
          <td>{{ user.hired_at }}</td>
          <td>{{ user.notes }}</td>
        </tr>
      </tbody>
    </table>
  </div>

  <UserActionsModal
    v-if="showActions && selectedUser"
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
    v-if="showFire && selectedUser"
    :user="selectedUser"
    @close="showFire = false"
    @confirm="onFireConfirm"
  />
  <UserPermissionsModal
    v-if="showPermissions && selectedUser"
    :user="selectedUser"
    @close="showPermissions = false"
  />
</template>
