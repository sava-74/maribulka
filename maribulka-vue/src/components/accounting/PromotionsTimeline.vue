<script setup lang="ts">
import { computed } from 'vue'

interface Promotion {
  id: number
  name: string
  discount_percent: number
  start_date: string | null
  end_date: string | null
}

const props = defineProps<{
  promotions: Promotion[]
}>()

const currentYear = new Date().getFullYear()

// Количество дней в каждом месяце
const daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]

// Проверка високосного года
function isLeapYear(year: number): boolean {
  return (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0
}

// Корректируем февраль для високосного года
if (isLeapYear(currentYear)) {
  daysInMonth[1] = 29
}

const daysInYear = daysInMonth.reduce((sum, days) => sum + days, 0)

// Вычисляем процентную ширину каждого месяца на основе реальных дней
const months = [
  { short: 'Янв', full: 'Январь', num: 1, days: daysInMonth[0]! },
  { short: 'Фев', full: 'Февраль', num: 2, days: daysInMonth[1]! },
  { short: 'Мар', full: 'Март', num: 3, days: daysInMonth[2]! },
  { short: 'Апр', full: 'Апрель', num: 4, days: daysInMonth[3]! },
  { short: 'Май', full: 'Май', num: 5, days: daysInMonth[4]! },
  { short: 'Июн', full: 'Июнь', num: 6, days: daysInMonth[5]! },
  { short: 'Июл', full: 'Июль', num: 7, days: daysInMonth[6]! },
  { short: 'Авг', full: 'Август', num: 8, days: daysInMonth[7]! },
  { short: 'Сен', full: 'Сентябрь', num: 9, days: daysInMonth[8]! },
  { short: 'Окт', full: 'Октябрь', num: 10, days: daysInMonth[9]! },
  { short: 'Ноя', full: 'Ноябрь', num: 11, days: daysInMonth[10]! },
  { short: 'Дек', full: 'Декабрь', num: 12, days: daysInMonth[11]! }
].map(month => ({
  ...month,
  width: (month.days / daysInYear) * 100
}))

// Вспомогательная функция: парсинг даты из строки YYYY-MM-DD без UTC сдвига
function parseLocalDate(dateStr: string): Date {
  const [year, month, day] = dateStr.split('-').map(Number)
  return new Date(year!, month! - 1, day!)
}

// Вспомогательная функция: день года (1-365/366)
function getDayOfYear(dateStr: string): number {
  const [year, month, day] = dateStr.split('-').map(Number)

  // Массив кумулятивных дней до начала каждого месяца
  const cumulativeDays = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334]

  let dayOfYear = cumulativeDays[month! - 1]! + day!

  // Если високосный год и месяц после февраля, добавляем 1
  if (isLeapYear(year!) && month! > 2) {
    dayOfYear += 1
  }

  return dayOfYear
}

// Проверяет, является ли день первым числом месяца
function isFirstOfMonth(dayOfYear: number): boolean {
  // Кумулятивные дни (день 1=1 янв, 32=1 фев, 60=1 мар, ...)
  const firstDays = [1, 32, 60, 91, 121, 152, 182, 213, 244, 274, 305, 335]

  // Корректировка для високосного года (после февраля добавляем +1)
  if (isLeapYear(currentYear)) {
    return firstDays.slice(0, 2).includes(dayOfYear) ||
           firstDays.slice(2).map(d => d + 1).includes(dayOfYear)
  }

  return firstDays.includes(dayOfYear)
}

// Фильтруем только акции с датами (не бессрочные) и только текущего года
const timedPromotions = computed(() => {
  return props.promotions
    .filter(p => {
      if (!p.start_date || !p.end_date) return false
      const startYear = parseLocalDate(p.start_date).getFullYear()
      const endYear = parseLocalDate(p.end_date).getFullYear()
      // Берём только акции текущего года
      return startYear === currentYear || endYear === currentYear
    })
    .map(p => {
      const startYear = parseLocalDate(p.start_date!).getFullYear()
      const endYear = parseLocalDate(p.end_date!).getFullYear()

      // Если акция начинается в прошлом году, обрезаем начало
      let actualStartDate = p.start_date!
      if (startYear < currentYear) {
        actualStartDate = `${currentYear}-01-01`
      }

      // Если акция заканчивается в следующем году, обрезаем конец
      let actualEndDate = p.end_date!
      if (endYear > currentYear) {
        actualEndDate = `${currentYear}-12-31`
      }

      // Вычисляем порядковый день начала и конца акции (с учётом обрезки)
      const startDay = getDayOfYear(actualStartDate)
      const endDay = getDayOfYear(actualEndDate)

      // Позиция в процентах (пропорционально растягивается)
      const left = ((startDay - 1) / daysInYear) * 100  // день 1 = 0%
      const width = ((endDay - startDay + 1) / daysInYear) * 100  // ширина в %

      return {
        ...p,
        startDay,
        endDay,
        left,
        width,
        color: getPromotionColor(p.id)
      }
    })
})

// Генерируем цвета для акций (уникальные пастельные)
function getPromotionColor(id: number): string {
  const colors = [
    '#FF6B6B', // Красный
    '#4ECDC4', // Бирюзовый
    '#45B7D1', // Голубой
    '#FFA07A', // Лососевый
    '#98D8C8', // Мятный
    '#F7DC6F', // Желтый
    '#BB8FCE', // Фиолетовый
    '#85C1E2', // Небесно-голубой
    '#F8B88B', // Персиковый
    '#A9DFBF'  // Светло-зелёный
  ]
  return colors[id % colors.length] as string
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return ''
  const parts = dateStr.split('-')
  const day = parts[2] || ''
  const month = parts[1] || ''
  const year = parts[0] || ''
  return `${day}.${month}.${year.slice(2)}`
}
</script>

<template>
  <div class="timeline-container">
    <h3 class="timeline-title">График акций {{ currentYear }}</h3>

    <!-- Временная шкала: 365px = 365 дней -->
    <div class="timeline">
      <!-- Шкала с делениями и блоками акций -->
      <div class="timeline-scale">
        <!-- Блоки акций внутри шкалы -->
        <div class="timeline-promotions">
          <div
            v-for="promo in timedPromotions"
            :key="promo.id"
            class="timeline-promotion"
            :style="{
              left: promo.left + '%',
              width: promo.width + '%',
              backgroundColor: promo.color
            }"
            :title="`${promo.name} (${promo.discount_percent}%) — ${formatDate(promo.start_date!)} – ${formatDate(promo.end_date!)} (дни ${promo.startDay}-${promo.endDay})`"
          >
            <span class="promotion-label">{{ promo.name }} ({{ promo.discount_percent }}%)</span>
          </div>
        </div>

        <!-- Подписи месяцев -->
        <div class="timeline-months">
          <div
            v-for="month in months"
            :key="month.num"
            class="timeline-month-label"
            :style="{ width: month.width + '%' }"
          >
            {{ month.short }}
          </div>
        </div>

        <!-- Черточки -->
        <div class="timeline-ticks">
          <div
            v-for="day in daysInYear"
            :key="day"
            class="timeline-tick"
            :class="{ 'first-of-month': isFirstOfMonth(day) }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Легенда (если акций нет) -->
    <div v-if="timedPromotions.length === 0" class="timeline-empty">
      Нет активных акций с датами
    </div>
  </div>
</template>
