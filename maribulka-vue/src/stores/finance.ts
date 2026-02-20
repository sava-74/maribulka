// Store для прихода и расхода
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const API_URL = '/api'

export const useFinanceStore = defineStore('finance', () => {
  const income = ref<any[]>([])
  const incomeByBooking = ref<any[]>([])
  const expenses = ref<any[]>([])
  const refundableBookings = ref<any[]>([]) // Заказы доступные для возврата
  const loadingIncome = ref(false)
  const loadingExpenses = ref(false)
  const currentMonth = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM для прихода
  const currentExpenseMonth = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM для расходов

  // ========================================
  // COMPUTED
  // ========================================
  const totalIncome = computed(() => {
    return income.value.reduce((sum, item) => sum + parseFloat(item.amount), 0)
  })

  const totalExpenses = computed(() => {
    return expenses.value.reduce((sum, item) => sum + parseFloat(item.amount), 0)
  })

  const profit = computed(() => {
    return totalIncome.value - totalExpenses.value
  })

  const profitability = computed(() => {
    if (totalIncome.value === 0) return 0
    return ((profit.value / totalIncome.value) * 100).toFixed(2)
  })

  // ========================================
  // ПРИХОД
  // ========================================
  async function fetchIncome(month?: string) {
    loadingIncome.value = true
    try {
      const queryMonth = month || currentMonth.value
      const response = await fetch(`${API_URL}/income.php?month=${queryMonth}`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      income.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки прихода:', error)
      income.value = [] // Установить пустой массив при ошибке
    } finally {
      loadingIncome.value = false
    }
  }

  async function fetchIncomeByBooking(bookingId: number) {
    try {
      const response = await fetch(`${API_URL}/income.php?booking_id=${bookingId}`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      incomeByBooking.value = await response.json()
      return incomeByBooking.value
    } catch (error) {
      console.error('Ошибка загрузки доходов по заказу:', error)
      incomeByBooking.value = []
      return []
    }
  }

  // Получить заказы доступные для возврата (статус new/failed + есть оплата)
  async function fetchRefundableBookings() {
    try {
      const response = await fetch(`${API_URL}/bookings.php?action=refundable`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      refundableBookings.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки заказов для возврата:', error)
      refundableBookings.value = []
    }
  }

  // ========================================
  // РАСХОД
  // ========================================
  async function fetchExpenses(month?: string) {
    loadingExpenses.value = true
    try {
      const queryMonth = month || currentExpenseMonth.value
      const response = await fetch(`${API_URL}/expenses.php?month=${queryMonth}`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      expenses.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки расходов:', error)
      expenses.value = [] // Установить пустой массив при ошибке
    } finally {
      loadingExpenses.value = false
    }
  }

  async function createExpense(data: any) {
    try {
      const response = await fetch(`${API_URL}/expenses.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchExpenses(currentExpenseMonth.value)
      }
      return result
    } catch (error) {
      console.error('Ошибка создания расхода:', error)
      return { success: false, error: error }
    }
  }

  async function updateExpense(data: any) {
    try {
      const response = await fetch(`${API_URL}/expenses.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchExpenses(currentExpenseMonth.value)
      }
      return result
    } catch (error) {
      console.error('Ошибка обновления расхода:', error)
      return { success: false, error: error }
    }
  }

  async function deleteExpense(id: number) {
    try {
      const response = await fetch(`${API_URL}/expenses.php?id=${id}`, {
        method: 'DELETE'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchExpenses(currentExpenseMonth.value)
      }
      return result
    } catch (error) {
      console.error('Ошибка удаления расхода:', error)
      return { success: false, error: error }
    }
  }

  async function deleteIncome(id: number) {
    try {
      const response = await fetch(`${API_URL}/income.php?id=${id}`, {
        method: 'DELETE'
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      if (result.success) {
        await fetchIncome()
      }
      return result
    } catch (error) {
      console.error('Ошибка удаления дохода:', error)
      return { success: false, error: error }
    }
  }

  function setCurrentMonth(month: string) {
    currentMonth.value = month
    fetchIncome(month)
  }

  function setCurrentExpenseMonth(month: string) {
    currentExpenseMonth.value = month
    fetchExpenses(month)
  }

  // Получить доход по типам съёмок
  async function fetchIncomeByShootingType(month?: string) {
    try {
      const queryMonth = month || currentMonth.value
      const response = await fetch(`${API_URL}/bookings.php?action=income_by_type&month=${queryMonth}`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return await response.json()
    } catch (error) {
      console.error('Ошибка загрузки дохода по типам съёмок:', error)
      return []
    }
  }

  return {
    // State
    income,
    incomeByBooking,
    expenses,
    refundableBookings,
    loadingIncome,
    loadingExpenses,
    currentMonth,
    currentExpenseMonth,

    // Computed
    totalIncome,
    totalExpenses,
    profit,
    profitability,

    // Actions
    fetchIncome,
    fetchIncomeByBooking,
    fetchRefundableBookings,
    fetchExpenses,
    createExpense,
    updateExpense,
    deleteExpense,
    deleteIncome,
    setCurrentMonth,
    setCurrentExpenseMonth,
    fetchIncomeByShootingType
  }
})