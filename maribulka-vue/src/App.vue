<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import TopBar from './components/TopBar.vue'
import LoginModal from './components/LoginModal.vue'
import LaunchPad from './components/launchpad/LaunchPad.vue'

const authStore = useAuthStore()
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
    </div>
  </div>
  <LoginModal :isVisible="showLogin" :origin="loginOrigin" @close="showLogin = false" />
</template>
