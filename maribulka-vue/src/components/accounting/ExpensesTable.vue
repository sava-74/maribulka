<script setup lang="ts">
import { onMounted } from 'vue'
import { useFinanceStore } from '../../stores/finance'
import '../../assets/tables.css'
import '../../assets/buttons.css'
import '../../assets/layout.css'
import '../../assets/responsive.css'

const financeStore = useFinanceStore()

onMounted(() => {
  financeStore.fetchExpenses()
})
</script>

<template>
  <div class="expenses-table table-general">
    <div class="header-with-action">
      <div>
        <h2 class="section-header">💸 Расход</h2>
        <div class="total-display">
          <span>Всего за месяц: <strong class="total-amount-expense">{{ financeStore.totalExpenses.toFixed(2) }} ₽</strong></span>
        </div>
      </div>
      <button class="glass-button">
        ➕ Добавить расход
      </button>
    </div>

    <div v-if="financeStore.expenses.length > 0" class="table-scroll-container">
      <table class="accounting-table">
        <thead>
          <tr>
            <th>Дата</th>
            <th>Сумма</th>
            <th>Категория</th>
            <th>Описание</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in financeStore.expenses" :key="item.id">
            <td>{{ item.date }}</td>
            <td class="amount-expense">{{ parseFloat(item.amount).toFixed(2) }} ₽</td>
            <td>{{ item.category }}</td>
            <td>{{ item.description }}</td>
            <td class="action-buttons">
              <button class="glass-button" title="Редактировать">✏️</button>
              <button class="glass-button" title="Удалить">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="empty-state">
      <p>📭 Нет расходов за текущий месяц</p>
      <button class="glass-button">➕ Добавить первый расход</button>
    </div>
  </div>
</template>
