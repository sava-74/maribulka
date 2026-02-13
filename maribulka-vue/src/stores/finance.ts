// Store для прихода и расхода
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const API_URL = '/api'

export const useFinanceStore = defineStore('finance', () => {
  const income = ref<any[]>([])
  const incomeByBooking = ref<any[]>([])
  const expenses = ref<any[]>([])
  const loadingIncome = ref(false)
  const loadingExpenses = ref(false)
  const currentMonth = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM

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
      income.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки прихода:', error)
    } finally {
      loadingIncome.value = false
    }
  }

  async function fetchIncomeByBooking(bookingId: number) {
    const response = await fetch(`${API_URL}/income.php?booking_id=${bookingId}`)
    incomeByBooking.value = await response.json()
    return incomeByBooking.value
  }

  // ========================================
  // РАСХОД
  // ========================================
  async function fetchExpenses(month?: string) {
    loadingExpenses.value = true
    try {
      const queryMonth = month || currentMonth.value
      const response = await fetch(`${API_URL}/expenses.php?month=${queryMonth}`)
      expenses.value = await response.json()
    } catch (error) {
      console.error('Ошибка загрузки расходов:', error)
    } finally {
      loadingExpenses.value = false
    }
  }

  async function createExpense(data: any) {
    const response = await fetch(`${API_URL}/expenses.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    const result = await response.json()
    if (result.success) {
      await fetchExpenses()
    }
    return result
  }

  async function updateExpense(data: any) {
    const response = await fetch(`${API_URL}/expenses.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    const result = await response.json()
    if (result.success) {
      await fetchExpenses()
    }
    return result
  }

  async function deleteExpense(id: number) {
    const response = await fetch(`${API_URL}/expenses.php?id=${id}`, {
      method: 'DELETE'
    })
    const result = await response.json()
    if (result.success) {
      await fetchExpenses()
    }
    return result
  }

  async function deleteIncome(id: number) {
    const response = await fetch(`${API_URL}/income.php?id=${id}`, {
      method: 'DELETE'
    })
    const result = await response.json()
    if (result.success) {
      await fetchIncome()
    }
    return result
  }

  function setCurrentMonth(month: string) {
    currentMonth.value = month
    fetchIncome(month)
    fetchExpenses(month)
  }

  return {
    // State
    income,
    incomeByBooking,
    expenses,
    loadingIncome,
    loadingExpenses,
    currentMonth,

    // Computed
    totalIncome,
    totalExpenses,
    profit,
    profitability,

    // Actions
    fetchIncome,
    fetchIncomeByBooking,
    fetchExpenses,
    createExpense,
    updateExpense,
    deleteExpense,
    deleteIncome,
    setCurrentMonth
  }
})
