<script setup lang="ts">
import { computed, ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCheckCircle, mdilCurrencyRub, mdilCancel } from '@mdi/light-js'
import { useBookingsStore } from '../../stores/bookings'
import AlertModal from '../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  booking: any | null
}>()
const emit = defineEmits(['close'])

const bookingsStore = useBookingsStore()

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

const orderInfo = computed(() => {
  if (!props.booking) return null
  const total = parseFloat(props.booking.total_amount) || 0
  const paid = parseFloat(props.booking.paid_amount) || 0
  const remaining = total - paid
  const isPaidFull = remaining <= 0

  // Формируем ID заказа: МБ-{id}.{year}
  const createdAt = props.booking.created_at || ''
  const year = createdAt.slice(0, 4) // Берем год из created_at
  const orderId = `МБ-${props.booking.id}.${year}`

  return {
    orderId,
    client: props.booking.client_name,
    shootingDate: props.booking.shooting_date,
    shootingType: props.booking.shooting_type_name,
    basePrice: Math.round(parseFloat(props.booking.base_price) || 0),
    discount: props.booking.promo_discount_percent || 0,
    total: Math.round(total),
    paid: Math.round(paid),
    remaining: Math.round(remaining),
    isPaidFull
  }
})

const handleQuickPayment = async () => {
  if (!props.booking) return

  const result = await bookingsStore.quickPayment(props.booking.id)
  if (result.success) {
    // После успешной оплаты обновляем данные
    await bookingsStore.fetchBookings()
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось провести оплату'
    showAlert.value = true
  }
}

const handleDeliver = async () => {
  if (!props.booking) return

  const result = await bookingsStore.markAsDelivered(props.booking.id)
  if (result.success) {
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось провести заказ'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
        <h2>Выдача съёмки</h2>

        <div v-if="orderInfo" class="delivery-info">
          <p><strong>ID заказа:</strong> {{ orderInfo.orderId }}</p>
          <p><strong>Клиент:</strong> {{ orderInfo.client }}</p>
          <p><strong>Дата съёмки:</strong> {{ orderInfo.shootingDate }}</p>
          <p><strong>Тип съёмки:</strong> {{ orderInfo.shootingType }}</p>

          <div class="divider"></div>

          <p><strong>Стоимость:</strong> {{ orderInfo.basePrice }} ₽</p>
          <p v-if="orderInfo.discount > 0"><strong>Скидка:</strong> -{{ orderInfo.discount }}%</p>
          <p><strong>Итого:</strong> <span class="price-value">{{ orderInfo.total }} ₽</span></p>

          <div class="divider"></div>

          <p><strong>Оплачено:</strong> {{ orderInfo.paid }} ₽</p>
          <p v-if="!orderInfo.isPaidFull" class="remaining-warning">
            <strong>⚠️ Требуется оплатить:</strong>
            <span class="remaining-amount">{{ orderInfo.remaining }} ₽</span>
          </p>
          <p v-else class="paid-full">✅ Оплачено полностью</p>
        </div>

        <div class="modal-actions">
          <!-- Кнопка "Оплата" - показываем только если не оплачено полностью -->
          <button
            v-if="orderInfo && !orderInfo.isPaidFull"
            class="glass-button"
            @click="handleQuickPayment"
            title="Оплатить остаток"
          >
            <svg-icon type="mdi" :path="mdilCurrencyRub" />
          </button>

          <!-- Кнопка "Провести" - активна только если оплачено полностью -->
          <button
            class="glass-button"
            :disabled="orderInfo && !orderInfo.isPaidFull"
            @click="handleDeliver"
            title="Провести заказ"
          >
            <svg-icon type="mdi" :path="mdilCheckCircle" />
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
.delivery-info {
  margin: 20px 0;
  padding: 15px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: #333;
}

.delivery-info p {
  margin: 8px 0;
  font-size: 14px;
}

.divider {
  height: 1px;
  background: rgba(0, 0, 0, 0.1);
  margin: 12px 0;
}

.price-value {
  font-weight: 600;
  color: #333;
  font-size: 16px;
}

.remaining-warning {
  color: #d97706;
  font-weight: 600;
}

.remaining-amount {
  color: #dc2626;
  font-size: 16px;
}

.paid-full {
  color: #059669;
  font-weight: 600;
}

.glass-button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
</style>
