<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCancel, mdilCheck } from '@mdi/light-js'
import { useBookingsStore } from '../../stores/bookings'
import { useReferencesStore } from '../../stores/references'
import AlertModal from '../AlertModal.vue'
import '../../assets/responsive.css'

const props = defineProps<{
  isVisible: boolean
  booking: any | null
}>()
const emit = defineEmits(['close'])

const bookingsStore = useBookingsStore()
const referencesStore = useReferencesStore()

// Form fields
const bookingId = ref(0)
const shootingDate = ref('')
const shootingTime = ref('10:00')
const processingDays = ref(10)
const phone = ref('')
const shootingTypeId = ref('')
const quantity = ref(1)
const promotionId = ref('')
const notes = ref('')

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Load reference data on mount
onMounted(async () => {
  if (referencesStore.shootingTypes.length === 0) {
    await referencesStore.fetchShootingTypes()
  }
  if (referencesStore.promotions.length === 0) {
    await referencesStore.fetchPromotions()
  }
})

// Watch for booking prop changes to populate form
watch(() => props.booking, (newBooking) => {
  if (newBooking) {
    bookingId.value = newBooking.id
    // Разделяем дату и время
    const dateTime = newBooking.shooting_date || ''
    const [datePart, timePart] = dateTime.split(' ')
    shootingDate.value = datePart?.split('T')[0] || datePart || ''
    shootingTime.value = timePart?.substring(0, 5) || '10:00'
    processingDays.value = newBooking.processing_days || 10
    phone.value = newBooking.phone || ''
    shootingTypeId.value = newBooking.shooting_type_id || ''
    quantity.value = newBooking.quantity || 1
    promotionId.value = newBooking.promotion_id || ''
    notes.value = newBooking.notes || ''
  }
}, { immediate: true })

// Computed: Автоматический расчёт даты выдачи
const deliveryDate = computed(() => {
  if (!shootingDate.value || !processingDays.value) return ''
  const shooting = new Date(shootingDate.value)
  shooting.setDate(shooting.getDate() + processingDays.value)
  return shooting.toISOString().split('T')[0]
})

// Computed: Выбранный тип съёмки
const selectedShootingType = computed(() => {
  if (!shootingTypeId.value) return null
  return referencesStore.shootingTypes.find(t => t.id === parseInt(shootingTypeId.value))
})

// Computed: Выбранная акция
const selectedPromotion = computed(() => {
  if (!promotionId.value) return null
  return referencesStore.promotions.find(p => p.id === parseInt(promotionId.value))
})

// Computed: Базовая цена
const basePrice = computed(() => {
  return selectedShootingType.value ? parseFloat(selectedShootingType.value.base_price) : 0
})

// Computed: Процент скидки
const discountPercent = computed(() => {
  return selectedPromotion.value ? parseFloat(selectedPromotion.value.discount_percent) : 0
})

// Computed: Скидка в рублях
const discount = computed(() => {
  return (basePrice.value * discountPercent.value) / 100
})

// Computed: Финальная цена за одну съёмку
const finalPrice = computed(() => {
  return basePrice.value - discount.value
})

// Computed: Общая сумма
const totalAmount = computed(() => {
  return finalPrice.value * quantity.value
})

// Маска для телефона
function formatPhone(event: Event) {
  const input = event.target as HTMLInputElement
  let value = input.value.replace(/\D/g, '') // Убираем все кроме цифр

  if (value.length > 0 && value[0] !== '7') {
    value = '7' + value
  }

  if (value.length > 11) {
    value = value.substring(0, 11)
  }

  let formatted = '+7'
  if (value.length > 1) {
    formatted += '(' + value.substring(1, 4)
  }
  if (value.length >= 5) {
    formatted += ')' + value.substring(4, 7)
  }
  if (value.length >= 8) {
    formatted += '-' + value.substring(7, 9)
  }
  if (value.length >= 10) {
    formatted += '-' + value.substring(9, 11)
  }

  phone.value = formatted
}

const handleSubmit = async () => {
  // Validation
  if (!shootingDate.value || !phone.value || !shootingTypeId.value) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Заполните обязательные поля: Дата съёмки, Телефон, Тип съёмки'
    showAlert.value = true
    return
  }

  // Объединяем дату и время
  const shootingDateTime = `${shootingDate.value} ${shootingTime.value}:00`

  const data = {
    id: bookingId.value,
    shooting_date: shootingDateTime,
    processing_days: processingDays.value,
    phone: phone.value,
    shooting_type_id: parseInt(shootingTypeId.value),
    quantity: quantity.value,
    promotion_id: promotionId.value ? parseInt(promotionId.value) : null,
    notes: notes.value || null
  }

  const result = await bookingsStore.updateBooking(data)
  if (result.success) {
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Ошибка при обновлении записи: ' + (result.error || 'Неизвестная ошибка')
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass modal-wide">
      <h2>Редактировать запись</h2>

      <div class="input-group">
        <!-- Строка 1: Даты -->
        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Дата съёмки: *</label>
            <input v-model="shootingDate" type="date" class="modal-input" required />
          </div>
          <div class="input-field input-field-narrow">
            <label class="input-label">Время: *</label>
            <input v-model="shootingTime" type="time" class="modal-input" required />
          </div>
          <div class="input-field input-field-narrow">
            <label class="input-label">Дней:</label>
            <input v-model.number="processingDays" type="number" class="modal-input" min="1" max="99" />
          </div>
          <div class="input-field">
            <label class="input-label">Дата выдачи:</label>
            <input :value="deliveryDate" type="date" class="modal-input" readonly />
          </div>
        </div>

        <!-- Строка 2: Клиент (readonly) и телефон -->
        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Клиент:</label>
            <input :value="booking?.client_name" type="text" class="modal-input" readonly />
          </div>
          <div class="input-field input-field-phone">
            <label class="input-label">Телефон: *</label>
            <input v-model="phone" type="tel" class="modal-input" placeholder="+7(888)888-88-88" maxlength="16" @input="formatPhone" required />
          </div>
        </div>

        <!-- Строка 3: Тип съёмки, количество, акция -->
        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Тип съёмки: *</label>
            <select v-model="shootingTypeId" class="modal-input" required>
              <option value="">Выберите тип</option>
              <option v-for="type in referencesStore.shootingTypes" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
          </div>
          <div class="input-field input-field-narrow">
            <label class="input-label">Кол-во:</label>
            <input v-model.number="quantity" type="number" class="modal-input" min="1" max="99" />
          </div>
          <div class="input-field input-field-promotion">
            <label class="input-label">Акция:</label>
            <select v-model="promotionId" class="modal-input">
              <option value="">Без акции</option>
              <option v-for="promo in referencesStore.promotions" :key="promo.id" :value="promo.id">
                {{ promo.name }} (-{{ promo.discount_percent }}%)
              </option>
            </select>
          </div>
        </div>

        <!-- Информация о ценах (readonly, расчётная) -->
        <div class="price-info">
          <div class="price-row">
            <span class="price-label">Базовая цена:</span>
            <span class="price-value">{{ Math.round(basePrice) }} ₽</span>
          </div>
          <div class="price-row" v-if="discount > 0">
            <span class="price-label">Скидка ({{ discountPercent }}%):</span>
            <span class="price-value discount-value">-{{ Math.round(discount) }} ₽</span>
          </div>
          <div class="price-row">
            <span class="price-label">Цена за 1 съёмку:</span>
            <span class="price-value">{{ Math.round(finalPrice) }} ₽</span>
          </div>
          <div class="price-row total">
            <span class="price-label">Итого (×{{ quantity }}):</span>
            <span class="price-value total-value">{{ Math.round(totalAmount) }} ₽</span>
          </div>
        </div>

        <!-- Примечания -->
        <div class="notes-field">
          <label class="input-label">Примечания:</label>
          <textarea v-model="notes" class="modal-textarea" rows="2" placeholder="Дополнительная информация о заказе"></textarea>
        </div>
      </div>

      <div class="modal-actions">
        <button class="glass-button" @click="handleSubmit">
          <svg-icon type="mdi" :path="mdilCheck" />
        </button>
        <button class="glass-button" @click="emit('close')">
          <svg-icon type="mdi" :path="mdilCancel" />
        </button>
      </div>
    </div>
  </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>

<style scoped>
/* Компактные отступы */
.input-group {
  gap: 2px;
}

.input-row {
  gap: 2px;
  margin-bottom: 2px;
}

.input-label {
  font-size: 13px;
  margin-bottom: 1px;
}

.modal-input {
  padding: 2px 4px;
  font-size: 13px;
}

.price-info {
  margin-top: 2px;
  padding: 2px;
}

.price-row {
  padding: 1px 0;
  font-size: 13px;
}

/* Примечания */
.notes-field {
  margin-top: 2px;
  width: 100%;
}

.modal-textarea {
  width: 100%;
  padding: 4px;
  font-size: 13px;
  border: 1px solid rgba(0, 0, 0, 0.2);
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.9);
  color: #000;
  resize: vertical;
  min-height: 40px;
}

.modal-textarea:focus {
  outline: none;
  border-color: rgba(59, 130, 246, 0.5);
  background: rgba(255, 255, 255, 1);
}
</style>
