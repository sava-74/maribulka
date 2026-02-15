<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilMenu, mdilImage, mdilCurrencyRub, mdilSettings } from '@mdi/light-js'
import { mdiFileCabinet, mdiHomeOutline } from '@mdi/js'
import { useAuthStore } from '../stores/auth'
import { useNavigationStore } from '../stores/navigation'

const isExpanded = ref(false)
const auth = useAuthStore()
const nav = useNavigationStore()

const toggleSidebar = () => {
  isExpanded.value = !isExpanded.value
}

const navigateTo = (page: 'home' | 'portfolio' | 'accounting' | 'settings' | 'references') => {
  nav.navigateTo(page)
  isExpanded.value = false
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
      <!-- Домой -->
      <div class="nav-item">
        <button class="glass-button" @click="navigateTo('home')">
          <svg-icon type="mdi" :path="mdiHomeOutline" ></svg-icon>
          <span class="nav-text">Домой</span>
        </button>
      </div>

      <!-- Портфолио -->
      <div class="nav-item">
        <button class="glass-button" @click="navigateTo('portfolio')">
          <svg-icon type="mdi" :path="mdilImage" ></svg-icon>
          <span class="nav-text">Портфолио</span>
        </button>
      </div>

      <!-- Админ-секция -->
      <div v-if="auth.isAdmin" class="admin-section">
        <div class="nav-item">
          <button class="glass-button" @click="navigateTo('accounting')">
            <svg-icon type="mdi" :path="mdilCurrencyRub" ></svg-icon>
            <span class="nav-text">Бухгалтерия</span>
          </button>
        </div>
        <div class="nav-item">
          <button class="glass-button" @click="navigateTo('references')">
            <svg-icon type="mdi" :path="mdiFileCabinet" ></svg-icon>
            <span class="nav-text">Справочники</span>
          </button>
        </div>
        <div class="nav-item">
          <button class="glass-button" @click="navigateTo('settings')">
            <svg-icon type="mdi" :path="mdilSettings" ></svg-icon>
            <span class="nav-text">Настройки</span>
          </button>
        </div>
      </div>
    </div>
  </aside>
</template>

