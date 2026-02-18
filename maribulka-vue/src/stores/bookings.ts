// Store для управления записями
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const API_URL = '/api'

export const useBookingsStore = defineStore('bookings', () => {
  const bookings = ref<any[]>([])
  const loading = ref(false)
  const currentMonth = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM

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
  async function fetchBookings(month?: string) {
    loading.value = true
    try {
      const queryMonth = month || currentMonth.value
      const response = await fetch(`${API_URL}/bookings.php?month=${queryMonth}`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      bookings.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки записей:', error)
      bookings.value = [] // Установить пустой массив при ошибке
    } finally {
      loading.value = false
    }
  }

  async function fetchBookingsByDate(date: string) {
    loading.value = true
    try {
      const response = await fetch(`${API_URL}/bookings.php?date=${date}`)
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
      const response = await fetch(`${API_URL}/bookings.php?id=${id}`)
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
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
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
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
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
        method: 'DELETE'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
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
  async function markAsCompleted(id: number) {
    try {
      const response = await fetch(`${API_URL}/bookings.php?action=complete&id=${id}`, {
        method: 'POST'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchBookings()
      }
      return result
    } catch (error) {
      console.error('Ошибка отметки выполнения:', error)
      return { success: false, error: error }
    }
  }

  async function markAsDelivered(id: number) {
    try {
      const response = await fetch(`${API_URL}/bookings.php?action=deliver&id=${id}`, {
        method: 'POST'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchBookings()
      }
      return result
    } catch (error) {
      console.error('Ошибка отметки доставки:', error)
      return { success: false, error: error }
    }
  }

  async function cancelBooking(id: number, cancelledBy: 'client' | 'photographer') {
    try {
      const response = await fetch(`${API_URL}/bookings.php?action=cancel&id=${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cancelled_by: cancelledBy })
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchBookings()
      }
      return result
    } catch (error) {
      console.error('Ошибка отмены записи:', error)
      return { success: false, error: error }
    }
  }

  async function addPayment(id: number, amount: number) {
    try {
      const response = await fetch(`${API_URL}/bookings.php?action=payment&id=${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount })
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
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
        method: 'POST'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
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
      const response = await fetch(`${API_URL}/bookings.php?action=get_next_id`)
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

    // Actions
    fetchBookings,
    fetchBookingsByDate,
    getBooking,
    createBooking,
    updateBooking,
    deleteBooking,
    markAsCompleted,
    markAsDelivered,
    cancelBooking,
    addPayment,
    quickPayment,
    setCurrentMonth,
    getNextId
  }
})