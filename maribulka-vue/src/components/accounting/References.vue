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
    <div class="accounting-nav">
      <nav class="tabs">
        <button
          class="glass-button-text"
          @click="activeSection = 'clients'"
          :class="{ active: activeSection === 'clients' }"
        >
          Клиенты
        </button>
        <button
          class="glass-button-text"
          @click="activeSection = 'shooting-types'"
          :class="{ active: activeSection === 'shooting-types' }"
        >
          Типы съёмок
        </button>
        <button
          class="glass-button-text"
          @click="activeSection = 'promotions'"
          :class="{ active: activeSection === 'promotions' }"
        >
          Акции
        </button>
        <button
          class="glass-button-text"
          @click="activeSection = 'expense-categories'"
          :class="{ active: activeSection === 'expense-categories' }"
        >
          Категории расходов
        </button>
      </nav>
    </div>

    <!-- Контент вкладок -->
    <div class="tab-content">
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
