<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import TopBar from './components/TopBar.vue'
import Home from './components/home/Home.vue'

const authStore = useAuthStore()

onMounted(async () => {
  document.documentElement.setAttribute('data-theme', 'dark')
  await authStore.checkSession()
})
</script>

<template>
  <div id="app">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <TopBar />
    <main>
      <Home />
    </main>
  </div>
</template>
