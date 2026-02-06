<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilMenu, mdilImage, mdilCurrencyRub, mdilSettings } from '@mdi/light-js'
import { useAuthStore } from '../stores/auth'

const isExpanded = ref(false)
const auth = useAuthStore()

const toggleSidebar = () => {
  isExpanded.value = !isExpanded.value
}

const handleClickOutside = () => {
  if (isExpanded.value) {
    isExpanded.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <aside 
    
    :class="['side-bar', { expanded: isExpanded }]"
  >
    <!-- Кнопка меню -->
    <div class="nav-item">
      <button class="glass-button" @click.stop="toggleSidebar">
        <svg-icon type="mdi" :path="mdilMenu" ></svg-icon>
        <span class="nav-text">Меню</span>
      </button>
    </div>

    <div class="nav-links">
      <!-- Портфолио -->
      <div class="nav-item">
        <button class="glass-button">
          <svg-icon type="mdi" :path="mdilImage" ></svg-icon>
          <span class="nav-text">Портфолио</span>
        </button>
      </div>

      <!-- Админ-секция -->
      <div v-if="auth.isAdmin" class="admin-section">
        <div class="nav-item">
          <button class="glass-button">
            <svg-icon type="mdi" :path="mdilCurrencyRub" ></svg-icon>
            <span class="nav-text">Бухгалтерия</span>
          </button>
        </div>
        <div class="nav-item">
          <button class="glass-button">
            <svg-icon type="mdi" :path="mdilSettings" ></svg-icon>
            <span class="nav-text">Настройки</span>
          </button>
        </div>
      </div>
    </div>
  </aside>
</template>

