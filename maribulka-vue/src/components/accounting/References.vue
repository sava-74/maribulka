<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useReferencesStore } from '../../stores/references'
import ClientsTable from './ClientsTable.vue'
import '../../assets/buttons.css'
import '../../assets/tables.css'
import '../../assets/layout.css'

const referencesStore = useReferencesStore()
const activeSection = ref('clients')

onMounted(() => {
  referencesStore.fetchShootingTypes()
  referencesStore.fetchPromotions()
  referencesStore.fetchClients()
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
        <h3 class="section-header">📸 Типы съёмок</h3>
        <table class="accounting-table">
          <thead>
            <tr>
              <th>Название</th>
              <th>Базовая цена</th>
              <th>Описание</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="type in referencesStore.shootingTypes" :key="type.id">
              <td>{{ type.name }}</td>
              <td>{{ type.base_price }} ₽</td>
              <td>{{ type.description }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Акции -->
      <div v-if="activeSection === 'promotions'">
        <h3 class="section-header">🎁 Акции</h3>
        <table class="accounting-table">
          <thead>
            <tr>
              <th>Название</th>
              <th>Скидка</th>
              <th>Период</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="promo in referencesStore.promotions" :key="promo.id">
              <td>{{ promo.name }}</td>
              <td>{{ promo.discount_percent }}%</td>
              <td>
                {{ promo.start_date || '∞' }} - {{ promo.end_date || '∞' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
