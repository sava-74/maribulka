<script setup lang="ts">
import { onMounted } from 'vue'
import { useFinanceStore } from '../../stores/finance'
import '../../assets/tables.css'
import '../../assets/layout.css'
import '../../assets/responsive.css'

const financeStore = useFinanceStore()

onMounted(() => {
  financeStore.fetchIncome()
})
</script>

<template>
  <div class="income-table">
    <div class="header-with-action-center">
      <h2 class="page-header">💰 Приход</h2>
      <div class="total-display">
        <span>Всего за месяц: <strong class="total-amount-income">{{ financeStore.totalIncome.toFixed(2) }} ₽</strong></span>
      </div>
    </div>

    <table v-if="financeStore.income.length > 0" class="accounting-table">
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
          <td class="amount-income">{{ parseFloat(item.amount).toFixed(2) }} ₽</td>
          <td>
            <span class="badge" :class="'badge-' + item.category">
              {{ item.category === 'advance' ? 'Аванс' : item.category === 'balance' ? 'Доплата' : 'Полная оплата' }}
            </span>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-else class="empty-state">
      <p>📭 Нет поступлений за текущий месяц</p>
    </div>
  </div>
</template>
