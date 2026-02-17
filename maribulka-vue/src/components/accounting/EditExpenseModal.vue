<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCheck, mdilCancel } from '@mdi/light-js'
import { useFinanceStore } from '../../stores/finance'
import { useReferencesStore } from '../../stores/references'
import AlertModal from '../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  expense: any | null
}>()
const emit = defineEmits(['close', 'success'])

const financeStore = useFinanceStore()
const referencesStore = useReferencesStore()

// Form data
const id = ref(0)
const date = ref('')
const amount = ref('')
const category = ref('')
const description = ref('')
const booking_id = ref('')

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Проверка: выбрана ли категория "Возврат средств" (ID = 2)
const isRefundCategory = computed(() => {
  return parseInt(category.value) === 2
})

// Заполнение формы при открытии
watch(() => props.isVisible, (newValue) => {
  if (newValue && props.expense) {
    id.value = props.expense.id
    // Форматируем дату в YYYY-MM-DD для input type="date"
    const [datePart] = (props.expense.date || '').split(' ')
    date.value = datePart || ''
    amount.value = props.expense.amount || ''
    category.value = props.expense.category || ''
    description.value = props.expense.description || ''
    booking_id.value = props.expense.booking_id || ''

    // Загружаем категории расходов и список заказов для возврата
    referencesStore.fetchExpenseCategories()
    financeStore.fetchRefundableBookings()
  }
})

// Автоматическое заполнение суммы и описания при выборе заказа (только для возврата)
watch(booking_id, (newBookingId) => {
  if (isRefundCategory.value && newBookingId) {
    const booking = financeStore.refundableBookings.find(b => b.id === parseInt(newBookingId))
    if (booking && booking.paid_amount) {
      amount.value = booking.paid_amount.toString()
      description.value = `${booking.order_number} - ${booking.client_name}`
    }
  }
})

// Сброс booking_id, amount и description при смене категории
watch(category, (newCategory, oldCategory) => {
  if (oldCategory && newCategory !== oldCategory) {
    booking_id.value = ''
    if (isRefundCategory.value) {
      amount.value = ''
      description.value = ''
    }
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

  // Для категории "Возврат средств" - booking_id обязателен
  if (isRefundCategory.value && !booking_id.value) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Для возврата средств необходимо выбрать заказ'
    showAlert.value = true
    return
  }

  const expenseData: any = {
    id: id.value,
    date: date.value,
    amount: parseFloat(amount.value),
    category: parseInt(category.value),
    description: description.value.trim()
  }

  // Добавляем booking_id только если указан
  if (booking_id.value) {
    expenseData.booking_id = parseInt(booking_id.value)
  }

  const result = await financeStore.updateExpense(expenseData)

  if (result.success) {
    emit('success')
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось обновить расход'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
        <h2>Редактировать расход</h2>

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
              disabled
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

          <!-- Поле ID заказа - только для категории "Возврат средств" -->
          <div v-if="isRefundCategory" class="input-field">
            <label class="input-label">Заказ: <span class="required">*</span></label>
            <select
              class="modal-input"
              v-model="booking_id"
              disabled
            >
              <option value="">Выберите заказ</option>
              <option
                v-for="booking in financeStore.refundableBookings"
                :key="booking.id"
                :value="booking.id"
              >
                {{ booking.order_number }} - {{ booking.client_name }}
              </option>
            </select>
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
