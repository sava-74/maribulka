<script setup lang="ts">
import { ref } from 'vue'
import BookingsFullCalendar from './BookingsFullCalendar.vue'
import BookingsCalendar from './BookingsCalendar.vue'
import IncomeTable from './IncomeTable.vue'
import ExpensesTable from './ExpensesTable.vue'
import Reports from './Reports.vue'
import '../../assets/buttons.css'
import '../../assets/layout.css'
import '../../assets/responsive.css'

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
  <div class="accounting">
    <!-- Навигация (вкладки) -->
    <nav class="tabs">
      <button
        class="glass-button-text"
        @click="switchTab('bookings')"
        :class="{ active: activeTab === 'bookings' }"
      >
        Запись
      </button>
      <button
        class="glass-button-text"
        @click="switchTab('income')"
        :class="{ active: activeTab === 'income' }"
      >
        Приход
      </button>
      <button
        class="glass-button-text"
        @click="switchTab('expenses')"
        :class="{ active: activeTab === 'expenses' }"
      >
        Расход
      </button>
      <button
        class="glass-button-text"
        @click="switchTab('reports')"
        :class="{ active: activeTab === 'reports' }"
      >
        Отчёты
      </button>
    </nav>

    <!-- Контент вкладок -->
    <div class="tab-content">
      <template v-if="activeTab === 'bookings'">
        <h2 class="section-header">Запись на съёмку</h2>
        <!-- Календарь class="calendar-container" -->
        
          <BookingsFullCalendar :showTable="showTable" @toggle-table="showTable = !showTable" />
        
        <!-- Таблица -->
        
          <div v-if="showTable" class="table-container" style="margin-top: 20px;">
            <BookingsCalendar />
          </div>
        
      </template>
      <IncomeTable v-if="activeTab === 'income'" />
      <ExpensesTable v-if="activeTab === 'expenses'" />
      <Reports v-if="activeTab === 'reports'" />
    </div>
  </div>
</template>
