<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilPlus } from '@mdi/light-js'
import { useReferencesStore } from '../stores/references'
import { useAuthStore } from '../stores/auth'
import { useHomeStore } from '../stores/home'
import UploadPhotoModal from '../components/home/UploadPhotoModal.vue'
import EditStudioDescriptionModal from '../components/home/EditStudioDescriptionModal.vue'

const referencesStore = useReferencesStore()
const authStore = useAuthStore()
const homeStore = useHomeStore()

const showUploadModal = ref(false)
const showEditDescriptionModal = ref(false)
const selectedPosition = ref(0)

// Загружаем данные при монтировании
onMounted(() => {
  referencesStore.fetchPromotions()
  homeStore.fetchPhotos()
  homeStore.fetchDescription()
})

// Находим действующую акцию (текущая дата в диапазоне start_date - end_date)
const activePromotion = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return referencesStore.promotions.find(p => {
    if (!p.start_date || !p.end_date) return false

    const [startYear, startMonth, startDay] = p.start_date.split('-').map(Number)
    const [endYear, endMonth, endDay] = p.end_date.split('-').map(Number)

    const startDate = new Date(startYear!, startMonth! - 1, startDay!)
    const endDate = new Date(endYear!, endMonth! - 1, endDay!)

    startDate.setHours(0, 0, 0, 0)
    endDate.setHours(0, 0, 0, 0)

    return today >= startDate && today <= endDate
  })
})

// Обработчик добавления фото
function handleAddPhoto(position: number) {
  selectedPosition.value = position
  showUploadModal.value = true
}
</script>

<template>
  <div class="home-container">
    <!-- Блок действующей акции -->
    <div v-if="activePromotion" class="promotion-banner">
      <h1 class="promotion-text">
        Акция "{{ activePromotion.name }}" {{ Math.round(activePromotion.discount_percent) }}%
      </h1>
    </div>

    <!-- Блок фото студии -->
    <div class="studio-photos">
      <div class="photos-grid">
        <div
          v-for="index in 4"
          :key="index"
          class="photo-item"
        >
          <img
            v-if="homeStore.photos[index - 1]"
            :src="homeStore.photos[index - 1]"
            :alt="`Студия фото ${index}`"
          >

          <!-- Кнопка + в каждом слоте (только для админа) -->
          <button
            v-if="authStore.isAdmin"
            class="glass-button photo-add-button"
            @click="handleAddPhoto(index - 1)"
            title="Добавить/изменить фото"
          >
            <svg-icon type="mdi" :path="mdilPlus" />
          </button>
        </div>
      </div>
    </div>

    <!-- Блок описания студии -->
    <div class="studio-description">
      <div class="description-header">
        <h2>О студии</h2>
        <button
          v-if="authStore.isAdmin"
          class="glass-button"
          @click="showEditDescriptionModal = true"
          title="Редактировать описание"
        >
          <svg-icon type="mdi" :path="mdilPlus" />
        </button>
      </div>
      <div class="description-content" v-html="homeStore.description"></div>
    </div>

    <!-- Модалка загрузки фото -->
    <UploadPhotoModal
      :is-visible="showUploadModal"
      :position="selectedPosition"
      @close="showUploadModal = false"
    />

    <!-- Модалка редактирования описания -->
    <EditStudioDescriptionModal
      :is-visible="showEditDescriptionModal"
      @close="showEditDescriptionModal = false"
    />
  </div>
</template>
