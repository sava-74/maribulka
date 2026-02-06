<script setup lang="ts">
import { onMounted } from 'vue'
import { useFinanceStore } from '../../stores/finance'

const financeStore = useFinanceStore()

onMounted(() => {
  financeStore.fetchIncome()
})
</script>

<template>
  <div class="income-table">
    <div class="header">
      <h2>💰 Приход</h2>
      <div class="summary">
        <span>Всего за месяц: <strong>{{ financeStore.totalIncome.toFixed(2) }} ₽</strong></span>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Дата</th>
          <th>Клиент</th>
          <th>Телефон</th>
          <th>Тип съёмки</th>
          <th>Сумма</th>
          <th>Категория</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in financeStore.income" :key="item.id">
          <td>{{ item.date }}</td>
          <td>{{ item.client_name }}</td>
          <td>{{ item.phone }}</td>
          <td>{{ item.shooting_type_name }}</td>
          <td class="amount">{{ parseFloat(item.amount).toFixed(2) }} ₽</td>
          <td>
            <span class="badge" :class="'badge-' + item.category">
              {{ item.category === 'advance' ? 'Аванс' : item.category === 'balance' ? 'Доплата' : 'Полная оплата' }}
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.income-table {
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
  color: #4ade80;
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
  color: #4ade80;
}

.badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.badge-advance {
  background: rgba(251, 191, 36, 0.2);
  color: #fbbf24;
}

.badge-balance {
  background: rgba(59, 130, 246, 0.2);
  color: #3b82f6;
}

.badge-full_payment {
  background: rgba(74, 222, 128, 0.2);
  color: #4ade80;
}

tbody tr:hover {
  background: rgba(255, 255, 255, 0.05);
}
</style>
