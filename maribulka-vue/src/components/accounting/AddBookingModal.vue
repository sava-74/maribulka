<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useBookingsStore } from '../../stores/bookings'
import { useReferencesStore } from '../../stores/references'
import { getLocalDateString } from '../../config/timezone'
import AlertModal from '../AlertModal.vue'
import '../../assets/responsive.css'

const props = defineProps<{ isVisible: boolean, defaultDate?: string }>()
const emit = defineEmits(['close'])

const bookingsStore = useBookingsStore()
const referencesStore = useReferencesStore()

// Form fields
const shootingDate = ref('')
const shootingTime = ref('10:00')
const processingDays = ref(10)
const clientName = ref('')
const phone = ref('')
const shootingTypeId = ref('')
const quantity = ref(1)
const promotionId = ref('')
const notes = ref('')
const paymentAmount = ref(0)
const generatedOrderNumber = ref('')

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Load reference data on mount and generate order number
onMounted(async () => {
  if (referencesStore.shootingTypes.length === 0) {
    await referencesStore.fetchShootingTypes()
  }
  if (referencesStore.promotions.length === 0) {
    await referencesStore.fetchPromotions()
  }
  if (referencesStore.clients.length === 0) {
    await referencesStore.fetchClients()
  }

  // Генерируем order_number
  const nextId = await bookingsStore.getNextId()
  const today = new Date()
  const day = today.getDate()
  const month = today.getMonth() + 1
  const year = today.getFullYear().toString().slice(-2)
  const magicNumber = day * month

  generatedOrderNumber.value = `МБ${nextId}${magicNumber}${year}`
})

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

// Computed: Выбранный клиент
const selectedClient = computed(() => {
  if (!clientName.value) return null
  return referencesStore.clients.find(c => c.name === clientName.value)
})

// Watch: Автоподстановка телефона при выборе клиента
watch(selectedClient, (client) => {
  if (client && client.phone) {
    phone.value = client.phone
  }
})

// Оплата по умолчанию = 0 (пользователь вводит сам)

// Функция поиска активной акции для даты съёмки
function getActivePromotionForDate(date: string): number | null {
  // Если дата съёмки выбрана - ищем срочную акцию на эту дату
  if (date) {
    const timedPromotion = referencesStore.promotions.find(promo => {
      if (!promo.start_date || !promo.end_date) return false
      return date >= promo.start_date && date <= promo.end_date
    })

    if (timedPromotion) return timedPromotion.id
  }

  // Если даты нет ИЛИ нет срочной акции - берём бессрочную "Без скидки"
  const permanentPromotion = referencesStore.promotions.find(promo => {
    return !promo.start_date && !promo.end_date
  })

  return permanentPromotion ? permanentPromotion.id : null
}

// Computed: Доступные акции для dropdown (бессрочные + актуальные на сегодня)
const availablePromotions = computed(() => {
  // Используем дату съёмки, если выбрана, иначе сегодня (UTC+5 из конфига)
  const targetDate = shootingDate.value || getLocalDateString()

  return referencesStore.promotions.filter(promo => {
    // Бессрочные акции всегда доступны
    if (!promo.start_date && !promo.end_date) return true

    // Срочные акции доступны только если дата съёмки в диапазоне
    if (promo.start_date && promo.end_date) {
      return targetDate >= promo.start_date && targetDate <= promo.end_date
    }

    return false
  })
})

// Подставляем дату из календаря при открытии
watch(() => props.isVisible, (visible) => {
  if (visible && props.defaultDate) {
    shootingDate.value = props.defaultDate
  }
})

// Автовыбор акции при изменении даты съёмки
watch(() => shootingDate.value, (newDate) => {
  if (newDate) {
    const activePromotionId = getActivePromotionForDate(newDate)
    promotionId.value = activePromotionId ? activePromotionId.toString() : ''
  }
}, { immediate: true })

// Сегодняшняя дата (для ограничения min)
const todayStr = computed(() => {
  const t = new Date()
  return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(t.getDate()).padStart(2, '0')}`
})

// Выбранная дата — сегодня?
const isToday = computed(() => shootingDate.value === todayStr.value)

// Все кандидатные слоты с 08:00 до 22:00 (шаг 30 мин)
const allTimeSlots = computed(() => {
  const slots: string[] = []
  for (let h = 8; h <= 22; h++) {
    slots.push(`${String(h).padStart(2, '0')}:00`)
    if (h < 22) slots.push(`${String(h).padStart(2, '0')}:30`)
  }
  return slots
})

// Занятые блоки на выбранную дату: массив [startMin, endMin]
const busyBlocks = computed(() => {
  if (!shootingDate.value) return [] as [number, number][]
  const blocks: [number, number][] = []

  for (const booking of bookingsStore.bookings) {
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
    const endMin = startMin + duration + 30 // duration + обязательный перерыв
    blocks.push([startMin, endMin])
  }
  return blocks
})

// Длительность нового бронирования (duration + 30 мин перерыв)
const newBookingBlock = computed(() => {
  if (!selectedShootingType.value) return 0
  return (selectedShootingType.value.duration_minutes || 30) + 30
})

// Свободные слоты: только те, куда новый блок влезает целиком без пересечений
const freeTimeSlots = computed(() => {
  if (!shootingTypeId.value || newBookingBlock.value === 0) return [] as string[]

  const blockLen = newBookingBlock.value
  // Текущее время в минутах (для фильтрации прошедших слотов на сегодня)
  const now = new Date()
  const nowMinutes = now.getHours() * 60 + now.getMinutes()

  return allTimeSlots.value.filter(slot => {
    const parts = slot.split(':').map(Number)
    const hh = parts[0]
    const mm = parts[1]
    if (hh === undefined || mm === undefined) return false

    const candidateStart = hh * 60 + mm
    const candidateEnd = candidateStart + blockLen

    // На сегодня — пропускаем слоты раньше текущего времени
    if (isToday.value && candidateStart <= nowMinutes) return false

    // Проверяем что блок не выходит за рабочий день (до 23:00 = 1380 мин)
    if (candidateEnd > 23 * 60) return false

    // Проверяем пересечение с каждым занятым блоком
    for (const [busyStart, busyEnd] of busyBlocks.value) {
      if (candidateStart < busyEnd && candidateEnd > busyStart) {
        return false // пересечение
      }
    }
    return true
  })
})

// При смене даты/типа — выбрать первый свободный слот
watch([() => shootingDate.value, () => shootingTypeId.value, freeTimeSlots], () => {
  if (freeTimeSlots.value.length > 0 && !freeTimeSlots.value.includes(shootingTime.value)) {
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
  if (!shootingDate.value || !clientName.value || !phone.value || !shootingTypeId.value) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Заполните обязательные поля: Дата съёмки, Клиент, Телефон, Тип съёмки'
    showAlert.value = true
    return
  }

  // Находим или создаём клиента
  let clientId = selectedClient.value?.id

  if (!clientId) {
    // Создаём нового клиента
    const clientResult = await referencesStore.createClient({
      name: clientName.value,
      phone: phone.value
    })
    if (clientResult.success) {
      clientId = clientResult.id
    } else {
      alertTitle.value = 'Ошибка'
      alertMessage.value = 'Ошибка при создании клиента'
      showAlert.value = true
      return
    }
  }

  // Объединяем дату и время
  const shootingDateTime = `${shootingDate.value} ${shootingTime.value}:00`

  const data = {
    order_number: generatedOrderNumber.value,
    shooting_date: shootingDateTime,
    processing_days: processingDays.value,
    client_id: clientId,
    phone: phone.value,
    shooting_type_id: parseInt(shootingTypeId.value),
    quantity: quantity.value,
    promotion_id: promotionId.value ? parseInt(promotionId.value) : null,
    notes: notes.value || null,
    payment_amount: paymentAmount.value > 0 ? paymentAmount.value : null
  }

  const result = await bookingsStore.createBooking(data)
  if (result.success) {
    // Reset form
    shootingDate.value = ''
    shootingTime.value = '10:00'
    processingDays.value = 10
    clientName.value = ''
    phone.value = ''
    shootingTypeId.value = ''
    quantity.value = 1
    promotionId.value = ''
    notes.value = ''
    paymentAmount.value = 0
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Ошибка при создании записи: ' + (result.error || 'Неизвестная ошибка')
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass modal-compact modal-wide">
      <div class="modal-glassTitle">Добавить запись на съёмку</div>

      <!-- Номер заказа -->
      <div v-if="generatedOrderNumber" class="order-number-preview">
        <label class="input-label">Номер заказа:</label>
        <strong>{{ generatedOrderNumber }}</strong>
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
            <input v-model="shootingDate" type="date" class="modal-input" :min="todayStr" required />
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

        <!-- Строка 3: Клиент и телефон -->
        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Клиент: *</label>
            <input v-model="clientName" type="text" class="modal-input" list="clients-list" placeholder="Выберите или введите имя" required />
            <datalist id="clients-list">
              <option v-for="client in referencesStore.clients" :key="client.id" :value="client.name"></option>
            </datalist>
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
            <span class="price-label">Оплата:</span>
            <span class="price-value">
              <input v-model.number="paymentAmount" type="number" class="payment-input-inline" min="0" :max="Math.round(totalAmount)" /> ₽
            </span>
          </div>
        </div>

        <!-- Примечания -->
        <div class="notes-field">
          <label class="input-label">Примечания:</label>
          <textarea v-model="notes" class="modal-textarea" rows="2" placeholder="Дополнительная информация о заказе"></textarea>
        </div>
      </div>

      <div class="modal-actions">
        <!-- Кнопка "Отмена" -->
        <button class="buttonGL" @click="emit('close')">
          <svg-icon type="mdi" :path="mdiCloseCircleOutline" />
        </button>
        <!-- Кнопка "OK" -->
        <button class="buttonGL" @click="handleSubmit">
          <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
        </button>
      </div>
    </div>
  </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>
