// Store для записей на съёмку
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
    const byDate: Record<string, any[]> = {}
    bookings.value.forEach(booking => {
      const date = booking.shooting_date
      if (!byDate[date]) {
        byDate[date] = []
      }
      byDate[date].push(booking)
    })
    return byDate
  })

  // ========================================
  // ACTIONS
  // ========================================
  async function fetchBookings(month?: string) {
    loading.value = true
    try {
      const queryMonth = month || currentMonth.value
      const response = await fetch(`${API_URL}/bookings.php?month=${queryMonth}`)
      bookings.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки записей:', error)
    } finally {
      loading.value = false
    }
  }

  async function fetchBookingsByDate(date: string) {
    loading.value = true
    try {
      const response = await fetch(`${API_URL}/bookings.php?date=${date}`)
      return await response.json()
    } catch (error) {
      console.error('Ошибка загрузки записей на дату:', error)
      return []
    } finally {
      loading.value = false
    }
  }

  async function getBooking(id: number) {
    const response = await fetch(`${API_URL}/bookings.php?id=${id}`)
    return await response.json()
  }

  async function createBooking(data: any) {
    const response = await fetch(`${API_URL}/bookings.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    const result = await response.json()
    if (result.success) {
      await fetchBookings()
    }
    return result
  }

  async function updateBooking(data: any) {
    const response = await fetch(`${API_URL}/bookings.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    const result = await response.json()
    if (result.success) {
      await fetchBookings()
    }
    return result
  }

  async function deleteBooking(id: number) {
    const response = await fetch(`${API_URL}/bookings.php?id=${id}`, {
      method: 'DELETE'
    })
    const result = await response.json()
    if (result.success) {
      await fetchBookings()
    }
    return result
  }

  // ========================================
  // СПЕЦИАЛЬНЫЕ ДЕЙСТВИЯ
  // ========================================
  async function markAsCompleted(id: number) {
    const response = await fetch(`${API_URL}/bookings.php?action=complete&id=${id}`, {
      method: 'POST'
    })
    const result = await response.json()
    if (result.success) {
      await fetchBookings()
    }
    return result
  }

  async function markAsDelivered(id: number) {
    const response = await fetch(`${API_URL}/bookings.php?action=deliver&id=${id}`, {
      method: 'POST'
    })
    const result = await response.json()
    if (result.success) {
      await fetchBookings()
    }
    return result
  }

  async function cancelBooking(id: number, cancelledBy: 'client' | 'photographer') {
    const response = await fetch(`${API_URL}/bookings.php?action=cancel&id=${id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cancelled_by: cancelledBy })
    })
    const result = await response.json()
    if (result.success) {
      await fetchBookings()
    }
    return result
  }

  async function addPayment(id: number, amount: number) {
    const response = await fetch(`${API_URL}/bookings.php?action=payment&id=${id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ amount })
    })
    const result = await response.json()
    if (result.success) {
      await fetchBookings()
    }
    return result
  }

  async function quickPayment(id: number) {
    const response = await fetch(`${API_URL}/bookings.php?action=quick_payment&id=${id}`, {
      method: 'POST'
    })
    const result = await response.json()
    if (result.success) {
      await fetchBookings()
    }
    return result
  }

  function setCurrentMonth(month: string) {
    currentMonth.value = month
    fetchBookings(month)
  }

  async function getNextId(): Promise<number> {
    const response = await fetch(`${API_URL}/bookings.php?action=get_next_id`)
    const data = await response.json()
    return data.next_id
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
