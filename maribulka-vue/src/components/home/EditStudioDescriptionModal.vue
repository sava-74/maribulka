<script setup lang="ts">
import { ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCancel, mdilCheck } from '@mdi/light-js'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { useHomeStore } from '../../stores/home'
import AlertModal from '../AlertModal.vue'

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
const alertType = ref<'success' | 'error'>('success')

// Настройки редактора Quill
const editorOptions = {
  modules: {
    toolbar: [
      [{ header: [1, 2, 3, false] }],
      ['bold', 'italic', 'underline', 'strike'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      [{ align: [] }],
      ['link'],
      ['clean']
    ]
  },
  placeholder: 'Введите описание студии...',
  theme: 'snow'
}

// Загружаем текущее описание при открытии модалки
watch(() => props.isVisible, (visible) => {
  if (visible) {
    content.value = homeStore.description
  }
})

async function handleSave() {
  if (!content.value.trim()) {
    alertType.value = 'error'
    alertMessage.value = 'Описание не может быть пустым'
    showAlert.value = true
    return
  }

  const result = await homeStore.updateDescription(content.value)

  if (result.success) {
    alertType.value = 'success'
    alertMessage.value = 'Описание успешно обновлено'
    showAlert.value = true
    setTimeout(() => {
      emit('close')
    }, 1500)
  } else {
    alertType.value = 'error'
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
            v-model:content="content"
            content-type="html"
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
      :type="alertType"
      @close="showAlert = false"
    />
  </div>
</template>
