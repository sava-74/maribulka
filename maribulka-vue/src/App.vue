<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import { useNavigationStore } from './stores/navigation'
import TopBar from './components/TopBar.vue'
import LoginModal from './components/LoginModal.vue'
import LaunchPad from './components/launchpad/LaunchPad.vue'
import Home from './components/home/Home.vue'
import CalendarPanel from './components/calendar/CalendarPanel.vue'
import CalendarSidebar from './components/calendar/CalendarSidebar.vue'
import BookingsTable from './components/calendar/BookingsTable.vue'
import IncomeTable from './components/finance/income/IncomeTable.vue'
import ExpensesTable from './components/finance/expenses/ExpensesTable.vue'

const authStore = useAuthStore()
const navStore = useNavigationStore()
const showLogin = ref(false)
const loginOrigin = ref({ x: 0, y: 0, w: 0, h: 0 })
const showLaunchpad = ref(false)
const launchpadOrigin = ref({ x: 0, y: 0, w: 0, h: 0 })
const launchpadRef = ref<InstanceType<typeof LaunchPad> | null>(null)
const calendarPanelRef = ref<InstanceType<typeof CalendarPanel> | null>(null)
const sidebarDate = ref('')
const sidebarBookings = ref<any[]>([])
const sidebarIsDayView = ref(false)

function onSidebarUpdate(payload: { date: string; bookings: any[]; isDayView: boolean }) {
  sidebarDate.value = payload.date
  sidebarBookings.value = payload.bookings
  sidebarIsDayView.value = payload.isDayView
}

function openLogin(origin: { x: number, y: number, w: number, h: number }) {
  loginOrigin.value = origin
  showLogin.value = true
}

function openLaunchpad(origin: { x: number, y: number, w: number, h: number }) {
  launchpadOrigin.value = origin
  showLaunchpad.value = true
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
      <template v-if="navStore.currentPage === 'calendar'">
        <CalendarPanel ref="calendarPanelRef" @sidebar-update="onSidebarUpdate" />
        <CalendarSidebar
          v-if="!sidebarIsDayView"
          :date="sidebarDate"
          :bookings="sidebarBookings"
          @add="calendarPanelRef?.handleSidebarAdd()"
          @select="calendarPanelRef?.handleSidebarSelect($event)"
        />
      </template>
      <BookingsTable v-if="navStore.currentPage === 'bookings'" />
      <IncomeTable v-if="navStore.currentPage === 'income'" />
      <ExpensesTable v-if="navStore.currentPage === 'expenses'" />
    </div>
  </div>
  <LoginModal :isVisible="showLogin" :origin="loginOrigin" @close="showLogin = false" />
</template>
