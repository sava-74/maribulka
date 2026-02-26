<script setup lang="ts">
import { ref } from 'vue'

const activeTab = ref('portrait')
</script>

<template>
  <div class="glass-panel-tabs glass-panel-tabs-sticky">
      <!-- Навигация вкладок -->
      <div class="glass-panel-tabs-nav">
        <button
          class="buttonGL-tab"
          :class="{ active: activeTab === 'portrait' }"
          @click="activeTab = 'portrait'"
        >
          Портрет
        </button>
        <button
          class="buttonGL-tab"
          :class="{ active: activeTab === 'group' }"
          @click="activeTab = 'group'"
        >
          Групповая
        </button>
        <button
          class="buttonGL-tab"
          :class="{ active: activeTab === 'family' }"
          @click="activeTab = 'family'"
        >
          Семейная
        </button>
      </div>

      <!-- Панель контента -->
      <div class="glass-panel-tabs-content">
        <div v-if="activeTab === 'portrait'" style="padding: 20px; text-align: center;">
          <h2>Портретная съёмка</h2>
          <p style="margin-top: 10px; color: #666;">Раздел в разработке...</p>
        </div>
        <div v-if="activeTab === 'group'" style="padding: 20px; text-align: center;">
          <h2>Групповая съёмка</h2>
          <p style="margin-top: 10px; color: #666;">Раздел в разработке...</p>
        </div>
        <div v-if="activeTab === 'family'" style="padding: 20px; text-align: center;">
          <h2>Семейная съёмка</h2>
          <p style="margin-top: 10px; color: #666;">Раздел в разработке...</p>
        </div>
      </div>
  </div>
</template>
