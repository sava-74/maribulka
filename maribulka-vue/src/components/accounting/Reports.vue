<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { 
  mdiCashPlus,        // Доход (вместо 💰)
  mdiCashMinus,       // Расход (вместо 💸)  
  mdiCashMultiple,    // Прибыль (вместо 💵)
  mdiFinance          // Рентабельность (вместо 📈)
} from '@mdi/js'
import { useFinanceStore } from '../../stores/finance'
import { Chart, BarController, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js'
import '../../assets/reports.css'
import '../../assets/layout.css'

// Регистрация компонентов Chart.js
Chart.register(BarController, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const financeStore = useFinanceStore()

// Русские названия месяцев
const monthNames = [
  'январь', 'февраль', 'март', 'апрель', 'май', 'июнь',
  'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'
]

// Периоды для фильтрации
const periods = [
  { value: 'month', label: 'Месяц' },
  { value: 'quarter', label: 'Квартал' },
  { value: 'year', label: 'Год' }
]

const selectedPeriod = ref('month')
const selectedDate = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM

// Мемоизированные вычисляемые свойства для оптимизации
const filteredData = computed(() => {
  const income = selectedPeriod.value === 'month' 
    ? financeStore.income.filter(item => item.date?.startsWith(selectedDate.value))
    : financeStore.income
  
  const expenses = selectedPeriod.value === 'month'
    ? financeStore.expenses.filter(item => item.date?.startsWith(selectedDate.value))
    : financeStore.expenses
  
  return { income, expenses }
})

const periodIncomeTotal = computed(() => {
  return filteredData.value.income.reduce((sum, item) => {
    const amount = parseFloat(item.amount || '0')
    return sum + (isNaN(amount) ? 0 : amount)
  }, 0)
})

const periodExpensesTotal = computed(() => {
  // Используем financeStore.expenses напрямую (данные из API расходов)
  const expensesData = selectedPeriod.value === 'month'
    ? financeStore.expenses.filter(item => item.date?.startsWith(selectedDate.value))
    : financeStore.expenses
  
  return expensesData.reduce((sum, item) => {
    const amount = parseFloat(item.amount || '0')
    return sum + (isNaN(amount) ? 0 : amount)
  }, 0)
})

const periodProfit = computed(() => {
  return periodIncomeTotal.value - periodExpensesTotal.value
})

const periodProfitability = computed(() => {
  if (periodIncomeTotal.value === 0) return '0.00'
  return ((periodProfit.value / periodIncomeTotal.value) * 100).toFixed(2)
})

// Статистика по категориям расходов (из API расходов)
const expensesByCategory = computed(() => {
  const categories: Record<string, number> = {}
  
  // Используем financeStore.expenses напрямую (данные из API расходов)
  const expensesData = selectedPeriod.value === 'month'
    ? financeStore.expenses.filter(item => item.date?.startsWith(selectedDate.value))
    : financeStore.expenses
  
  expensesData.forEach(expense => {
    const categoryName = expense.category_name || 'Без категории'
    const amount = parseFloat(expense.amount || '0')
    categories[categoryName] = (categories[categoryName] || 0) + (isNaN(amount) ? 0 : amount)
  })
  
  return Object.entries(categories)
    .map(([name, amount]) => ({ name, amount }))
    .sort((a, b) => b.amount - a.amount)
})

// Статистика по источникам дохода
const incomeBySource = computed(() => {
  const sources: Record<string, number> = {}
  
  filteredData.value.income.forEach(income => {
    const sourceName = income.source || 'Не указан'
    const amount = parseFloat(income.amount || '0')
    sources[sourceName] = (sources[sourceName] || 0) + (isNaN(amount) ? 0 : amount)
  })
  
  return Object.entries(sources)
    .map(([name, amount]) => ({ name, amount }))
    .sort((a, b) => b.amount - a.amount)
})

// ========================================
// Chart.js для расходов по категориям
// ========================================
const expensesChartCanvas = ref<HTMLCanvasElement | null>(null)
let expensesChartInstance: Chart | null = null

// Цвета для диаграммы
const chartColors = [
  '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
  '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
]

const createExpensesChart = () => {
  if (!expensesChartCanvas.value) return
  
  // Уничтожить существующую диаграмму
  if (expensesChartInstance) {
    expensesChartInstance.destroy()
  }
  
  const data = expensesByCategory.value
  if (data.length === 0) return
  
  // Добавляем строку "Всего" первой
  const totalAmount = periodExpensesTotal.value
  const chartData = [
    { name: 'Всего', amount: totalAmount },
    ...data
  ]
  
  // Цвета: первой строке серый, остальным - цветные
  const colors = ['#6b7280', ...chartColors.slice(0, data.length)]
  
  expensesChartInstance = new Chart(expensesChartCanvas.value, {
    type: 'bar',
    data: {
      labels: chartData.map(item => item.name),
      datasets: [{
        label: 'Сумма (₽)',
        data: chartData.map(item => item.amount),
        backgroundColor: colors.slice(0, chartData.length),
        borderColor: colors.slice(0, chartData.length).map(color => color),
        borderWidth: 1,
        barThickness: 'flex',
        maxBarThickness: 40,
        minBarLength: 2,
        barPercentage: 0.6,
        categoryPercentage: 1.5
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      layout: {
        padding: {
          left: 0,
          right: 10,
          top: 10,
          bottom: 10
        }
      },
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              const rawValue = context.parsed.x
              const value = rawValue ?? 0
              const label = context.label
              
              if (label === 'Всего') {
                return `${value.toLocaleString('ru-RU')} ₽`
              }
              
              const total = periodExpensesTotal.value
              const percent = total > 0 ? ((value / total) * 100).toFixed(1) : '0'
              return `${value.toLocaleString('ru-RU')} ₽ (${percent}%)`
            }
          }
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          max: totalAmount, // Максимум = расходы всего
          grid: {
            display: false
          },
          ticks: {
            callback: function(value) {
              // Скрываем последний тик
              if (value >= totalAmount) return ''
              return Number(value).toLocaleString('ru-RU') + ' ₽'
            },
            font: {
              size: 10
            }
          }
        },
        y: {
          grid: {
            display: false
          },
          ticks: {
            display: false
          }
        }
      }
    },
    plugins: [{
      id: 'barLabels',
      afterDatasetsDraw(chart: any) {
        const { ctx, chartArea } = chart
        if (!chartArea) return
        
        ctx.save()
        ctx.font = 'bold 10px sans-serif'
        ctx.textAlign = 'left'
        ctx.textBaseline = 'middle'
        
        chart.getDatasetMeta(0).data.forEach((bar: any, index: number) => {
          const item = chartData[index]
          if (!item) return
          
          const value = item.amount
          const text = value.toLocaleString('ru-RU') + ' ₽'
          
          ctx.fillStyle = '#ffffff'
          const textWidth = ctx.measureText(text).width
          const barWidth = bar.width
          
          if (textWidth + 10 < barWidth) {
            ctx.fillText(text, bar.x - textWidth - 5, bar.y)
          } else {
            ctx.fillStyle = '#333'
            ctx.fillText(text, bar.x + barWidth + 5, bar.y)
          }
        })
        ctx.restore()
      }
    }]
  })
}

// Обновление диаграммы при изменении данных
watch(expensesByCategory, () => {
  nextTick(() => {
    createExpensesChart()
  })
}, { deep: true })

// Форматирование даты для отображения
const formatDateRange = computed(() => {
  const parts = selectedDate.value.split('-')
  if (parts.length !== 2) return ''
  
  const yearStr = parts[0]
  const monthStr = parts[1]
  
  if (!yearStr || !monthStr) return ''
  
  const year = parseInt(yearStr)
  const month = parseInt(monthStr) - 1
  const date = new Date(year, month)
  
  switch (selectedPeriod.value) {
    case 'month':
      return `${monthNames[date.getMonth()]} ${year}`
    case 'quarter':
      const quarter = Math.floor(date.getMonth() / 3) + 1
      return `${quarter} квартал ${year}`
    case 'year':
      return year.toString()
    default:
      return ''
  }
})

// Загрузка данных при монтировании
onMounted(() => {
  financeStore.fetchIncome()
  financeStore.fetchExpenses()
  nextTick(() => {
    createExpensesChart()
  })
})

// Реакция на изменение фильтров
watch([selectedPeriod, selectedDate], () => {
  if (selectedPeriod.value === 'month') {
    financeStore.setCurrentMonth(selectedDate.value)
    financeStore.setCurrentExpenseMonth(selectedDate.value)
  }
})
</script>

<template>
  <div class="reports">
    <!-- Панель с заголовком и фильтрами -->
    <div class="reports-header-panel">
      <h2 class="page-header-large">Финансовые отчёты</h2>
      <div class="filter-controls">
        <select v-model="selectedPeriod" class="glass-select">
          <option v-for="period in periods" :key="period.value" :value="period.value">
            {{ period.label }}
          </option>
        </select>
        <input 
          v-if="selectedPeriod === 'month'" 
          v-model="selectedDate" 
          type="month" 
          class="glass-input"
          :placeholder="formatDateRange"
        >
      </div>
    </div>

    <!-- Основные метрики за выбранный период -->
    <div class="stats-grid">
      <div class="stat-card income">
        <div class="icon">
          <svg-icon type="mdi" :path="mdiCashPlus" size="48" />
        </div>
        <div class="income-content centered-content">
          <h3>Доход</h3>
          <p class="value">{{ periodIncomeTotal.toLocaleString('ru-RU', { minimumFractionDigits: 2 }) }} ₽</p>
        </div>
      </div>

      <div class="stat-card expenses">
        <div class="icon">
          <svg-icon type="mdi" :path="mdiCashMinus" size="48" />
        </div>
        <div class="expenses-content centered-content">
          <h3>Расход</h3>
          <p class="value">{{ periodExpensesTotal.toLocaleString('ru-RU', { minimumFractionDigits: 2 }) }} ₽</p>
        </div>
      </div>

      <div class="stat-card profit">
        <div class="icon">
          <svg-icon type="mdi" :path="mdiCashMultiple" size="48" />
        </div>
        <div class="profit-content centered-content">
          <h3>Прибыль</h3>
          <p class="value" :class="{ negative: periodProfit < 0 }">
            {{ periodProfit.toLocaleString('ru-RU', { minimumFractionDigits: 2 }) }} ₽
          </p>
        </div>
      </div>

      <div class="stat-card profitability">
        <div class="icon">
          <svg-icon type="mdi" :path="mdiFinance" size="48" />
        </div>
        <div class="profitability-content centered-content">
          <h3>Рентабельность</h3>
          <p class="value">{{ periodProfitability }}%</p>
        </div>
      </div>
    </div>

    <!-- Детальная аналитика -->
    <div class="analytics-section">
      <div class="analysis-grid">
        <!-- Расходы по категориям -->
        <div class="analysis-card">
          <h3 class="card-title">Расходы по категориям</h3>
          <div class="chart-container">
            <canvas ref="expensesChartCanvas"></canvas>
          </div>
          <div v-if="expensesByCategory.length === 0" class="no-data">
            Нет данных о расходах
          </div>
        </div>

        <!-- Доход по источникам -->
        <div class="analysis-card">
          <h3 class="card-title">Доход по источникам</h3>
          <div class="category-list">
            <div 
              v-for="source in incomeBySource" 
              :key="source.name"
              class="category-item"
            >
              <div class="category-header">
                <span class="category-name">{{ source.name }}</span>
                <span class="category-amount">{{ source.amount.toLocaleString('ru-RU', { minimumFractionDigits: 2 }) }} ₽</span>
              </div>
              <div class="progress-bar">
                <div 
                  class="progress-fill income" 
                  :style="{ width: periodIncomeTotal > 0 ? ((source.amount / periodIncomeTotal) * 100) + '%' : '0%' }"
                ></div>
              </div>
            </div>
            <div v-if="incomeBySource.length === 0" class="no-data">
              Нет данных о доходах
            </div>
          </div>
        </div>
      </div>

      <!-- Сравнение с предыдущим периодом -->
      <div class="comparison-card">
        <h3 class="card-title">Сравнение с предыдущим периодом</h3>
        <div class="comparison-grid">
          <div class="comparison-item">
            <span class="comparison-label">Доход</span>
            <span class="comparison-value positive">
              <svg-icon type="mdi" :path="mdiCashPlus" size="20" />
              +12.5%
            </span>
          </div>
          <div class="comparison-item">
            <span class="comparison-label">Расход</span>
            <span class="comparison-value negative">
              <svg-icon type="mdi" :path="mdiCashMinus" size="20" />
              +8.3%
            </span>
          </div>
          <div class="comparison-item">
            <span class="comparison-label">Прибыль</span>
            <span class="comparison-value positive">
              <svg-icon type="mdi" :path="mdiCashMultiple" size="20" />
              +18.7%
            </span>
          </div>
        </div>
        <p class="comparison-note">* Данные за предыдущий {{ selectedPeriod === 'month' ? 'месяц' : selectedPeriod }}</p>
      </div>
    </div>

    <!-- Информационный блок -->
    <div class="info">
      <p>Анализ за: {{ formatDateRange }}</p>
      <p>Всего записей дохода: {{ filteredData.income.length }}</p>
      <p>Всего записей расхода: {{ filteredData.expenses.length }}</p>
      <p v-if="periodProfit < 0" class="warning">
        Убыток! Рекомендуется проанализировать статьи расходов
      </p>
      <p v-else-if="periodProfit > 0" class="positive">
        Прибыль! Отличный финансовый результат
      </p>
    </div>
  </div>
</template>