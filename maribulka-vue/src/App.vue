<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from './stores/auth'
import { useNavigationStore } from './stores/navigation'
import TopBar from './components/TopBar.vue'
import SideBar from './components/SideBar.vue'
import LoginModal from './components/LoginModal.vue'
import Accounting from './components/accounting/Accounting.vue'
import References from './components/accounting/References.vue'
import Settings from './components/accounting/Settings.vue'
import Home from './views/Home.vue'

// Глобальные стили
import './assets/modal.css'
import './assets/content.css'
import './assets/home.css'

const isModalOpen = ref(false)
const authStore = useAuthStore()
const navStore = useNavigationStore()
</script>

<template>
  <div class="app-container">
    <!-- Событие из топбара -->
    <TopBar @open-login="isModalOpen = true" />

    <SideBar />

    <main class="content">
      <!-- Главная страница -->
      <Home v-if="navStore.currentPage === 'home'" />

      <!-- Портфолио -->
      <div v-else-if="navStore.currentPage === 'portfolio'" class="page-portfolio">
        <h1>📸 Портфолио</h1>
        <p>Раздел в разработке</p>
      </div>

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
.welcome,
.page-portfolio,
.no-access {
  text-align: center;
  padding: 60px 20px;
  color: #fff;
}

.welcome h1,
.page-portfolio h1 {
  font-size: 48px;
  margin-bottom: 20px;
  font-weight: 700;
}

.welcome p,
.page-portfolio p,
.no-access p {
  font-size: 20px;
  color: rgba(255, 255, 255, 0.8);
  margin: 10px 0;
}

.no-access h2 {
  font-size: 36px;
  margin-bottom: 16px;
  color: #f87171;
}
</style>
