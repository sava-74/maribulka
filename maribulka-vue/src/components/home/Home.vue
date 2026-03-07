<script setup lang="ts">
// @ts-nocheck
import { ref, computed, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiTextBoxPlusOutline, mdiCameraPlusOutline } from '@mdi/js'
import { useAuthStore } from '../../stores/auth'
import { useHomeStore } from '../../stores/home'
import UploadPhotoModal from '../home/UploadPhotoModal.vue'
import EditStudioDescriptionModal from '../home/EditStudioDescriptionModal.vue'


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
  <div>

    <!-- Панель 1 -->
    <div class="padGlass padGlass-work">
      Панель 1
    </div>

    <!-- Панель 2 -->
    <div class="padGlass padGlass-work">
      Панель 2
    </div>

    <!-- Панель 3 -->
    <div class="padGlass padGlass-work">
      Панель 3
    </div>

    <!-- Панель 4 -->
    <div class="padGlass padGlass-work">
      Панель 4
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
