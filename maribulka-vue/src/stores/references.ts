// Store для справочников (типы съёмок, акции, клиенты)
import { defineStore } from 'pinia'
import { ref } from 'vue'

const API_URL = '/api'

export const useReferencesStore = defineStore('references', () => {
  // Типы съёмок
  const shootingTypes = ref<any[]>([])
  const loadingShootingTypes = ref(false)

  // Акции
  const promotions = ref<any[]>([])
  const loadingPromotions = ref(false)

  // Клиенты
  const clients = ref<any[]>([])
  const loadingClients = ref(false)

  // Категории расходов
  const expenseCategories = ref<any[]>([])
  const loadingExpenseCategories = ref(false)

  // ========================================
  // ТИПЫ СЪЁМОК
  // ========================================
  async function fetchShootingTypes() {
    loadingShootingTypes.value = true
    try {
      const response = await fetch(`${API_URL}/shooting-types.php`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      shootingTypes.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки типов съёмок:', error)
      shootingTypes.value = [] // Установить пустой массив при ошибке
    } finally {
      loadingShootingTypes.value = false
    }
  }

  async function createShootingType(data: any) {
    try {
      const response = await fetch(`${API_URL}/shooting-types.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchShootingTypes()
      }
      return result
    } catch (error) {
      console.error('Ошибка создания типа съёмок:', error)
      return { success: false, error: error }
    }
  }

  async function updateShootingType(data: any) {
    try {
      const response = await fetch(`${API_URL}/shooting-types.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchShootingTypes()
      }
      return result
    } catch (error) {
      console.error('Ошибка обновления типа съёмок:', error)
      return { success: false, error: error }
    }
  }

  async function checkShootingTypeRelations(id: number) {
    try {
      const response = await fetch(`${API_URL}/shooting-types.php?check_relations=1&id=${id}`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return await response.json()
    } catch (error) {
      console.error('Ошибка проверки связей типа съёмок:', error)
      return { success: false, error: error }
    }
  }

  async function deleteShootingType(id: number) {
    try {
      const response = await fetch(`${API_URL}/shooting-types.php?id=${id}`, {
        method: 'DELETE'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchShootingTypes()
      }
      return result
    } catch (error) {
      console.error('Ошибка удаления типа съёмок:', error)
      return { success: false, error: error }
    }
  }

  // ========================================
  // АКЦИИ
  // ========================================
  async function fetchPromotions() {
    loadingPromotions.value = true
    try {
      const response = await fetch(`${API_URL}/promotions.php`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      promotions.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки акций:', error)
      promotions.value = [] // Установить пустой массив при ошибке
    } finally {
      loadingPromotions.value = false
    }
  }

  async function createPromotion(data: any) {
    try {
      const response = await fetch(`${API_URL}/promotions.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchPromotions()
      }
      return result
    } catch (error) {
      console.error('Ошибка создания акции:', error)
      return { success: false, error: error }
    }
  }

  async function updatePromotion(data: any) {
    try {
      const response = await fetch(`${API_URL}/promotions.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchPromotions()
      }
      return result
    } catch (error) {
      console.error('Ошибка обновления акции:', error)
      return { success: false, error: error }
    }
  }

  async function checkPromotionRelations(id: number) {
    try {
      const response = await fetch(`${API_URL}/promotions.php?check_relations=1&id=${id}`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return await response.json()
    } catch (error) {
      console.error('Ошибка проверки связей акции:', error)
      return { success: false, error: error }
    }
  }

  async function deletePromotion(id: number) {
    try {
      const response = await fetch(`${API_URL}/promotions.php?id=${id}`, {
        method: 'DELETE'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchPromotions()
      }
      return result
    } catch (error) {
      console.error('Ошибка удаления акции:', error)
      return { success: false, error: error }
    }
  }

  // ========================================
  // КЛИЕНТЫ
  // ========================================
  async function fetchClients() {
    loadingClients.value = true
    try {
      const response = await fetch(`${API_URL}/clients.php`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      clients.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки клиентов:', error)
      clients.value = [] // Установить пустой массив при ошибке
    } finally {
      loadingClients.value = false
    }
  }

  async function searchClients(query: string) {
    try {
      const response = await fetch(`${API_URL}/clients.php?search=${encodeURIComponent(query)}`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return await response.json()
    } catch (error) {
      console.error('Ошибка поиска клиентов:', error)
      return { success: false, error: error }
    }
  }

  async function createClient(data: any) {
    try {
      const response = await fetch(`${API_URL}/clients.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchClients()
      }
      return result
    } catch (error) {
      console.error('Ошибка создания клиента:', error)
      return { success: false, error: error }
    }
  }

  async function updateClient(data: any) {
    try {
      const response = await fetch(`${API_URL}/clients.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchClients()
      }
      return result
    } catch (error) {
      console.error('Ошибка обновления клиента:', error)
      return { success: false, error: error }
    }
  }

  async function checkClientRelations(id: number) {
    try {
      const response = await fetch(`${API_URL}/clients.php?check_relations=1&id=${id}`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return await response.json()
    } catch (error) {
      console.error('Ошибка проверки связей клиента:', error)
      return { success: false, error: error }
    }
  }

  async function deleteClient(id: number) {
    try {
      const response = await fetch(`${API_URL}/clients.php?id=${id}`, {
        method: 'DELETE'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchClients()
      }
      return result
    } catch (error) {
      console.error('Ошибка удаления клиента:', error)
      return { success: false, error: error }
    }
  }

  // ========================================
  // КАТЕГОРИИ РАСХОДОВ
  // ========================================
  async function fetchExpenseCategories() {
    loadingExpenseCategories.value = true
    try {
      const response = await fetch(`${API_URL}/expense-categories.php`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      expenseCategories.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки категорий расходов:', error)
      expenseCategories.value = [] // Установить пустой массив при ошибке
    } finally {
      loadingExpenseCategories.value = false
    }
  }

  async function createExpenseCategory(data: any) {
    try {
      const response = await fetch(`${API_URL}/expense-categories.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchExpenseCategories()
      }
      return result
    } catch (error) {
      console.error('Ошибка создания категории расходов:', error)
      return { success: false, error: error }
    }
  }

  async function updateExpenseCategory(data: any) {
    try {
      const response = await fetch(`${API_URL}/expense-categories.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchExpenseCategories()
      }
      return result
    } catch (error) {
      console.error('Ошибка обновления категории расходов:', error)
      return { success: false, error: error }
    }
  }

  async function checkExpenseCategoryRelations(id: number) {
    try {
      const response = await fetch(`${API_URL}/expense-categories.php?check_relations=1&id=${id}`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return await response.json()
    } catch (error) {
      console.error('Ошибка проверки связей категории расходов:', error)
      return { success: false, error: error }
    }
  }

  async function deleteExpenseCategory(id: number) {
    try {
      const response = await fetch(`${API_URL}/expense-categories.php?id=${id}`, {
        method: 'DELETE'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchExpenseCategories()
      }
      return result
    } catch (error) {
      console.error('Ошибка удаления категории расходов:', error)
      return { success: false, error: error }
    }
  }

  return {
    // State
    shootingTypes,
    loadingShootingTypes,
    promotions,
    loadingPromotions,
    clients,
    loadingClients,
    expenseCategories,
    loadingExpenseCategories,

    // Actions
    fetchShootingTypes,
    createShootingType,
    updateShootingType,
    deleteShootingType,
    checkShootingTypeRelations,

    fetchPromotions,
    createPromotion,
    updatePromotion,
    deletePromotion,
    checkPromotionRelations,

    fetchClients,
    searchClients,
    createClient,
    updateClient,
    deleteClient,
    checkClientRelations,

    fetchExpenseCategories,
    createExpenseCategory,
    updateExpenseCategory,
    deleteExpenseCategory,
    checkExpenseCategoryRelations
  }
})
