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

  // ========================================
  // ТИПЫ СЪЁМОК
  // ========================================
  async function fetchShootingTypes() {
    loadingShootingTypes.value = true
    try {
      const response = await fetch(`${API_URL}/shooting-types.php`)
      shootingTypes.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки типов съёмок:', error)
    } finally {
      loadingShootingTypes.value = false
    }
  }

  async function createShootingType(data: any) {
    const response = await fetch(`${API_URL}/shooting-types.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    const result = await response.json()
    if (result.success) {
      await fetchShootingTypes()
    }
    return result
  }

  async function updateShootingType(data: any) {
    const response = await fetch(`${API_URL}/shooting-types.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    const result = await response.json()
    if (result.success) {
      await fetchShootingTypes()
    }
    return result
  }

  async function deleteShootingType(id: number) {
    const response = await fetch(`${API_URL}/shooting-types.php?id=${id}`, {
      method: 'DELETE'
    })
    const result = await response.json()
    if (result.success) {
      await fetchShootingTypes()
    }
    return result
  }

  // ========================================
  // АКЦИИ
  // ========================================
  async function fetchPromotions() {
    loadingPromotions.value = true
    try {
      const response = await fetch(`${API_URL}/promotions.php`)
      promotions.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки акций:', error)
    } finally {
      loadingPromotions.value = false
    }
  }

  async function createPromotion(data: any) {
    const response = await fetch(`${API_URL}/promotions.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    const result = await response.json()
    if (result.success) {
      await fetchPromotions()
    }
    return result
  }

  async function updatePromotion(data: any) {
    const response = await fetch(`${API_URL}/promotions.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    const result = await response.json()
    if (result.success) {
      await fetchPromotions()
    }
    return result
  }

  async function deletePromotion(id: number) {
    const response = await fetch(`${API_URL}/promotions.php?id=${id}`, {
      method: 'DELETE'
    })
    const result = await response.json()
    if (result.success) {
      await fetchPromotions()
    }
    return result
  }

  // ========================================
  // КЛИЕНТЫ
  // ========================================
  async function fetchClients() {
    loadingClients.value = true
    try {
      const response = await fetch(`${API_URL}/clients.php`)
      clients.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки клиентов:', error)
    } finally {
      loadingClients.value = false
    }
  }

  async function searchClients(query: string) {
    const response = await fetch(`${API_URL}/clients.php?search=${encodeURIComponent(query)}`)
    return await response.json()
  }

  async function createClient(data: any) {
    const response = await fetch(`${API_URL}/clients.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    const result = await response.json()
    if (result.success) {
      await fetchClients()
    }
    return result
  }

  async function updateClient(data: any) {
    const response = await fetch(`${API_URL}/clients.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    const result = await response.json()
    if (result.success) {
      await fetchClients()
    }
    return result
  }

  async function deleteClient(id: number) {
    const response = await fetch(`${API_URL}/clients.php?id=${id}`, {
      method: 'DELETE'
    })
    const result = await response.json()
    if (result.success) {
      await fetchClients()
    }
    return result
  }

  return {
    // State
    shootingTypes,
    loadingShootingTypes,
    promotions,
    loadingPromotions,
    clients,
    loadingClients,

    // Actions
    fetchShootingTypes,
    createShootingType,
    updateShootingType,
    deleteShootingType,

    fetchPromotions,
    createPromotion,
    updatePromotion,
    deletePromotion,

    fetchClients,
    searchClients,
    createClient,
    updateClient,
    deleteClient
  }
})
