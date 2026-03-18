import { defineStore } from 'pinia'
import { ref } from 'vue'

export type PageType = 'home' | 'portfolio' | 'accounting' | 'settings' | 'references' | 'sandbox' | 'calendar' | 'bookings' | 'income' | 'expenses' | 'users'

export const useNavigationStore = defineStore('navigation', () => {
  const currentPage = ref<PageType>('home')

  function navigateTo(page: PageType) {
    currentPage.value = page
  }

  return {
    currentPage,
    navigateTo
  }
})
