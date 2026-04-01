import { defineStore } from 'pinia'
import { ref } from 'vue'

export type PageType =
  | 'home' | 'portfolio' | 'accounting' | 'settings' | 'references' | 'sandbox'
  | 'calendar' | 'bookings' | 'income' | 'expenses' | 'users'
  | 'clients' | 'shooting_types' | 'promotions' | 'expense_categories' | 'salary_types'

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
