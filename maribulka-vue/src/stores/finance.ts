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
  async function fetchIncome(start?: string, end?: string) {
    loadingIncome.value = true
    try {
      // Если переданы start и end, используем их, иначе текущий месяц
      let url = `${API_URL}/income.php`
      if (start && end) {
        url += `?start=${start}&end=${end}`
      } else {
        url += `?month=${currentMonth.value}`
      }

      const response = await fetch(url)
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
  async function fetchExpenses(start?: string, end?: string) {
    loadingExpenses.value = true
    try {
      // Если переданы start и end, используем их, иначе текущий месяц
      let url = `${API_URL}/expenses.php`
      if (start && end) {
        url += `?start=${start}&end=${end}`
      } else {
        url += `?month=${currentExpenseMonth.value}`
      }

      const response = await fetch(url)
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
        await fetchExpenses()
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
        await fetchExpenses()
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
        await fetchExpenses()
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
    fetchIncome()
  }

  function setCurrentExpenseMonth(month: string) {
    currentExpenseMonth.value = month
    fetchExpenses()
  }

  // Получить доход по типам съёмок
  async function fetchIncomeByShootingType(start?: string, end?: string) {
    try {
      let url = `${API_URL}/bookings.php?action=income_by_type`
      if (start && end) {
        url += `&start=${start}&end=${end}`
      } else {
        url += `&month=${currentMonth.value}`
      }

      const response = await fetch(url)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return await response.json()
    } catch (error) {
      console.error('Ошибка загрузки дохода по типам съёмок:', error)
      return []
    }
  }

  // Получить общий баланс кассы (все приходы - все расходы)
  async function fetchCashBalance() {
    try {
      const response = await fetch(`${API_URL}/expenses.php?balance=true`)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return await response.json() // { totalIncome, totalExpenses, balance }
    } catch (error) {
      console.error('Ошибка загрузки баланса кассы:', error)
      return { totalIncome: 0, totalExpenses: 0, balance: 0 }
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
    fetchIncomeByShootingType,
    fetchCashBalance
  }
})