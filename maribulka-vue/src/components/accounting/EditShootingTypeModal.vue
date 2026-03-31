<script setup lang="ts">
import { ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useReferencesStore } from '../../stores/references'
import AlertModal from '../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  shootingType: any | null
}>()
const emit = defineEmits(['close'])

const referencesStore = useReferencesStore()

// Form data
const id = ref(0)
const name = ref('')
const basePrice = ref('')
const durationMinutes = ref('30')
const description = ref('')

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Заполнение формы при открытии
watch(() => props.isVisible, (newValue) => {
  if (newValue && props.shootingType) {
    id.value = props.shootingType.id
    name.value = props.shootingType.name || ''
    basePrice.value = props.shootingType.base_price || ''
    durationMinutes.value = props.shootingType.duration_minutes || '30'
    description.value = props.shootingType.description || ''
  }
})

const handleSubmit = async () => {
  // Валидация
  if (!name.value.trim()) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Укажите название типа съёмки'
    showAlert.value = true
    return
  }

  if (!basePrice.value) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Укажите цену'
    showAlert.value = true
    return
  }

  const price = parseFloat(basePrice.value.toString())
  if (isNaN(price) || price <= 0) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Цена должна быть положительным числом'
    showAlert.value = true
    return
  }

  const duration = parseInt(durationMinutes.value.toString())
  if (isNaN(duration) || duration <= 0) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Длительность должна быть положительным числом'
    showAlert.value = true
    return
  }

  const result = await referencesStore.updateShootingType({
    id: id.value,
    name: name.value.trim(),
    base_price: price,
    duration_minutes: duration,
    description: description.value.trim() || null
  })

  if (result.success) {
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось обновить тип съёмки'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay">
      <div class="modal-glass">
        <div class="modal-glassTitle">Редактировать тип съёмки</div>

        <div class="input-group">
          <div class="input-field">
            <label class="input-label">Название: <span class="required">*</span></label>
            <input
              type="text"
              class="modal-input"
              v-model="name"
              placeholder="Например: Портретная съёмка"
              maxlength="100"
            />
          </div>

          <div class="input-field">
            <label class="input-label">Цена (₽): <span class="required">*</span></label>
            <input
              type="number"
              class="modal-input"
              v-model="basePrice"
              placeholder="3000"
              min="0"
              step="100"
            />
          </div>

          <div class="input-field">
            <label class="input-label">Длительность (мин): <span class="required">*</span></label>
            <input
              type="number"
              class="modal-input"
              v-model="durationMinutes"
              placeholder="30"
              min="1"
              step="5"
            />
          </div>

          <div class="input-field">
            <label class="input-label">Описание:</label>
            <textarea
              class="modal-input"
              v-model="description"
              placeholder="Описание типа съёмки"
              rows="3"
            ></textarea>
          </div>
        </div>

        <div class="ButtonFooter PosRight">
          <button class="buttonGL buttonGL-textFix" @click="emit('close')">
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" />
            <span>Отмена</span>
          </button>
          <button class="buttonGL buttonGL-textFix" @click="handleSubmit">
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
            <span>Сохранить</span>
          </button>
        </div>
      </div>
    </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>
