<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useReferencesStore } from '../../stores/references'
import ClientsTable from './ClientsTable.vue'
import ShootingTypesTable from './ShootingTypesTable.vue'
import PromotionsTable from './PromotionsTable.vue'
import ExpenseCategoriesTable from './ExpenseCategoriesTable.vue'
import '../../assets/buttons.css'
import '../../assets/tables.css'
import '../../assets/layout.css'
import '../../assets/panel.css'

const referencesStore = useReferencesStore()
const activeSection = ref('clients')

onMounted(() => {
  referencesStore.fetchShootingTypes()
  referencesStore.fetchPromotions()
  referencesStore.fetchClients()
  referencesStore.fetchExpenseCategories()
})
</script>

<template>
  <div class="accounting">
    <!-- Навигация (вкладки) -->
    <div class="panel panel-toolbar">
      <button
        class="glass-button-text"
        :class="{ active: activeSection === 'clients' }"
        @click="activeSection = 'clients'"
      >
        Клиенты
      </button>
      <button
        class="glass-button-text"
        :class="{ active: activeSection === 'shooting-types' }"
        @click="activeSection = 'shooting-types'"
      >
        Типы съёмок
      </button>
      <button
        class="glass-button-text"
        :class="{ active: activeSection === 'promotions' }"
        @click="activeSection = 'promotions'"
      >
        Акции
      </button>
      <button
        class="glass-button-text"
        :class="{ active: activeSection === 'expense-categories' }"
        @click="activeSection = 'expense-categories'"
      >
        Категории расходов
      </button>
    </div>

    <!-- Контент вкладок -->
    <div class="panel">
      <!-- Клиенты --> 
      <div v-if="activeSection === 'clients'">
        <ClientsTable />
      </div>

      <!-- Типы съёмок -->
      <div v-if="activeSection === 'shooting-types'">
        <ShootingTypesTable />
      </div>

      <!-- Акции -->
      <div v-if="activeSection === 'promotions'">
        <PromotionsTable />
      </div>

      <!-- Категории расходов -->
      <div v-if="activeSection === 'expense-categories'">
        <ExpenseCategoriesTable />
      </div>
    </div>
  </div>
</template>
