<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import { useNavigationStore } from './stores/navigation'
import TopBar from './components/TopBar.vue'
import LoginModal from './components/LoginModal.vue'
import LaunchPad from './components/launchpad/LaunchPad.vue'
import Home from './components/home/Home.vue'
import CalendarPanel from './components/calendar/CalendarPanel.vue'
import BookingsTable from './components/calendar/BookingsTable.vue'
import IncomeTable from './components/finance/income/IncomeTable.vue'
import ExpensesTable from './components/finance/expenses/ExpensesTable.vue'
import UsersTable from './components/users/UsersTable.vue'
import SandboxView from './sandbox/SandboxView.vue'
import UserFormModal from './components/users/UserFormModal.vue'
import SalaryTypesTable from './components/salaryTypes/SalaryTypesTable.vue'
import ClientsTable from './components/clients/ClientsTable.vue'
import ShootingTypesTable from './components/shootingTypes/ShootingTypesTable.vue'
import PromotionsTable from './components/promotions/PromotionsTable.vue'
import ExpenseCategoriesTable from './components/expenseCategories/ExpenseCategoriesTable.vue'

const authStore = useAuthStore()
const navStore = useNavigationStore()
const showLogin = ref(false)
const loginOrigin = ref({ x: 0, y: 0, w: 0, h: 0 })
const showLaunchpad = ref(false)
const launchpadOrigin = ref({ x: 0, y: 0, w: 0, h: 0 })
const launchpadRef = ref<InstanceType<typeof LaunchPad> | null>(null)

function openLogin(origin: { x: number, y: number, w: number, h: number }) {
  loginOrigin.value = origin
  showLogin.value = true
}

function openLaunchpad(origin: { x: number, y: number, w: number, h: number }) {
  launchpadOrigin.value = origin
  showLaunchpad.value = true
}

function onPasswordChanged() {
  authStore.mustChangePassword = false
}

onMounted(async () => {
  document.documentElement.setAttribute('data-theme', 'dark')
  await authStore.checkSession()
})
</script>

<template>
  <div class="app-root">
    <div class="app-bg-layer">
      <div class="orb orb-1"></div>
      <div class="orb orb-2"></div>
      <div class="orb orb-3"></div>
    </div>
    <TopBar :isLaunchpadOpen="showLaunchpad" @open-login="openLogin" @open-launchpad="openLaunchpad" @close-launchpad="launchpadRef?.close()" />
    <div class="worck-table">
      <LaunchPad ref="launchpadRef" :isVisible="showLaunchpad" :origin="launchpadOrigin" @close="showLaunchpad = false" />
      <Home v-if="navStore.currentPage === 'home'" />
      <CalendarPanel v-if="navStore.currentPage === 'calendar'"/>
      <BookingsTable v-if="navStore.currentPage === 'bookings'" />
      <IncomeTable v-if="navStore.currentPage === 'income'" />
      <ExpensesTable v-if="navStore.currentPage === 'expenses'" />
      <UsersTable v-if="navStore.currentPage === 'users'" />
      <SalaryTypesTable v-if="navStore.currentPage === 'salary_types'" />
      <ClientsTable v-if="navStore.currentPage === 'clients'" />
      <ShootingTypesTable v-if="navStore.currentPage === 'shooting_types'" />
      <PromotionsTable v-if="navStore.currentPage === 'promotions'" />
      <ExpenseCategoriesTable v-if="navStore.currentPage === 'expense_categories'" />
      <SandboxView v-if="navStore.currentPage === 'sandbox'" />
    </div>
  </div>
  <LoginModal :isVisible="showLogin" :origin="loginOrigin" @close="showLogin = false" />
  <UserFormModal
    v-if="authStore.mustChangePassword && authStore.userId"
    :user-id="authStore.userId"
    :force-edit="true"
    @save="onPasswordChanged"
  />
</template>
