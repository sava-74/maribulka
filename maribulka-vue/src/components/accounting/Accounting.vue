<script setup lang="ts">
import { ref } from 'vue'
import BookingsCalendar from './BookingsCalendar.vue'
import IncomeTable from './IncomeTable.vue'
import ExpensesTable from './ExpensesTable.vue'
import Reports from './Reports.vue'
import Settings from './Settings.vue'

// Текущая активная вкладка
const activeTab = ref('bookings')

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
        @click="switchTab('bookings')"
        :class="{ active: activeTab === 'bookings' }"
      >
        📅 Записи на съёмку
      </button>
      <button
        @click="switchTab('income')"
        :class="{ active: activeTab === 'income' }"
      >
        💰 Приход
      </button>
      <button
        @click="switchTab('expenses')"
        :class="{ active: activeTab === 'expenses' }"
      >
        💸 Расход
      </button>
      <button
        @click="switchTab('reports')"
        :class="{ active: activeTab === 'reports' }"
      >
        📊 Отчёты
      </button>
      <button
        @click="switchTab('settings')"
        :class="{ active: activeTab === 'settings' }"
      >
        ⚙️ Настройки
      </button>
    </nav>

    <!-- Контент вкладок -->
    <div class="tab-content">
      <BookingsCalendar v-if="activeTab === 'bookings'" />
      <IncomeTable v-if="activeTab === 'income'" />
      <ExpensesTable v-if="activeTab === 'expenses'" />
      <Reports v-if="activeTab === 'reports'" />
      <Settings v-if="activeTab === 'settings'" />
    </div>
  </div>
</template>

<style scoped>
.accounting {
  width: 100%;
  min-height: 100vh;
  padding: 20px;
}

.tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 30px;
  flex-wrap: wrap;
}

.tabs button {
  padding: 12px 24px;
  font-size: 16px;
  font-weight: 500;
  border: none;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #fff;
}

.tabs button:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.tabs button.active {
  background: rgba(255, 255, 255, 0.3);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.tab-content {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 30px;
  min-height: 500px;
}

.section h2 {
  margin: 0 0 20px 0;
  color: #fff;
}

.section p {
  color: rgba(255, 255, 255, 0.7);
  font-size: 18px;
}
</style>
