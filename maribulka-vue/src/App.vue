<script setup lang="ts">
import { ref } from 'vue'
import TopBar from './components/TopBar.vue'
import SideBar from './components/SideBar.vue'
import LoginModal from './components/LoginModal.vue'

// Глобальные стили
import './assets/modal.css'
import './assets/content.css'

const isModalOpen = ref(false)
</script>

<template>
  <div class="app-container">
    <!-- Событие из топбара -->
    <TopBar @open-login="isModalOpen = true" />
    
    <SideBar />
    
    <main class="content">
      <h1>Добро пожаловать</h1>
      <p>Если ты видишь этот текст, значит всё работает.</p>
    </main>

    <!-- Использование компонента (теперь TS увидит, что он "прочитан") -->
    <LoginModal :is-visible="isModalOpen" @close="isModalOpen = false" />
  </div>
</template>