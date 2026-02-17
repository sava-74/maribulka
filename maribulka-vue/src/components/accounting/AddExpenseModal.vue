<script setup lang="ts">
import { ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCheck, mdilCancel } from '@mdi/light-js'
import { useFinanceStore } from '../../stores/finance'
import { useReferencesStore } from '../../stores/references'
import AlertModal from '../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
}>()
const emit = defineEmits(['close', 'success'])

const financeStore = useFinanceStore()
const referencesStore = useReferencesStore()

// Form data
const date = ref('')
const amount = ref('')
const category = ref('')
const description = ref('')
const booking_id = ref('')

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Сброс формы при открытии
watch(() => props.isVisible, (newValue) => {
  if (newValue) {
    const today = new Date().toISOString().split('T')[0]
    date.value = today
    amount.value = ''
    category.value = ''
    description.value = ''
    booking_id.value = ''
  }
})

const handleSubmit = async () => {
  // Валидация
  if (!date.value) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Укажите дату расхода'
    showAlert.value = true
    return
  }

  if (!amount.value || parseFloat(amount.value) <= 0) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Укажите корректную сумму расхода'
    showAlert.value = true
    return
  }

  if (!category.value) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Выберите категорию расхода'
    showAlert.value = true
    return
  }

  if (!description.value.trim()) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Укажите описание расхода'
    showAlert.value = true
    return
  }

  const expenseData: any = {
    date: date.value,
    amount: parseFloat(amount.value),
    category_id: parseInt(category.value),
    description: description.value.trim()
  }

  // Добавляем booking_id только если указан
  if (booking_id.value) {
    expenseData.booking_id = parseInt(booking_id.value)
  }

  const result = await financeStore.createExpense(expenseData)

  if (result.success) {
    emit('success')
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось создать расход'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
        <h2>Новый расход</h2>

        <div class="input-group">
          <div class="input-field">
            <label class="input-label">Дата: <span class="required">*</span></label>
            <input
              type="date"
              class="modal-input"
              v-model="date"
            />
          </div>

          <div class="input-field">
            <label class="input-label">Сумма: <span class="required">*</span></label>
            <input
              type="number"
              class="modal-input"
              v-model="amount"
              placeholder="0.00"
              step="0.01"
              min="0.01"
            />
          </div>

          <div class="input-field">
            <label class="input-label">Категория: <span class="required">*</span></label>
            <select
              class="modal-input"
              v-model="category"
            >
              <option value="">Выберите категорию</option>
              <option
                v-for="cat in referencesStore.expenseCategories"
                :key="cat.id"
                :value="cat.id"
              >
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div class="input-field">
            <label class="input-label">Описание: <span class="required">*</span></label>
            <textarea
              class="modal-input"
              v-model="description"
              placeholder="Описание расхода"
              rows="3"
              maxlength="500"
            ></textarea>
          </div>

          <div class="input-field">
            <label class="input-label">ID заказа (опционально):</label>
            <input
              type="number"
              class="modal-input"
              v-model="booking_id"
              placeholder="Номер заказа"
              min="1"
            />
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
