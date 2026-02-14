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

const months = [
  { short: 'Янв', full: 'Январь', num: 1 },
  { short: 'Фев', full: 'Февраль', num: 2 },
  { short: 'Мар', full: 'Март', num: 3 },
  { short: 'Апр', full: 'Апрель', num: 4 },
  { short: 'Май', full: 'Май', num: 5 },
  { short: 'Июн', full: 'Июнь', num: 6 },
  { short: 'Июл', full: 'Июль', num: 7 },
  { short: 'Авг', full: 'Август', num: 8 },
  { short: 'Сен', full: 'Сентябрь', num: 9 },
  { short: 'Окт', full: 'Октябрь', num: 10 },
  { short: 'Ноя', full: 'Ноябрь', num: 11 },
  { short: 'Дек', full: 'Декабрь', num: 12 }
]

const currentYear = new Date().getFullYear()
const currentMonth = new Date().getMonth() + 1

// Фильтруем только акции с датами (не бессрочные)
const timedPromotions = computed(() => {
  return props.promotions
    .filter(p => p.start_date && p.end_date)
    .map(p => {
      const start = new Date(p.start_date!)
      const end = new Date(p.end_date!)

      // Вычисляем позицию и ширину блока (в процентах от года)
      const startMonth = start.getMonth() + 1
      const endMonth = end.getMonth() + 1

      // Левая граница (процент от начала года)
      const left = ((startMonth - 1) / 12) * 100

      // Ширина блока
      const width = ((endMonth - startMonth + 1) / 12) * 100

      return {
        ...p,
        left,
        width,
        startMonth,
        endMonth,
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

    <!-- Временная шкала с месяцами -->
    <div class="timeline">
      <!-- Месяцы -->
      <div class="timeline-months">
        <div
          v-for="month in months"
          :key="month.num"
          class="timeline-month"
          :class="{ current: month.num === currentMonth }"
        >
          {{ month.short }}
        </div>
      </div>

      <!-- Блоки акций -->
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
          :title="`${promo.name} (${promo.discount_percent}%) — ${formatDate(promo.start_date!)} – ${formatDate(promo.end_date!)}`"
        >
          <span class="promotion-label">{{ promo.name }} ({{ promo.discount_percent }}%)</span>
        </div>
      </div>
    </div>

    <!-- Легенда (если акций нет) -->
    <div v-if="timedPromotions.length === 0" class="timeline-empty">
      Нет активных акций с датами
    </div>
  </div>
</template>
