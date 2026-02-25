<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useReferencesStore } from '../../stores/references'
import ClientsTable from './ClientsTable.vue'
import ShootingTypesTable from './ShootingTypesTable.vue'
import PromotionsTable from './PromotionsTable.vue'
import ExpenseCategoriesTable from './ExpenseCategoriesTable.vue'

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
  <div class="glass-panel-tabs glass-panel-tabs-sticky">
      <!-- Навигация вкладок -->
      <div class="glass-panel-tabs-nav">
        <div class="tabs-group-left">
          <button
            class="glass-button-tab"
            :class="{ active: activeSection === 'clients' }"
            @click="activeSection = 'clients'"
          >
            Клиенты
          </button>
          <button
            class="glass-button-tab"
            :class="{ active: activeSection === 'shooting-types' }"
            @click="activeSection = 'shooting-types'"
          >
            Типы съёмок
          </button>
          <button
            class="glass-button-tab"
            :class="{ active: activeSection === 'promotions' }"
            @click="activeSection = 'promotions'"
          >
            Акции
          </button>
          <button
            class="glass-button-tab"
            :class="{ active: activeSection === 'expense-categories' }"
            @click="activeSection = 'expense-categories'"
          >
            Категории расходов
          </button>
        </div>
      </div>

      <!-- Панель контента -->
      <div class="glass-panel-tabs-content">
        <div v-if="activeSection === 'clients'">
          <ClientsTable />
        </div>
        <div v-if="activeSection === 'shooting-types'">
          <ShootingTypesTable />
        </div>
        <div v-if="activeSection === 'promotions'">
          <PromotionsTable />
        </div>
        <div v-if="activeSection === 'expense-categories'">
          <ExpenseCategoriesTable />
        </div>
      </div>
  </div>
</template>
