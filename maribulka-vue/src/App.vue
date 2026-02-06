<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from './stores/auth'
import TopBar from './components/TopBar.vue'
import SideBar from './components/SideBar.vue'
import LoginModal from './components/LoginModal.vue'
import Accounting from './components/accounting/Accounting.vue'

// Глобальные стили
import './assets/modal.css'
import './assets/content.css'

const isModalOpen = ref(false)
const authStore = useAuthStore()
</script>

<template>
  <div class="app-container">
    <!-- Событие из топбара -->
    <TopBar @open-login="isModalOpen = true" />
    
    <SideBar />
    
    <main class="content">
      <!-- Если залогинен - показываем бухгалтерию -->
      <Accounting v-if="authStore.isAuthenticated" />

      <!-- Если не залогинен - приветствие -->
      <div v-else class="welcome">
        <h1>Добро пожаловать в Maribulka</h1>
        <p>Система бухгалтерского учёта для фотографа</p>
        <p>Войдите чтобы начать работу →</p>
      </div>
    </main>

    <!-- Использование компонента (теперь TS увидит, что он "прочитан") -->
    <LoginModal :is-visible="isModalOpen" @close="isModalOpen = false" />
  </div>
</template>