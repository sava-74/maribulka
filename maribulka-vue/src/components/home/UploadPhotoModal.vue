<script setup lang="ts">
import { ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline, mdiDeleteCircleOutline, mdiCameraPlusOutline } from '@mdi/js'
import { useHomeStore } from '../../stores/home'
import AlertModal from '../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  position: number
}>()

const emit = defineEmits(['close'])

const homeStore = useHomeStore()

const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const uploading = ref(false)
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Обработчик выбора файла
function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  if (!file) return

  // Проверка типа файла
  if (!file.type.startsWith('image/')) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Выберите изображение (JPG, PNG, WEBP)'
    showAlert.value = true
    return
  }

  // Проверка размера (макс 5MB)
  if (file.size > 5 * 1024 * 1024) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Размер файла не должен превышать 5 МБ'
    showAlert.value = true
    return
  }

  selectedFile.value = file

  // Создаём preview
  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrl.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

// Загрузка фото
async function handleUpload() {
  if (!selectedFile.value) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Выберите файл для загрузки'
    showAlert.value = true
    return
  }

  uploading.value = true

  const result = await homeStore.uploadPhoto(selectedFile.value, props.position)

  uploading.value = false

  if (result.success) {
    emit('close')
    resetForm()
  } else {
    alertTitle.value = 'Ошибка загрузки'
    alertMessage.value = result.message || 'Не удалось загрузить фото'
    showAlert.value = true
  }
}

// Сброс формы
function resetForm() {
  selectedFile.value = null
  previewUrl.value = null
}

// Удаление фото
async function handleDelete() {
  uploading.value = true

  const result = await homeStore.deletePhoto(props.position)

  uploading.value = false

  if (result.success) {
    emit('close')
    resetForm()
  } else {
    alertTitle.value = 'Ошибка удаления'
    alertMessage.value = result.message || 'Не удалось удалить фото'
    showAlert.value = true
  }
}

// Закрытие модалки
function handleClose() {
  resetForm()
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click="handleClose">
      <div class="modal-glass" @click.stop>
        <div class="modal-glassTitle">Загрузить фото на позицию {{ position + 1 }}</div>

        <div class="modal-content">
          <!-- Превью фото -->
          <div v-if="previewUrl" class="photo-preview">
            <img :src="previewUrl" alt="Preview">
          </div>

          <!-- Input для выбора файла -->
          <div class="file-input-wrapper">
            <input
              type="file"
              id="photo-input"
              accept="image/jpeg,image/png,image/webp"
              @change="handleFileSelect"
              class="file-input"
            >
            <label for="photo-input" class="glass-button-text">
              <svg-icon type="mdi" :path="mdiCameraPlusOutline" />
              <span>Выбрать файл</span>
            </label>
          </div>
        </div>

        <div class="modal-actions">
          <button
            class="glass-button"
            @click="handleUpload"
            :disabled="!selectedFile || uploading"
            title="Загрузить"
          >
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
          </button>
          <button
            class="glass-button"
            @click="handleDelete"
            :disabled="uploading"
            title="Удалить фото"
          >
            <svg-icon type="mdi" :path="mdiDeleteCircleOutline" />
          </button>
          <button
            class="glass-button"
            @click="handleClose"
            title="Отмена"
          >
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" />
          </button>
        </div>
      </div>
    </div>

    <AlertModal
      :is-visible="showAlert"
      :title="alertTitle"
      :message="alertMessage"
      @close="showAlert = false"
    />
  </Teleport>
</template>
