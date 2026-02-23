<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import { useNavigationStore } from './stores/navigation'
import TopBar from './components/TopBar.vue'
import SideBar from './components/SideBar.vue'
import LoginModal from './components/LoginModal.vue'
import Accounting from './components/accounting/Accounting.vue'
import References from './components/accounting/References.vue'
import Settings from './components/accounting/Settings.vue'
import Portfolio from './components/Portfolio.vue'
import Home from './views/Home.vue'

// Глобальные стили
import './assets/modal.css'
import './assets/content.css'
import './assets/home.css'
import './assets/theme.css' 
import './assets/app.css'

const isModalOpen = ref(false)
const authStore = useAuthStore()
const navStore = useNavigationStore()

// Проверяем сессию при загрузке приложения
onMounted(async () => {
  await authStore.checkSession()
})
</script>

<template>
  <div id="app" class="app-container">
    <!-- Событие из топбара -->
    <TopBar @open-login="isModalOpen = true" />

    <SideBar />

    <main class="content">
      <!-- Главная страница -->
      <Home v-if="navStore.currentPage === 'home'" />

      <!-- Портфолио -->
      <Portfolio v-else-if="navStore.currentPage === 'portfolio'" />

      <!-- Бухгалтерия (только для админа) -->
      <Accounting v-else-if="navStore.currentPage === 'accounting' && authStore.isAdmin" />

      <!-- Справочники (только для админа) -->
      <References v-else-if="navStore.currentPage === 'references' && authStore.isAdmin" />

      <!-- Настройки (только для админа) -->
      <Settings v-else-if="navStore.currentPage === 'settings' && authStore.isAdmin" />

      <!-- Если нет доступа -->
      <div v-else class="no-access">
        <h2>⛔ Нет доступа</h2>
        <p>Эта страница доступна только администратору</p>
      </div>
    </main>

    <!-- Использование компонента (теперь TS увидит, что он "прочитан") -->
    <LoginModal :is-visible="isModalOpen" @close="isModalOpen = false" />
  </div>
</template>

<style scoped>

</style>