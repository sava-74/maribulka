<script setup lang="ts">
import { onMounted } from 'vue'
import { useFinanceStore } from '../../stores/finance'

const financeStore = useFinanceStore()

onMounted(() => {
  financeStore.fetchExpenses()
})
</script>

<template>
  <div class="expenses-table">
    <div class="header">
      <h2>💸 Расход</h2>
      <div class="summary">
        <span>Всего за месяц: <strong>{{ financeStore.totalExpenses.toFixed(2) }} ₽</strong></span>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Дата</th>
          <th>Сумма</th>
          <th>Категория</th>
          <th>Описание</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in financeStore.expenses" :key="item.id">
          <td>{{ item.date }}</td>
          <td class="amount">{{ parseFloat(item.amount).toFixed(2) }} ₽</td>
          <td>{{ item.category }}</td>
          <td>{{ item.description }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.expenses-table {
  width: 100%;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h2 {
  margin: 0;
}

.summary {
  font-size: 18px;
  color: rgba(255, 255, 255, 0.9);
}

.summary strong {
  color: #f87171;
  font-size: 24px;
}

table {
  width: 100%;
  border-collapse: collapse;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  overflow: hidden;
}

thead {
  background: rgba(255, 255, 255, 0.1);
}

th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

th {
  font-weight: 600;
  color: #fff;
}

td {
  color: rgba(255, 255, 255, 0.9);
}

.amount {
  font-weight: 600;
  color: #f87171;
}

tbody tr:hover {
  background: rgba(255, 255, 255, 0.05);
}
</style>
