<script setup lang="ts">
import { ref, computed } from 'vue'
import BookingsFullCalendar from './BookingsFullCalendar.vue'
import BookingsCalendar from './BookingsCalendar.vue'
import IncomeTable from './IncomeTable.vue'
import ExpensesTable from './ExpensesTable.vue'
import Reports from './Reports.vue'

// Текущая активная вкладка
const activeTab = ref('bookings')

// Показать/скрыть таблицу записей
const showTable = ref(false)

// Период фильтрации (по умолчанию текущий месяц)
const periodStart = ref<Date>(new Date(new Date().getFullYear(), new Date().getMonth(), 1))
const periodEnd = ref<Date>(new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0))

// Форматирование периода для кнопки
const periodText = computed(() => {
  const formatDate = (date: Date) => {
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const year = String(date.getFullYear()).slice(-2)
    return `${day}.${month}.${year}`
  }
  return `с ${formatDate(periodStart.value)} по ${formatDate(periodEnd.value)}`
})

// Переключение вкладок
function switchTab(tab: string) {
  activeTab.value = tab
}

// Открыть модалку выбора периода (пока просто заглушка)
function openPeriodModal() {
  console.log('Открыть модалку выбора периода')
}
</script>

<template>
  <div class="glass-panel-tabs glass-panel-tabs-sticky">
      <!-- Навигация вкладок -->
      <div class="glass-panel-tabs-nav">
        <!-- Левая группа: вкладки -->
        <div class="tabs-group-left">
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

        <!-- Правая группа: фильтр периода -->
        <div class="tabs-group-right">
          <button
            class="glass-button-tab active period-filter-button"
            @click="openPeriodModal"
          >
            {{ periodText }}
          </button>
        </div>
      </div>

      <!-- Панель контента -->
      <div class="glass-panel-tabs-content">
        <template v-if="activeTab === 'bookings'">
          <BookingsFullCalendar
            :showTable="showTable"
            :period-start="periodStart"
            :period-end="periodEnd"
            @toggle-table="showTable = !showTable"
          />
          <div v-if="showTable" style="margin-top: 20px;">
            <BookingsCalendar
              :period-start="periodStart"
              :period-end="periodEnd"
            />
          </div>
        </template>
        <IncomeTable
          v-if="activeTab === 'income'"
          :period-start="periodStart"
          :period-end="periodEnd"
        />
        <ExpensesTable
          v-if="activeTab === 'expenses'"
          :period-start="periodStart"
          :period-end="periodEnd"
        />
        <Reports
          v-if="activeTab === 'reports'"
          :period-start="periodStart"
          :period-end="periodEnd"
        />
      </div>
  </div>
</template>
