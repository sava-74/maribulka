<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
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

// Props от родителя
const props = defineProps<{
  periodStart: Date
  periodEnd: Date
}>()

const financeStore = useFinanceStore()

// Форматирование дат для API (YYYY-MM-DD)
function formatDateForAPI(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Мемоизированные вычисляемые свойства для оптимизации
// Данные уже отфильтрованы на backend по периоду из родительского компонента
const filteredData = computed(() => {
  return {
    income: financeStore.income,
    expenses: financeStore.expenses
  }
})

const periodIncomeTotal = computed(() => {
  return filteredData.value.income.reduce((sum, item) => {
    const amount = parseFloat(item.amount || '0')
    return sum + (isNaN(amount) ? 0 : amount)
  }, 0)
})

const periodExpensesTotal = computed(() => {
  return financeStore.expenses.reduce((sum, item) => {
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

// Статистика по категориям расходов (из таблицы расходов API)
const expensesByCategory = computed(() => {
  const categories: Record<string, number> = {}

  financeStore.expenses.forEach(expense => {
    const categoryName = expense.category_name || 'Без категории'
    const amount = parseFloat(expense.amount || '0')
    categories[categoryName] = (categories[categoryName] || 0) + (isNaN(amount) ? 0 : amount)
  })

  return Object.entries(categories)
    .map(([name, amount]) => ({ name, amount }))
    .sort((a, b) => b.amount - a.amount)
})


// ========================================
// Chart.js для расходов по категориям
// ========================================
const expensesChartCanvas = ref<HTMLCanvasElement | null>(null)
let expensesChartInstance: Chart | null = null

// Доход по типам съёмок из API
const incomeByShootingType = ref<any[]>([])

interface IncomeByType {
  name: string
  count: number
  total: number
}

const incomeByShootingTypeTotal = computed(() => {
  // Суммируем данные которые уже получены из API
  return incomeByShootingType.value.reduce((sum, item) => {
    const amount = parseFloat(item.total || '0')
    return sum + (isNaN(amount) ? 0 : amount)
  }, 0)
})

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
              if (Number(value) >= totalAmount) return ''
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
          
          ctx.fillStyle = '#ffffff' // Цвет текста по умолчанию для надписей внутри баров
          const textWidth = ctx.measureText(text).width
          const barWidth = bar.width
          
          if (textWidth + 10 < barWidth) {
            ctx.fillText(text, bar.x - textWidth - 5, bar.y)
          } else {
            ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--generalColorText').trim() || '#ffffff'
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

// ========================================
// Chart.js для дохода по источникам (типам съёмок)
// ========================================
const incomeChartCanvas = ref<HTMLCanvasElement | null>(null)
let incomeChartInstance: Chart | null = null

const createIncomeChart = async () => {
  if (!incomeChartCanvas.value) return
  
  // Уничтожить существующую диаграмму
  if (incomeChartInstance) {
    incomeChartInstance.destroy()
  }
  
  // Загружаем данные
  const start = formatDateForAPI(props.periodStart)
  const end = formatDateForAPI(props.periodEnd)
  const data = await financeStore.fetchIncomeByShootingType(start, end)
  incomeByShootingType.value = data
  
  if (!data || data.length === 0) return
  
  // Вычисляем сумму напрямую из данных
  const totalAmount = data.reduce((sum: number, item: any) => {
    return sum + (parseFloat(item.total || '0') || 0)
  }, 0)
  
  // Формируем данные для диаграммы с "Всего" первой строкой
  const chartData: IncomeByType[] = [
    { name: 'Всего', count: 0, total: totalAmount },
    ...data.map((item: any) => ({
      name: item.shooting_type_name || 'Unknown',
      count: parseInt(item.count || '0'),
      total: parseFloat(item.total || '0')
    }))
  ]
  
  // Цвета: первой строке серый, остальным - цветные
  const colors = ['#6b7280', ...chartColors.slice(0, data.length)]
  
  incomeChartInstance = new Chart(incomeChartCanvas.value, {
    type: 'bar',
    data: {
      labels: chartData.map((item: IncomeByType) => item.name),
      datasets: [{
        label: 'Сумма (₽)',
        data: chartData.map((item: IncomeByType) => item.total),
        backgroundColor: colors,
        borderColor: colors,
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
              const index = context.dataIndex
              const count = chartData[index]?.count || 0
              
              const total = incomeByShootingTypeTotal.value
              const percent = total > 0 ? ((value / total) * 100).toFixed(1) : '0'
              return `${count} - ${value.toLocaleString('ru-RU')} ₽ (${percent}%)`
            }
          }
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          max: totalAmount,
          grid: {
            display: false
          },
          ticks: {
            callback: function(value) {
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
      id: 'incomeBarLabels',
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
          
          // Формат: {кол-во} - {сумма}
          const text = item.count > 0 
            ? `${item.count} - ${item.total.toLocaleString('ru-RU')} ₽`
            : item.total.toLocaleString('ru-RU') + ' ₽'
          
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

// Watch на изменение периода
watch([() => props.periodStart, () => props.periodEnd], async () => {
  const start = formatDateForAPI(props.periodStart)
  const end = formatDateForAPI(props.periodEnd)

  await financeStore.fetchIncome(start, end)
  await financeStore.fetchExpenses(start, end)

  nextTick(() => {
    createExpensesChart()
    createIncomeChart()
  })
}, { immediate: true })
</script>

<template>
  <div class="reports">
    <!-- Панель с заголовком -->
    <div class="reports-header-panel">
      <h2 class="page-header-large">Финансовые отчёты</h2>
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

        <!-- Доход по источникам (типам съёмок) -->
        <div class="analysis-card">
          <h3 class="card-title">Доход по источникам</h3>
          <div class="chart-container">
            <canvas ref="incomeChartCanvas"></canvas>
          </div>
          <div v-if="incomeByShootingType.length === 0" class="no-data">
            Нет данных о доходах
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
        <p class="comparison-note">* Данные за предыдущий период</p>
      </div>
    </div>

    <!-- Информационный блок -->
    <div class="info">
      <p>Всего записей дохода: {{ filteredData.income.length }}</p>
      <p>Всего расходов: {{ periodExpensesTotal.toLocaleString('ru-RU', { minimumFractionDigits: 2 }) }} ₽</p>
      <p v-if="periodProfit < 0" class="warning">
        Убыток! Рекомендуется проанализировать статьи расходов
      </p>
      <p v-else-if="periodProfit > 0" class="positive">
        Прибыль! Отличный финансовый результат
      </p>
    </div>
  </div>
</template>