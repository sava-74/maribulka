import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  // Проверяем localStorage СРАЗУ при инициализации
  const isAdmin = ref(localStorage.getItem('isAdmin') === 'true')

  function login(remember: boolean) {
    isAdmin.value = true
    if (remember) {
      localStorage.setItem('isAdmin', 'true')
    } else {
      // Если вошли без галочки, статус живет только до закрытия вкладки (в оперативной памяти)
      localStorage.removeItem('isAdmin')
    }
  }

  function logout() {
    isAdmin.value = false
    localStorage.removeItem('isAdmin')
  }

  return { isAdmin, login, logout }
})