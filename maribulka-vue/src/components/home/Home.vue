<script setup lang="ts">
// @ts-nocheck
import { ref, computed, onMounted, toRef } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiTextBoxPlusOutline, mdiCameraPlusOutline } from '@mdi/js'
import { useAuthStore } from '../../stores/auth'
import { useHomeStore } from '../../stores/home'
import { useStackFade } from '../../composables/useStackFade'
import UploadPhotoModal from '../home/UploadPhotoModal.vue'
import EditStudioDescriptionModal from '../home/EditStudioDescriptionModal.vue'

const authStore = useAuthStore()
const homeStore = useHomeStore()

const showUploadModal = ref(false)
const showEditDescriptionModal = ref(false)
const selectedPosition = ref(0)

// Внутренний scroll-контейнер
const scrollAreaRef = ref<HTMLElement | null>(null)

// Refs на DOM-элементы панелей
const panel1 = ref<HTMLElement | null>(null)
const panel2 = ref<HTMLElement | null>(null)
const panel3 = ref<HTMLElement | null>(null)
const panel4 = ref<HTMLElement | null>(null)
const panelsRef = computed(() =>
  [panel1.value, panel2.value, panel3.value, panel4.value].filter(Boolean) as HTMLElement[]
)

useStackFade(scrollAreaRef, panelsRef)

onMounted(() => {
  homeStore.fetchPhotos()
  homeStore.fetchDescription()
})

const descriptionHtml = computed(() => {
  const html = homeStore.description
  return html.replace(/<a /g, '<a target="_blank" rel="noopener noreferrer" ')
})

function handleAddPhoto(position: number) {
  selectedPosition.value = position
  showUploadModal.value = true
}

function handleDescriptionClick(event: MouseEvent) {
  let target = event.target as HTMLElement
  while (target && target.tagName !== 'A' && target !== event.currentTarget) {
    target = target.parentElement as HTMLElement
  }
  if (target && target.tagName === 'A') {
    const link = target as HTMLAnchorElement
    event.preventDefault()
    event.stopPropagation()
    if (link.href) {
      window.open(link.href, '_blank', 'noopener,noreferrer')
    }
  }
}
</script>

<template>
  <div ref="scrollAreaRef" class="home-scroll-area">
    <div class="home-stack">

      <!-- Панель 1 -->
      <div ref="panel1" class="home-panel">
        <div class="home-panel-glass"></div>
        <div class="home-panel-content">
          Панель 1
        </div>
      </div>

      <!-- Панель 2 -->
      <div ref="panel2" class="home-panel">
        <div class="home-panel-glass"></div>
        <div class="home-panel-content">
          Панель 2
        </div>
      </div>

      <!-- Панель 3 -->
      <div ref="panel3" class="home-panel">
        <div class="home-panel-glass"></div>
        <div class="home-panel-content">
          Панель 3
        </div>
      </div>

      <!-- Панель 4 -->
      <div ref="panel4" class="home-panel">
        <div class="home-panel-glass"></div>
        <div class="home-panel-content">
          Панель 4
        </div>
      </div>

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
