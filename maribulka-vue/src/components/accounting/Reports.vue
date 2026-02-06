<script setup lang="ts">
import { onMounted } from 'vue'
import { useFinanceStore } from '../../stores/finance'

const financeStore = useFinanceStore()

onMounted(() => {
  financeStore.fetchIncome()
  financeStore.fetchExpenses()
})
</script>

<template>
  <div class="reports">
    <h2>📊 Отчёты и аналитика</h2>

    <div class="stats-grid">
      <div class="stat-card income">
        <div class="icon">💰</div>
        <div class="content">
          <h3>Доход</h3>
          <p class="value">{{ financeStore.totalIncome.toFixed(2) }} ₽</p>
        </div>
      </div>

      <div class="stat-card expenses">
        <div class="icon">💸</div>
        <div class="content">
          <h3>Расход</h3>
          <p class="value">{{ financeStore.totalExpenses.toFixed(2) }} ₽</p>
        </div>
      </div>

      <div class="stat-card profit">
        <div class="icon">💵</div>
        <div class="content">
          <h3>Прибыль</h3>
          <p class="value" :class="{ negative: financeStore.profit < 0 }">
            {{ financeStore.profit.toFixed(2) }} ₽
          </p>
        </div>
      </div>

      <div class="stat-card profitability">
        <div class="icon">📈</div>
        <div class="content">
          <h3>Рентабельность</h3>
          <p class="value">{{ financeStore.profitability }}%</p>
        </div>
      </div>
    </div>

    <div class="info">
      <p>📅 Данные за текущий месяц: {{ financeStore.currentMonth }}</p>
      <p>🔔 Полная аналитика с графиками будет добавлена позже</p>
    </div>
  </div>
</template>

<style scoped>
.reports h2 {
  margin: 0 0 30px 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 24px;
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-4px);
}

.stat-card .icon {
  font-size: 48px;
}

.stat-card .content h3 {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.7);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-card .value {
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  color: #fff;
}

.stat-card.income .value {
  color: #4ade80;
}

.stat-card.expenses .value {
  color: #f87171;
}

.stat-card.profit .value {
  color: #60a5fa;
}

.stat-card.profit .value.negative {
  color: #f87171;
}

.stat-card.profitability .value {
  color: #fbbf24;
}

.info {
  padding: 20px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
}

.info p {
  margin: 8px 0;
  color: rgba(255, 255, 255, 0.7);
}
</style>
