<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilLogin, mdilLogout } from '@mdi/light-js'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const emit = defineEmits(['open-login'])

const handleAction = () => {
  if (auth.isAdmin) {
    if (confirm('Выйти из системы?')) auth.logout()
  } else {
    emit('open-login')
  }
}
</script>

<template>
  <header class="top-bar">
    <div class="logo-area">
      <img src="/img/owner.jpg" alt="Марибулька" class="owner-photo">
      <span class="site-name">Марибулька</span>
    </div>

    <!-- Иконка меняется динамически: mdilLogin или mdilLogout -->
    <button class="glass-button" @click="handleAction">
      <svg-icon type="mdi" :path="auth.isAdmin ? mdilLogout : mdilLogin" />
    </button>
  </header>
</template>