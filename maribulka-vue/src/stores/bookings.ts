// Store для управления записями
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const API_URL = '/api'

export const useBookingsStore = defineStore('bookings', () => {
  const bookings = ref<any[]>([])
  const loading = ref(false)
  const currentMonth = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM

  // Кеш по месяцам с TTL
  const cache = ref<Record<string, { data: any[], loadedAt: number }>>({})
  const CACHE_TTL = 5 * 60 * 1000 // 5 минут

  function getCached(monthKey: string): any[] | null {
    const entry = cache.value[monthKey]
    if (!entry) return null
    if (Date.now() - entry.loadedAt > CACHE_TTL) {
      delete cache.value[monthKey]
      return null
    }
    return entry.data
  }

  function setCached(monthKey: string, data: any[]) {
    cache.value[monthKey] = { data, loadedAt: Date.now() }
  }

  function invalidateCache(monthKey?: string) {
    if (monthKey) {
      delete cache.value[monthKey]
    } else {
      cache.value = {}
    }
  }

  // ========================================
  // COMPUTED
  // ========================================
  const bookingsByDate = computed(() => {
    // Группировка записей по дате
    return bookings.value.reduce((acc, booking) => {
      const date = booking.booking_date
      if (!acc[date]) {
        acc[date] = []
      }
      acc[date].push(booking)
      return acc
    }, {})
  })

  // ========================================
  // ЗАПИСИ
  // ========================================
  async function fetchBookings(start?: string, end?: string) {
    const monthKey = start ? start.slice(0, 7) : currentMonth.value

    // Проверяем кеш
    const cached = getCached(monthKey)
    if (cached) {
      bookings.value = cached
      return
    }

    loading.value = true
    try {
      let url = `${API_URL}/bookings.php`
      if (start && end) {
        url += `?start=${start}&end=${end}`
      } else {
        url += `?month=${currentMonth.value}`
      }

      const response = await fetch(url, { credentials: 'include' })
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      const data = await response.json()
      setCached(monthKey, data)
      bookings.value = data
    } catch (error) {
      console.error('Ошибка загрузки записей:', error)
      bookings.value = []
    } finally {
      loading.value = false
    }
  }

  async function fetchBookingsByDate(date: string) {
    loading.value = true
    try {
      const response = await fetch(`${API_URL}/bookings.php?date=${date}`, { credentials: 'include' })
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return await response.json()
    } catch (error) {
      console.error('Ошибка загрузки записей на дату:', error)
      return []
    } finally {
      loading.value = false
    }
  }

  async function getBooking(id: number) {
    try {
      const response = await fetch(`${API_URL}/bookings.php?id=${id}`, { credentials: 'include' })
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return await response.json()
    } catch (error) {
      console.error('Ошибка получения записи:', error)
      return null
    }
  }

  async function createBooking(data: any) {
    try {
      const response = await fetch(`${API_URL}/bookings.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        invalidateCache(currentMonth.value)
        await fetchBookings()
      }
      return result
    } catch (error) {
      console.error('Ошибка создания записи:', error)
      return { success: false, error: error }
    }
  }

  async function updateBooking(data: any) {
    try {
      const response = await fetch(`${API_URL}/bookings.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        invalidateCache(currentMonth.value)
        await fetchBookings()
      }
      return result
    } catch (error) {
      console.error('Ошибка обновления записи:', error)
      return { success: false, error: error }
    }
  }

  async function deleteBooking(id: number) {
    try {
      const response = await fetch(`${API_URL}/bookings.php?id=${id}`, {
        method: 'DELETE',
        credentials: 'include'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        invalidateCache(currentMonth.value)
        await fetchBookings()
      }
      return result
    } catch (error) {
      console.error('Ошибка удаления записи:', error)
      return { success: false, error: error }
    }
  }

  // ========================================
  // СПЕЦИАЛЬНЫЕ ДЕЙСТВИЯ
  // ========================================
  // НОВЫЙ БИЗНЕС-ПРОЦЕСС
  // ========================================

  /**
   * Подтвердить съёмку (new → in_progress)
   */
  async function confirmSession(id: number) {
    try {
      const response = await fetch(`${API_URL}/bookings.php?action=confirm_session&id=${id}`, {
        method: 'POST',
        credentials: 'include'
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result = await response.json()
      if (result.success) {
        invalidateCache(currentMonth.value)
        await fetchBookings()
      }
      return result
    } catch (error) {
      console.error('Ошибка подтверждения съёмки:', error)
      return { success: false, error: error }
    }
  }

  /**
   * Выдать заказ (in_progress → completed/partially/not_completed)
   * @param id ID заказа
   * @param result 'completed' | 'completed_partially' | 'not_completed'
   */
  async function completeOrder(id: number, result: 'completed' | 'completed_partially' | 'not_completed') {
    try {
      const response = await fetch(`${API_URL}/bookings.php?action=complete_order&id=${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ result })
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const responseData = await response.json()
      if (responseData.success) {
        invalidateCache(currentMonth.value)
        await fetchBookings()
      }
      return responseData
    } catch (error) {
      console.error('Ошибка выдачи заказа:', error)
      return { success: false, error: error }
    }
  }

  /**
   * Отменить заказ / Клиент не пришёл
   * @param id ID заказа
   * @param cancelledBy 'client' | 'photographer' | 'no_show'
   */
  async function cancelBooking(id: number, cancelledBy: 'client' | 'photographer' | 'no_show') {
    try {
      const response = await fetch(`${API_URL}/bookings.php?action=cancel&id=${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ cancelled_by: cancelledBy })
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result = await response.json()
      if (result.success) {
        invalidateCache(currentMonth.value)
        await fetchBookings()
      }
      return result
    } catch (error) {
      console.error('Ошибка отмены записи:', error)
      return { success: false, error: error }
    }
  }

  // ========================================
  // УСТАРЕВШИЕ (для обратной совместимости)
  // ========================================

  /**
   * @deprecated Использовать confirmSession()
   */
  async function markAsCompleted(id: number) {
    return confirmSession(id)
  }

  /**
   * @deprecated Использовать completeOrder()
   */
  async function markAsDelivered(id: number) {
    return completeOrder(id, 'completed')
  }

  async function addPayment(id: number, amount: number) {
    try {
      const response = await fetch(`${API_URL}/bookings.php?action=payment&id=${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ amount })
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        invalidateCache(currentMonth.value)
        await fetchBookings()
      }
      return result
    } catch (error) {
      console.error('Ошибка добавления платежа:', error)
      return { success: false, error: error }
    }
  }

  async function quickPayment(id: number) {
    try {
      const response = await fetch(`${API_URL}/bookings.php?action=quick_payment&id=${id}`, {
        method: 'POST',
        credentials: 'include'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        invalidateCache(currentMonth.value)
        await fetchBookings()
      }
      return result
    } catch (error) {
      console.error('Ошибка быстрого платежа:', error)
      return { success: false, error: error }
    }
  }

  function setCurrentMonth(month: string) {
    currentMonth.value = month
    fetchBookings(month)
  }

  async function getNextId(): Promise<number> {
    try {
      const response = await fetch(`${API_URL}/bookings.php?action=get_next_id`, { credentials: 'include' })
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      const data = await response.json()
      return data.next_id
    } catch (error) {
      console.error('Ошибка получения следующего ID:', error)
      return -1
    }
  }

  return {
    // State
    bookings,
    loading,
    currentMonth,

    // Computed
    bookingsByDate,

    // Cache
    invalidateCache,

    // Actions
    fetchBookings,
    fetchBookingsByDate,
    getBooking,
    createBooking,
    updateBooking,
    deleteBooking,
    // Новый бизнес-процесс
    confirmSession,
    completeOrder,
    cancelBooking,
    // Устаревшие (обратная совместимость)
    markAsCompleted,
    markAsDelivered,
    // Оплата
    addPayment,
    quickPayment,
    setCurrentMonth,
    getNextId
  }
})