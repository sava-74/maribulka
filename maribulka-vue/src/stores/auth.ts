import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useNavigationStore } from './navigation'
import { hasPermission, type Role, type Section, type Action } from './permissions'

export const useAuthStore = defineStore('auth', () => {
  const isAdmin = ref(false)
  const isAuthenticated = ref(false)
  const isLoading = ref(false)
  const userName = ref('')
  const userProfession = ref('')
  const userId = ref<number | null>(null)
  const userRole = ref<Role>(5)
  const userSpecializations = ref({ photographer: false, hairdresser: false, admin_role: false })
  const userPermissions = ref<Array<{ section: string; action: string; allowed: boolean }>>([])
  const mustChangePassword = ref(false)
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

  function setUser(user: any) {
    userRole.value = (user.role as number) ?? 5
    userName.value = user.name ?? ''
    userProfession.value = user.permission_name ?? ''
    userId.value = user.id ?? null
    userSpecializations.value = user.specializations ?? { photographer: false, hairdresser: false, admin_role: false }
    userPermissions.value = user.permissions ?? []
    isAdmin.value = userRole.value === 1 || userRole.value === 2
    isAuthenticated.value = true
    localStorage.setItem('isAdmin', isAdmin.value ? 'true' : 'false')
  }

  function clearUser() {
    userRole.value = 5
    userName.value = ''
    userProfession.value = ''
    userId.value = null
    userSpecializations.value = { photographer: false, hairdresser: false, admin_role: false }
    userPermissions.value = []
    isAdmin.value = false
    isAuthenticated.value = false
    localStorage.removeItem('isAdmin')
  }

  function can(section: Section, action: Action): boolean {
    return hasPermission(userRole.value, section, action, userPermissions.value)
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
        if (data.success && data.isAuthenticated) {
          setUser(data.user)
          if (!data.rememberMe) startHeartbeat()
          return true
        }
      }

      // Если сессия невалидна - очищаем
      clearUser()
      return false
    } catch (error) {
      console.error('Ошибка проверки сессии:', error)
      clearUser()
      return false
    } finally {
      isLoading.value = false
    }
  }

  // Вход (теперь только устанавливает флаг, реальный вход через API в LoginModal)
  function login(remember: boolean, user?: { id: number; name: string; role?: number; specializations?: any; permissions?: any[] }) {
    if (user) {
      setUser(user)
    } else {
      isAdmin.value = true
      localStorage.setItem('isAdmin', 'true')
    }
    if (remember) {
      stopHeartbeat()
    } else {
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
      clearUser()
      useNavigationStore().navigateTo('home')
    }
  }

  return {
    isAdmin,
    isAuthenticated,
    isLoading,
    userName,
    userProfession,
    userId,
    userRole,
    userSpecializations,
    userPermissions,
    mustChangePassword,
    can,
    login,
    logout,
    checkSession
  }
})
