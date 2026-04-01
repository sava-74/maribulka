<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline, mdiEye, mdiEyeOff } from '@mdi/js'
import { useAuthStore } from '../stores/auth'
import AlertModal from './AlertModal.vue'
import { useGenie } from '../composables/useGenie'

const props = defineProps<{
  isVisible: boolean
  origin?: { x: number, y: number, w: number, h: number }
}>()
const emit = defineEmits(['close'])

const panelRef = ref<HTMLElement | null>(null)
const { closing, close } = useGenie(panelRef, () => props.isVisible, () => emit('close'))

const genieStyle = computed(() => {
  if (!props.origin) return {}
  const cx = window.innerWidth / 2
  const cy = window.innerHeight / 2
  return {
    '--genie-dx': `${props.origin.x - cx}px`,
    '--genie-dy': `${props.origin.y - cy}px`,
  }
})

const rememberMe = ref(false)
const auth = useAuthStore()
const password = ref('')
const login = ref(localStorage.getItem('lastLogin') ?? '')
const showPassword = ref(false)

watch(() => props.isVisible, (val) => {
  if (!val) {
    password.value = ''
    showPassword.value = false
  }
})

const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

const handleLogin = async () => {
  try {
    const response = await fetch('/api/auth.php?action=login', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ login: login.value, password: password.value, rememberMe: rememberMe.value })
    });

    if (response.ok) {
      const data = await response.json()
      if (data.success) {
        localStorage.setItem('lastLogin', login.value)
        auth.login(rememberMe.value, data.user)
        if (data.mustChangePassword) {
          auth.mustChangePassword = true
        }
        password.value = ''
        close()
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
    <div v-if="isVisible" class="modal-overlay" :class="{ 'overlay-leave': closing }">
      <div ref="panelRef" class="padGlass modal-sm" :class="closing ? 'genie-leave' : 'genie-enter'" :style="genieStyle">
        <div class="modal-glassTitle">Вход в систему</div>
        <div class="input-group">
          <input v-model="login" type="text" class="modal-input" placeholder="Логин" />
          <div class="input-with-icon">
            <span class="input-eye-left" @click="showPassword = !showPassword">
              <svg-icon type="mdi" :path="showPassword ? mdiEye : mdiEyeOff" class="btn-icon" />
            </span>
            <input v-model="password" :type="showPassword ? 'text' : 'password'" class="modal-input input-with-icon-left" placeholder="Пароль" @keyup.enter="handleLogin" />
          </div>
          <label class="remember-label">
            <input type="checkbox" v-model="rememberMe"> Запомнить меня
          </label>
        </div>
        <div class="ButtonFooter PosCenter">
          <button class="btnGlass iconText" @click="close()">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="handleLogin">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Войти</span>
          </button>
        </div>
      </div>
    </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>