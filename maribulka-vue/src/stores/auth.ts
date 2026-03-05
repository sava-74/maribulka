import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const isAdmin = ref(false)
  const isLoading = ref(false)
  let heartbeatTimer: ReturnType<typeof setInterval> | null = null

  function startHeartbeat() {
    stopHeartbeat()
    heartbeatTimer = setInterval(async () => {
      try {
        await fetch('/api/auth.php?action=ping', {
          method: 'POST',
          credentials: 'include'
        })
      } catch { /* ignore */ }
    }, 30_000)
  }

  function stopHeartbeat() {
    if (heartbeatTimer) {
      clearInterval(heartbeatTimer)
      heartbeatTimer = null
    }
  }

  // Проверка сессии на бэкенде
  async function checkSession() {
    isLoading.value = true
    try {
      const response = await fetch('/api/auth.php?action=check', {
        method: 'GET',
        credentials: 'include'
      })

      if (response.ok) {
        const data = await response.json()
        if (data.success && data.isAuthenticated && data.user?.role === 'admin') {
          isAdmin.value = true
          localStorage.setItem('isAdmin', 'true')
          if (!data.rememberMe) startHeartbeat()
          return true
        }
      }

      // Если сессия невалидна - очищаем
      isAdmin.value = false
      localStorage.removeItem('isAdmin')
      return false
    } catch (error) {
      console.error('Ошибка проверки сессии:', error)
      isAdmin.value = false
      localStorage.removeItem('isAdmin')
      return false
    } finally {
      isLoading.value = false
    }
  }

  // Вход (теперь только устанавливает флаг, реальный вход через API в LoginModal)
  function login(remember: boolean) {
    isAdmin.value = true
    if (remember) {
      localStorage.setItem('isAdmin', 'true')
      stopHeartbeat()
    } else {
      localStorage.removeItem('isAdmin')
      startHeartbeat()
    }
  }

  // Выход (используется в TopBar, вызывает API)
  async function logout() {
    stopHeartbeat()
    try {
      await fetch('/api/auth.php?action=logout', {
        method: 'POST',
        credentials: 'include'
      })
    } catch (error) {
      console.error('Ошибка выхода:', error)
    } finally {
      isAdmin.value = false
      localStorage.removeItem('isAdmin')
    }
  }

  return {
    isAdmin,
    isAuthenticated: isAdmin,
    isLoading,
    login,
    logout,
    checkSession
  }
})