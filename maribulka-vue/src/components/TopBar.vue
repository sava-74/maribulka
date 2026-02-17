<script setup lang="ts">
import { ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilLogin, mdilLogout } from '@mdi/light-js'
import { useAuthStore } from '../stores/auth'
import ConfirmModal from './ConfirmModal.vue'

const auth = useAuthStore()
const emit = defineEmits(['open-login'])

const showConfirm = ref(false)

const handleAction = () => {
  if (auth.isAdmin) {
    showConfirm.value = true
  } else {
    emit('open-login')
  }
}

const handleLogout = async () => {
  await auth.logout()
  showConfirm.value = false
}
</script>

<template>
  <header class="top-bar">
    <div class="logo-area">
      <img src="/img/owner.jpg" alt="Марибулька" class="owner-photo">
      <span class="site-name">Фотостудия Марии</span>
    </div>

    <!-- Иконка меняется динамически: mdilLogin или mdilLogout -->
    <button class="glass-button" @click="handleAction">
      <svg-icon type="mdi" :path="auth.isAdmin ? mdilLogout : mdilLogin" />
    </button>
    <ConfirmModal
      :isVisible="showConfirm"
      message="Выйти из системы?"
      title="Подтверждение"
      @confirm="handleLogout"
      @cancel="showConfirm = false"
    />
  </header>
</template>