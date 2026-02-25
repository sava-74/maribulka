<script setup lang="ts">
import { ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import RichTextEditor from '../RichTextEditor.vue'
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

// Загружаем текущее описание и блокируем скролл body
watch(() => props.isVisible, (visible) => {
  if (visible) {
    content.value = homeStore.description
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
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

  // Исправляем ссылки: добавляем https:// если нет протокола
  // (TipTap уже автоматически добавляет протокол, но для старых данных оставляем)
  let fixedContent = content.value
  fixedContent = fixedContent.replace(/<a\s+([^>]*?)href="([^"]+)"([^>]*)>/gi, (match, before, href, after) => {
    if (!/^(https?:\/\/|mailto:)/i.test(href)) {
      return `<a ${before}href="https://${href}"${after}>`
    }
    return match
  })

  const result = await homeStore.updateDescription(fixedContent)

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
        <div class="modal-glassTitle">Редактирование описания студии</div>
      </div>

      <div class="modal-body">
        <RichTextEditor
          v-model="content"
          placeholder="Введите описание студии..."
        />
      </div>

      <div class="modal-footer">
        <button class="glass-button-text" @click="handleClose">
          <svg-icon type="mdi" :path="mdiCloseCircleOutline" />
          <span>Отмена</span>
        </button>        
        <button class="glass-button-text" @click="handleSave">
          <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
          <span>Сохранить</span>
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
