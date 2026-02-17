import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const isAdmin = ref(false)
  const isLoading = ref(false)

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
          // Синхронизируем с localStorage
          localStorage.setItem('isAdmin', 'true')
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
    } else {
      localStorage.removeItem('isAdmin')
    }
  }

  // Выход (используется в TopBar, вызывает API)
  async function logout() {
    try {
      await fetch('/api/auth.php?action=logout', {
        method: 'POST',
        credentials: 'include'
      })
    } catch (error) {
      console.error('Ошибка выхода:', error)
    } finally {
      // Очищаем состояние независимо от результата API
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