<script setup lang="ts">
import { ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
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
    const response = await fetch('/api/auth.php?action=login', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ login: login.value, password: password.value })
    });

    if (response.ok) {
      const data = await response.json()
      if (data.success) {
        // Устанавливаем флаг авторизации
        auth.login(rememberMe.value)
        password.value = ''
        emit('close')
      } else {
        alertTitle.value = 'Ошибка доступа'
        alertMessage.value = data.message || 'Неверный логин или пароль'
        showAlert.value = true
      }
    } else {
      const data = await response.json()
      alertTitle.value = 'Ошибка доступа'
      alertMessage.value = data.message || 'Неверный логин или пароль'
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
      <div class="modal-glassTitle">Вход в систему</div>

      <div class="input-group">
        <input v-model="login" type="text" class="modal-input" placeholder="Логин" />
        <input v-model="password" type="password" class="modal-input" placeholder="Пароль" @keyup.enter="handleLogin" />
        <!-- Галочка "Запомнить меня" -->
        <label class="remember-label">
            <input type="checkbox" v-model="rememberMe"> Запомнить меня
        </label>
      </div>

      <div class="modal-actions">
        <!-- Кнопка "Отмена" -->
        <button class="glass-button" @click="emit('close')">
          <svg-icon type="mdi" :path="mdiCloseCircleOutline" />
          <span class="textButton">Отмена</span>
        </button>
        <!-- Кнопка "Войти" -->
        <button class="glass-button" @click="handleLogin">
          <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
          <span class="textButton">Войти</span>
        </button>
      </div>
      </div>
    </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>