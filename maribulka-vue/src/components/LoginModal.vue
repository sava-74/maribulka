<script setup lang="ts">
import { ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCancel, mdilCheck } from '@mdi/light-js'
import { useAuthStore } from '../stores/auth'
import AlertModal from './AlertModal.vue'

const props = defineProps<{ isVisible: boolean }>()
const emit = defineEmits(['close'])
const rememberMe = ref(false) // Новая переменная для галочки
const auth = useAuthStore()
const password = ref('')
const login = ref('admin') // Логин по умолчанию

const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

const handleLogin = async () => {
  try {
    //const response = await fetch('http://localhost:3001/api/login', {
    const response = await fetch('/api/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ login: login.value, password: password.value })
    });

    if (response.ok) {
      auth.login(rememberMe.value);
      password.value = '';
      emit('close');
    } else {
      alertTitle.value = 'Ошибка доступа'
      alertMessage.value = 'Неверный пароль'
      showAlert.value = true
    }
  } catch (error) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Сервер базы данных не отвечает'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
      <h2>Вход в систему</h2>
      
      <div class="input-group">
        <input v-model="login" type="text" class="modal-input" placeholder="Логин" />
        <input v-model="password" type="password" class="modal-input" placeholder="Пароль" @keyup.enter="handleLogin" />
        <!-- Галочка "Запомнить меня" -->
        <label class="remember-label">
            <input type="checkbox" v-model="rememberMe"> Запомнить меня
        </label>
      </div>

      <div class="modal-actions">
        <button class="glass-button" @click="handleLogin">
          <svg-icon type="mdi" :path="mdilCheck" />
        </button>
        <button class="glass-button" @click="emit('close')">
          <svg-icon type="mdi" :path="mdilCancel" />
        </button>
      </div>
      </div>
    </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>