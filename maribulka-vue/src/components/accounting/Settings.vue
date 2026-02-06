<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useReferencesStore } from '../../stores/references'

const referencesStore = useReferencesStore()
const activeSection = ref('shooting-types')

onMounted(() => {
  referencesStore.fetchShootingTypes()
  referencesStore.fetchPromotions()
  referencesStore.fetchClients()
})
</script>

<template>
  <div class="settings">
    <h2>⚙️ Настройки и справочники</h2>

    <!-- Подвкладки -->
    <div class="sub-tabs">
      <button @click="activeSection = 'shooting-types'" :class="{ active: activeSection === 'shooting-types' }">
        📸 Типы съёмок
      </button>
      <button @click="activeSection = 'promotions'" :class="{ active: activeSection === 'promotions' }">
        🎁 Акции
      </button>
      <button @click="activeSection = 'clients'" :class="{ active: activeSection === 'clients' }">
        👥 Клиенты
      </button>
    </div>

    <!-- Типы съёмок -->
    <div v-if="activeSection === 'shooting-types'" class="table-section">
      <h3>📸 Типы съёмок</h3>
      <table>
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
    <div v-if="activeSection === 'promotions'" class="table-section">
      <h3>🎁 Акции</h3>
      <table>
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

    <!-- Клиенты -->
    <div v-if="activeSection === 'clients'" class="table-section">
      <h3>👥 Клиенты</h3>
      <table>
        <thead>
          <tr>
            <th>ФИО</th>
            <th>Телефон</th>
            <th>Съёмок</th>
            <th>Заметки</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="client in referencesStore.clients" :key="client.id">
            <td>{{ client.name }}</td>
            <td>{{ client.phone }}</td>
            <td>{{ client.total_bookings }}</td>
            <td>{{ client.notes }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.settings h2 {
  margin: 0 0 20px 0;
}

.sub-tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 30px;
}

.sub-tabs button {
  padding: 10px 20px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 8px;
  color: #fff;
  cursor: pointer;
  transition: all 0.2s;
}

.sub-tabs button:hover {
  background: rgba(255, 255, 255, 0.15);
}

.sub-tabs button.active {
  background: rgba(255, 255, 255, 0.25);
}

.table-section h3 {
  margin: 0 0 15px 0;
}

table {
  width: 100%;
  border-collapse: collapse;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  overflow: hidden;
}

thead {
  background: rgba(255, 255, 255, 0.1);
}

th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

th {
  font-weight: 600;
  color: #fff;
}

td {
  color: rgba(255, 255, 255, 0.9);
}

tbody tr:hover {
  background: rgba(255, 255, 255, 0.05);
}
</style>
