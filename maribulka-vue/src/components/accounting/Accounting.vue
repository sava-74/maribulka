<script setup lang="ts">
import { ref } from 'vue'
import BookingsFullCalendar from './BookingsFullCalendar.vue'
import BookingsCalendar from './BookingsCalendar.vue'
import IncomeTable from './IncomeTable.vue'
import ExpensesTable from './ExpensesTable.vue'
import Reports from './Reports.vue'

// Текущая активная вкладка
const activeTab = ref('bookings')

// Показать/скрыть таблицу записей
const showTable = ref(false)

// Переключение вкладок
function switchTab(tab: string) {
  activeTab.value = tab
}
</script>

<template>
  <div class="glass-panel-tabs glass-panel-tabs-sticky">
      <!-- Навигация вкладок -->
      <div class="glass-panel-tabs-nav">
        <button
          class="glass-button-tab"
          :class="{ active: activeTab === 'bookings' }"
          @click="switchTab('bookings')"
        >
          Запись
        </button>
        <button
          class="glass-button-tab"
          :class="{ active: activeTab === 'income' }"
          @click="switchTab('income')"
        >
          Приход
        </button>
        <button
          class="glass-button-tab"
          :class="{ active: activeTab === 'expenses' }"
          @click="switchTab('expenses')"
        >
          Расход
        </button>
        <button
          class="glass-button-tab"
          :class="{ active: activeTab === 'reports' }"
          @click="switchTab('reports')"
        >
          Отчёты
        </button>
      </div>

      <!-- Панель контента -->
      <div class="glass-panel-tabs-content">
        <template v-if="activeTab === 'bookings'">
          <BookingsFullCalendar :showTable="showTable" @toggle-table="showTable = !showTable" />
          <div v-if="showTable" style="margin-top: 20px;">
            <BookingsCalendar />
          </div>
        </template>
        <IncomeTable v-if="activeTab === 'income'" />
        <ExpensesTable v-if="activeTab === 'expenses'" />
        <Reports v-if="activeTab === 'reports'" />
      </div>
  </div>
</template>
