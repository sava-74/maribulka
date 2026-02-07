<script setup lang="ts">
import { onMounted } from 'vue'
import { useFinanceStore } from '../../stores/finance'
import '../../assets/reports.css'
import '../../assets/layout.css'

const financeStore = useFinanceStore()

onMounted(() => {
  financeStore.fetchIncome()
  financeStore.fetchExpenses()
})
</script>

<template>
  <div class="reports">
    <h2 class="page-header-large">📊 Отчёты и аналитика</h2>

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
