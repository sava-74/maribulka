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
const paidAmount = ref(0)

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
    paidAmount.value = newBooking.paid_amount ? parseFloat(newBooking.paid_amount) : 0
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

// Computed: Доступные акции для dropdown (бессрочные + актуальные на сегодня)
const availablePromotions = computed(() => {
  const today = new Date().toISOString().split('T')[0] as string

  return referencesStore.promotions.filter(promo => {
    // Бессрочные акции всегда доступны
    if (!promo.start_date && !promo.end_date) return true

    // Срочные акции доступны только если сегодня в диапазоне
    if (promo.start_date && promo.end_date) {
      return today >= promo.start_date && today <= promo.end_date
    }

    return false
  })
})

// Все кандидатные слоты с 08:00 до 22:00 (шаг 30 мин)
const allTimeSlots = computed(() => {
  const slots: string[] = []
  for (let h = 8; h <= 22; h++) {
    slots.push(`${String(h).padStart(2, '0')}:00`)
    if (h < 22) slots.push(`${String(h).padStart(2, '0')}:30`)
  }
  return slots
})

// Занятые блоки на выбранную дату (исключая текущую запись)
const busyBlocks = computed(() => {
  if (!shootingDate.value) return [] as [number, number][]
  const blocks: [number, number][] = []

  for (const booking of bookingsStore.bookings) {
    // Пропускаем текущую редактируемую запись
    if (booking.id === bookingId.value) continue
    if (booking.status === 'cancelled' || booking.status === 'cancelled_client' ||
        booking.status === 'cancelled_photographer' || booking.status === 'failed') {
      continue
    }

    const bDate = new Date(booking.shooting_date)
    const bDateStr = `${bDate.getFullYear()}-${String(bDate.getMonth() + 1).padStart(2, '0')}-${String(bDate.getDate()).padStart(2, '0')}`
    if (bDateStr !== shootingDate.value) continue

    const shootingType = referencesStore.shootingTypes.find(
      (t: any) => t.id === booking.shooting_type_id
    )
    const duration = shootingType?.duration_minutes || 30
    const startMin = bDate.getHours() * 60 + bDate.getMinutes()
    const endMin = startMin + duration + 30
    blocks.push([startMin, endMin])
  }
  return blocks
})

// Длительность блока для выбранного типа съёмки
const newBookingBlock = computed(() => {
  if (!selectedShootingType.value) return 0
  return (selectedShootingType.value.duration_minutes || 30) + 30
})

// Свободные слоты
const freeTimeSlots = computed(() => {
  if (!shootingTypeId.value || newBookingBlock.value === 0) return [] as string[]
  const blockLen = newBookingBlock.value

  return allTimeSlots.value.filter(slot => {
    const parts = slot.split(':').map(Number)
    const hh = parts[0]
    const mm = parts[1]
    if (hh === undefined || mm === undefined) return false

    const candidateStart = hh * 60 + mm
    const candidateEnd = candidateStart + blockLen
    if (candidateEnd > 23 * 60) return false
    for (const [busyStart, busyEnd] of busyBlocks.value) {
      if (candidateStart < busyEnd && candidateEnd > busyStart) return false
    }
    return true
  })
})

// При смене типа съёмки — проверить, что текущий слот ещё свободен
watch([() => shootingTypeId.value, freeTimeSlots], () => {
  if (freeTimeSlots.value.length > 0 && !freeTimeSlots.value.includes(shootingTime.value)) {
    alertTitle.value = 'Внимание'
    alertMessage.value = 'Выбранное время не подходит для нового типа съёмки. Выберите другое время.'
    showAlert.value = true
    const firstSlot = freeTimeSlots.value[0]
    if (firstSlot) {
      shootingTime.value = firstSlot
    }
  }
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
      <div class="modal-glass modal-compact modal-wide">
      <h2>Редактировать запись</h2>

      <div v-if="booking?.order_number" class="order-number-preview">
        <label class="input-label">Номер заказа:</label>
        <strong>{{ booking.order_number }}</strong>
      </div>

      <div class="input-group">
        <!-- Строка 1: Тип съёмки, количество, акция -->
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
              <option v-for="promo in availablePromotions" :key="promo.id" :value="promo.id">
                {{ promo.name }} (-{{ promo.discount_percent }}%)
              </option>
            </select>
          </div>
        </div>

        <!-- Строка 2: Даты -->
        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Дата съёмки: *</label>
            <input v-model="shootingDate" type="date" class="modal-input" required />
          </div>
          <div class="input-field input-field-narrow">
            <label class="input-label">Время: *</label>
            <select v-model="shootingTime" class="modal-input" :disabled="!shootingTypeId" required>
              <option v-if="!shootingTypeId" value="" disabled>Выберите тип</option>
              <option v-else-if="freeTimeSlots.length === 0" value="" disabled>Нет слотов</option>
              <option v-for="slot in freeTimeSlots" :key="slot" :value="slot">{{ slot }}</option>
            </select>
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

        <!-- Строка 3: Клиент (readonly) и телефон -->
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

        <!-- Информация о ценах + оплата -->
        <div class="price-info">
          <div class="price-row">
            <span class="price-label">Базовая цена:</span>
            <span class="price-value">{{ Math.round(basePrice) }} ₽</span>
          </div>
          <div class="price-row" v-if="discount > 0">
            <span class="price-label">Скидка ({{ discountPercent }}%):</span>
            <span class="price-value discount-value">-{{ Math.round(discount) }} ₽</span>
          </div>
          <div class="price-row" v-if="quantity > 1">
            <span class="price-label">Цена за 1 съёмку:</span>
            <span class="price-value">{{ Math.round(finalPrice) }} ₽</span>
          </div>
          <div class="price-row total">
            <span class="price-label">Итого (×{{ quantity }}):</span>
            <span class="price-value total-value">{{ Math.round(totalAmount) }} ₽</span>
          </div>
          <div class="price-row">
            <span class="price-label">Оплачено:</span>
            <span class="price-value">{{ Math.round(paidAmount) }} ₽</span>
          </div>
        </div>

        <!-- Примечания -->
        <div class="notes-field">
          <label class="input-label">Примечания:</label>
          <textarea v-model="notes" class="modal-textarea" rows="2" placeholder="Дополнительная информация о заказе"></textarea>
        </div>
      </div>

      <div class="modal-actions">
        <!-- Кнопка "Закрыть" -->
        <button class="glass-button" @click="emit('close')">
          <svg-icon type="mdi" :path="mdilCancel" />
        </button>
        <!-- Кнопка "Сохранить" -->
        <button class="glass-button" @click="handleSubmit">
          <svg-icon type="mdi" :path="mdilCheck" />
        </button>        
      </div>
    </div>
  </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>
