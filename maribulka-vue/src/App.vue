<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import TopBar from './components/TopBar.vue'
//import Home from './components/home/Home.vue'
import LoginModal from './components/LoginModal.vue'

const authStore = useAuthStore()
const showLogin = ref(false)

onMounted(async () => {
  document.documentElement.setAttribute('data-theme', 'dark')
  await authStore.checkSession()
})
</script>

<template>
  <div class="app-root">
    <TopBar @open-login="showLogin = true" />
    <div class="app-bg-layer">
      <div class="orb orb-1"></div>
      <div class="orb orb-2"></div>
      <div class="orb orb-3"></div>
    </div>
  </div>
  <!-- <main>
    <Home />
  </main> -->
  <LoginModal :isVisible="showLogin" @close="showLogin = false" />
</template>
