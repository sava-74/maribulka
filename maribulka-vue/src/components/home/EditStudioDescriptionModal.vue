<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCancel, mdilCheck } from '@mdi/light-js'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { useHomeStore } from '../../stores/home'
import AlertModal from '../AlertModal.vue'
import type Quill from 'quill'

interface Props {
  isVisible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
}>()

const homeStore = useHomeStore()

const content = ref<string>('')
const showAlert = ref(false)
const alertMessage = ref('')
const quillRef = ref<InstanceType<typeof QuillEditor> | null>(null)

// Настройки редактора Quill
const editorOptions = {
  modules: {
    toolbar: [
      [{ header: [1, 2, 3, false] }],
      ['bold', 'italic', 'underline', 'strike'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      [{ align: [] }],
      [{ color: [] }, { background: [] }],
      ['link', 'image'],
      ['clean']
    ]
  },
  placeholder: 'Введите описание студии...',
  theme: 'snow'
}

// Обработчик загрузки изображений
function setupImageHandler() {
  if (!quillRef.value) return

  const quill = quillRef.value.getQuill() as Quill
  const toolbar = quill.getModule('toolbar')

  toolbar.addHandler('image', () => {
    const input = document.createElement('input')
    input.setAttribute('type', 'file')
    input.setAttribute('accept', 'image/*')

    input.onchange = async () => {
      const file = input.files?.[0]
      if (!file) return

      // Создаём canvas для ресайза
      const img = new Image()
      const reader = new FileReader()

      reader.onload = (e) => {
        img.src = e.target?.result as string

        img.onload = () => {
          const canvas = document.createElement('canvas')
          const ctx = canvas.getContext('2d')
          if (!ctx) return

          // Ограничение: ширина 200px, высота 300px (сохраняем пропорции)
          let width = img.width
          let height = img.height
          const maxWidth = 200
          const maxHeight = 300

          if (width > maxWidth) {
            height = (height * maxWidth) / width
            width = maxWidth
          }

          if (height > maxHeight) {
            width = (width * maxHeight) / height
            height = maxHeight
          }

          canvas.width = width
          canvas.height = height
          ctx.drawImage(img, 0, 0, width, height)

          // Конвертируем в base64
          const resizedImage = canvas.toDataURL('image/jpeg', 0.9)

          // Вставляем в редактор
          const range = quill.getSelection()
          if (range) {
            quill.insertEmbed(range.index, 'image', resizedImage)
            quill.setSelection(range.index + 1, 0)
          }
        }
      }

      reader.readAsDataURL(file)
    }

    input.click()
  })
}

// Загружаем текущее описание и настраиваем обработчик изображений
watch(() => props.isVisible, (visible) => {
  if (visible) {
    content.value = homeStore.description
    // Даём время QuillEditor отрендериться
    setTimeout(() => {
      setupImageHandler()
    }, 100)
  }
})

async function handleSave() {
  // Проверяем что есть контент (убираем HTML теги для проверки)
  const textOnly = content.value.replace(/<[^>]*>/g, '').trim()
  if (!textOnly) {
    alertMessage.value = 'Описание не может быть пустым'
    showAlert.value = true
    return
  }

  const result = await homeStore.updateDescription(content.value)

  if (result.success) {
    alertMessage.value = 'Описание успешно обновлено'
    showAlert.value = true
    setTimeout(() => {
      showAlert.value = false
      emit('close')
    }, 1500)
  } else {
    alertMessage.value = result.message || 'Ошибка при сохранении'
    showAlert.value = true
  }
}

function handleClose() {
  emit('close')
}
</script>

<template>
  <div v-if="isVisible" class="modal-overlay" @click.self="handleClose">
    <div class="modal-glass modal-large">
      <div class="modal-header">
        <h2>Редактирование описания студии</h2>
        <button class="glass-button" @click="handleClose" title="Закрыть">
          <svg-icon type="mdi" :path="mdilCancel" />
        </button>
      </div>

      <div class="modal-body">
        <div class="editor-container">
          <QuillEditor
            ref="quillRef"
            v-model:content="content"
            contentType="html"
            :options="editorOptions"
          />
        </div>
      </div>

      <div class="modal-footer">
        <button class="glass-button-text" @click="handleSave">
          <svg-icon type="mdi" :path="mdilCheck" />
          <span>Сохранить</span>
        </button>
        <button class="glass-button-text" @click="handleClose">
          <svg-icon type="mdi" :path="mdilCancel" />
          <span>Отмена</span>
        </button>
      </div>
    </div>

    <AlertModal
      :is-visible="showAlert"
      :message="alertMessage"
      @close="showAlert = false"
    />
  </div>
</template>
