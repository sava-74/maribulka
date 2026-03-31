<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import UserActionsModal from './UserActionsModal.vue'
import UserFormModal from './UserFormModal.vue'
import FireUserModal from './FireUserModal.vue'
import UserPermissionsModal from './UserPermissionsModal.vue'
import ViewUserModal from './ViewUserModal.vue'
import PadLoader from '../ui/padLoader/PadLoader.vue'

const auth = useAuthStore()

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
  salary_type: string | null
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

const users = ref<User[]>([])
const selectedUser = ref<User | null>(null)
const showActions = ref(false)
const showView = ref(false)
const showForm = ref(false)
const showFire = ref(false)
const showPermissions = ref(false)
const isCreating = ref(false)
const isEmpty = ref(false)

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

const isLoading = ref(true)
const loadProgress = ref(0)

async function loadUsers() {
  const res = await fetch('/api/users.php?action=list', { credentials: 'include' })
  const data = await res.json()
  if (data.success) users.value = data.data
}

onMounted(async () => {
  await loadUsers()
  loadProgress.value = 100
  await new Promise(resolve => setTimeout(resolve, 250))
  isLoading.value = false
})

function onRowClick(user: User) {
  isEmpty.value = false
  selectedUser.value = user
  showActions.value = true
}

function onOpenEmptyActions() {
  isEmpty.value = true
  selectedUser.value = null
  showActions.value = true
}

function onAdd() {
  showActions.value = false
  selectedUser.value = null
  isCreating.value = true
  showForm.value = true
}

function onView() {
  showActions.value = false
  showView.value = true
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
  <PadLoader v-if="isLoading" :progress="loadProgress" />
  <div class="padGlass padGlass-work data-table-panel">
    <div class="pad-title">Пользователи</div>

    <div class="data-table-scroll">
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
          <th>Принят</th>
          <th>Уволен</th>
          <th>Примечания</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="users.length === 0" class="row-empty" @click="onOpenEmptyActions">
          <td colspan="10" class="cell-empty">+ Добавить пользователя</td>
        </tr>
        <tr
          v-for="user in users"
          :key="user.id"
          :class="{ 'row-cancelled': user.fired_at }"
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
          <td>{{ user.fired_at }}</td>
          <td>{{ user.notes }}</td>
        </tr>
      </tbody>
    </table>
    </div>
  </div>

  <UserActionsModal
    v-if="showActions"
    :user="selectedUser ?? { id: 0, full_name: null, login: '', role: '' }"
    :current-role="auth.userRole ?? ''"
    :is-empty="isEmpty"
    @close="showActions = false"
    @add="onAdd"
    @view="onView"
    @edit="onEdit"
    @fire="onFire"
    @permissions="onPermissions"
  />
  <ViewUserModal
    v-if="showView && selectedUser"
    :user="selectedUser"
    @close="showView = false"
  />
  <UserFormModal
    v-if="showForm"
    :user-id="isCreating ? null : selectedUser?.id ?? null"
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
