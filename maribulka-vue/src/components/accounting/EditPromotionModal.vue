<script setup lang="ts">
import { ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useReferencesStore } from '../../stores/references'
import AlertModal from '../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  promotion: any | null
}>()
const emit = defineEmits(['close'])

const referencesStore = useReferencesStore()

// Form data
const id = ref(0)
const name = ref('')
const discountPercent = ref('')
const startDate = ref('')
const endDate = ref('')
const isUnlimited = ref(false)

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Заполнение формы при открытии
watch(() => props.isVisible, (newValue) => {
  if (newValue && props.promotion) {
    id.value = props.promotion.id
    name.value = props.promotion.name || ''
    discountPercent.value = props.promotion.discount_percent || ''
    startDate.value = props.promotion.start_date || ''
    endDate.value = props.promotion.end_date || ''
    isUnlimited.value = !props.promotion.start_date && !props.promotion.end_date
  }
})

const handleSubmit = async () => {
  // Валидация
  if (!name.value.trim()) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Укажите название акции'
    showAlert.value = true
    return
  }

  if (!discountPercent.value) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Укажите процент скидки'
    showAlert.value = true
    return
  }

  const discount = parseFloat(discountPercent.value.toString())
  if (isNaN(discount) || discount <= 0 || discount > 100) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Скидка должна быть от 1 до 100%'
    showAlert.value = true
    return
  }

  // Проверка: если не бессрочно, то либо обе даты заполнены, либо обе пустые
  if (!isUnlimited.value) {
    const hasStartDate = !!startDate.value
    const hasEndDate = !!endDate.value

    if (hasStartDate !== hasEndDate) {
      alertTitle.value = 'Ошибка'
      alertMessage.value = 'Укажите обе даты (начало и окончание) или оставьте обе пустыми'
      showAlert.value = true
      return
    }
  }

  const result = await referencesStore.updatePromotion({
    id: id.value,
    name: name.value.trim(),
    discount_percent: discount,
    start_date: isUnlimited.value ? null : (startDate.value || null),
    end_date: isUnlimited.value ? null : (endDate.value || null)
  })

  if (result.success) {
    emit('close')
  } else {
    alertTitle.value = result.error === 'Пересечение периодов' ? 'Пересечение периодов' : 'Ошибка'
    alertMessage.value = result.message || result.error || 'Не удалось обновить акцию'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
        <div class="modal-glassTitle">Редактировать акцию</div>

        <div class="input-group">
          <div class="input-field">
            <label class="input-label">Название: <span class="required">*</span></label>
            <input
              type="text"
              class="modal-input"
              v-model="name"
              placeholder="Например: Летняя скидка"
              maxlength="100"
            />
          </div>

          <div class="input-field">
            <label class="input-label">Скидка (%): <span class="required">*</span></label>
            <input
              type="number"
              class="modal-input"
              v-model="discountPercent"
              placeholder="10"
              min="1"
              max="100"
              step="1"
            />
          </div>

          <div class="input-field">
            <label class="input-label">
              <input
                type="checkbox"
                v-model="isUnlimited"
                style="margin-right: 8px;"
              />
              Бессрочно
            </label>
          </div>

          <div class="input-field">
            <label class="input-label">Дата начала:</label>
            <input
              type="date"
              class="modal-input"
              v-model="startDate"
              :disabled="isUnlimited"
            />
          </div>

          <div class="input-field">
            <label class="input-label">Дата окончания:</label>
            <input
              type="date"
              class="modal-input"
              v-model="endDate"
              :disabled="isUnlimited"
            />
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
