<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiTextBoxPlusOutline, mdiCameraPlusOutline } from '@mdi/js'
import { useAuthStore } from '../stores/auth'
import { useHomeStore } from '../stores/home'
import UploadPhotoModal from '../components/home/UploadPhotoModal.vue'
import EditStudioDescriptionModal from '../components/home/EditStudioDescriptionModal.vue'


const authStore = useAuthStore()
const homeStore = useHomeStore()

const showUploadModal = ref(false)
const showEditDescriptionModal = ref(false)
const selectedPosition = ref(0)

// Загружаем данные при монтировании
onMounted(() => {
  homeStore.fetchPhotos()
  homeStore.fetchDescription()
})



// Модифицируем HTML описания: добавляем target="_blank" ко всем ссылкам
const descriptionHtml = computed(() => {
  const html = homeStore.description
  // Заменяем все <a> теги, добавляя target="_blank" и rel="noopener noreferrer"
  return html.replace(/<a /g, '<a target="_blank" rel="noopener noreferrer" ')
})

// Обработчик добавления фото
function handleAddPhoto(position: number) {
  selectedPosition.value = position
  showUploadModal.value = true
}

// Обработчик кликов по ссылкам в описании
function handleDescriptionClick(event: MouseEvent) {
  let target = event.target as HTMLElement

  // Ищем ссылку (может быть обёрнута в span или другие элементы)
  while (target && target.tagName !== 'A' && target !== event.currentTarget) {
    target = target.parentElement as HTMLElement
  }

  // Если нашли ссылку
  if (target && target.tagName === 'A') {
    const link = target as HTMLAnchorElement

    // Для ЛЮБОЙ ссылки останавливаем обработку и открываем в новой вкладке
    event.preventDefault()
    event.stopPropagation()

    if (link.href) {
      window.open(link.href, '_blank', 'noopener,noreferrer')
    }
  }
}
</script>

<template>
  <div class="home-container">

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
            class="buttonGL photo-add-button"
            @click="handleAddPhoto(index - 1)"
            title="Добавить/изменить фото"
          >
            <svg-icon type="mdi" :path="mdiCameraPlusOutline" />
          </button>
        </div>
      </div>
    </div>

    <!-- Блок описания студии -->
    <div class="studio-description">
      <div class="description-header">
        <button
          v-if="authStore.isAdmin"
          class="buttonGL"
          @click="showEditDescriptionModal = true"
          title="Редактировать описание"
        >
          <svg-icon type="mdi" :path="mdiTextBoxPlusOutline" />
        </button>
      </div>
      <div class="description-content" v-html="descriptionHtml" @click="handleDescriptionClick"></div>
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
