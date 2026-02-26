<script setup lang="ts">
import { ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useReferencesStore } from '../../stores/references'
import AlertModal from '../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  category: any | null
}>()
const emit = defineEmits(['close'])

const referencesStore = useReferencesStore()

// Form data
const id = ref(0)
const name = ref('')
const is_active = ref(true)

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Заполнение формы при открытии
watch(() => props.isVisible, (newValue) => {
  if (newValue && props.category) {
    id.value = props.category.id
    name.value = props.category.name || ''
    is_active.value = props.category.is_active !== undefined ? props.category.is_active : true
  }
})

const handleSubmit = async () => {
  // Валидация
  if (!name.value.trim()) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Укажите название категории'
    showAlert.value = true
    return
  }

  const result = await referencesStore.updateExpenseCategory({
    id: id.value,
    name: name.value.trim(),
    is_active: is_active.value
  })

  if (result.success) {
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось обновить категорию'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
        <div class="modal-glassTitle">Редактировать категорию</div>

        <div class="input-group">
          <div class="input-field">
            <label class="input-label">Название: <span class="required">*</span></label>
            <input
              type="text"
              class="modal-input"
              v-model="name"
              placeholder="Название категории"
              maxlength="100"
            />
          </div>

          <div class="input-field">
            <label class="checkbox-label">
              <input
                type="checkbox"
                v-model="is_active"
              />
              <span>Активна</span>
            </label>
          </div>
        </div>

        <div class="modal-actions">
          <button class="buttonGL" @click="emit('close')">
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" />
          </button>
          <button class="buttonGL" @click="handleSubmit">
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
          </button>
        </div>
      </div>
    </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>
