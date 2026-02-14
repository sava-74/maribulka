<script setup lang="ts">
import { ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCheck, mdilCancel } from '@mdi/light-js'
import { useReferencesStore } from '../../stores/references'
import AlertModal from '../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
}>()
const emit = defineEmits(['close'])

const referencesStore = useReferencesStore()

// Form data
const name = ref('')
const phone = ref('')
const notes = ref('')

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Сброс формы при открытии
watch(() => props.isVisible, (newValue) => {
  if (newValue) {
    name.value = ''
    phone.value = ''
    notes.value = ''
  }
})

const handleSubmit = async () => {
  // Валидация
  if (!name.value.trim()) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Укажите ФИО клиента'
    showAlert.value = true
    return
  }

  if (!phone.value.trim()) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Укажите телефон клиента'
    showAlert.value = true
    return
  }

  const result = await referencesStore.createClient({
    name: name.value.trim(),
    phone: phone.value.trim(),
    notes: notes.value.trim() || null
  })

  if (result.success) {
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось создать клиента'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
        <h2>Добавить клиента</h2>

        <div class="input-group">
          <div class="input-field">
            <label class="input-label">ФИО: <span class="required">*</span></label>
            <input
              type="text"
              class="modal-input"
              v-model="name"
              placeholder="Иванов Иван Иванович"
              maxlength="100"
            />
          </div>

          <div class="input-field">
            <label class="input-label">Телефон: <span class="required">*</span></label>
            <input
              type="tel"
              class="modal-input"
              v-model="phone"
              placeholder="+7 (999) 123-45-67"
              maxlength="20"
            />
          </div>

          <div class="input-field">
            <label class="input-label">Примечание:</label>
            <textarea
              class="modal-input"
              v-model="notes"
              placeholder="Заметки о клиенте"
              rows="3"
            ></textarea>
          </div>
        </div>

        <div class="modal-actions">
          <button class="glass-button" @click="emit('close')">
            <svg-icon type="mdi" :path="mdilCancel" />
          </button>
          <button class="glass-button" @click="handleSubmit">
            <svg-icon type="mdi" :path="mdilCheck" />
          </button>
        </div>
      </div>
    </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>
