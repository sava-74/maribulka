<script setup lang="ts">
import { computed, ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCurrencyRub, mdiCloseCircleOutline } from '@mdi/js'
import { useBookingsStore } from '../../stores/bookings'
import AlertModal from '../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  booking: any | null
}>()
const emit = defineEmits(['close', 'openPayment'])

const bookingsStore = useBookingsStore()

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Результат выполнения заказа
const orderResult = ref<'completed' | 'completed_partially' | 'not_completed'>('completed')

const orderInfo = computed(() => {
  if (!props.booking) return null
  const total = parseFloat(props.booking.total_amount) || 0
  const paid = parseFloat(props.booking.paid_amount) || 0
  const remaining = total - paid
  const isPaidFull = remaining <= 0

  // ID заказа из БД
  const orderId = props.booking.order_number || `МБ-${props.booking.id}`

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

  // Проверяем оплату 100%
  if (!orderInfo.value?.isPaidFull) {
    emit('openPayment', props.booking.id)
    return
  }

  // Выдаём заказ с выбранным результатом
  const result = await bookingsStore.completeOrder(props.booking.id, orderResult.value)
  if (result.success) {
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось выдать заказ'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
        <div class="modal-glassTitle">Выдать заказ</div>

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

          <!-- Только если оплачено 100% показываем выбор результата -->
          <div v-if="orderInfo.isPaidFull" class="order-result-section">
            <div class="divider"></div>

            <p><strong>Результат выполнения:</strong></p>
            <div class="radio-group">
              <label class="radio-label">
                <input type="radio" name="orderResult" value="completed" v-model="orderResult" />
                <span>✅ Клиент принял заказ полностью</span>
              </label>
              <label class="radio-label">
                <input type="radio" name="orderResult" value="completed_partially" v-model="orderResult" />
                <span>⚠️ Клиент принял заказ частично (возврат 50%)</span>
              </label>
              <label class="radio-label">
                <input type="radio" name="orderResult" value="not_completed" v-model="orderResult" />
                <span>❌ Клиент не принял заказ (возврат 100%)</span>
              </label>
            </div>
          </div>
        </div>

        <div class="ButtonFooter PosRight">
          <!-- Кнопка "Закрыть" -->
          <button class="buttonGL buttonGL-textFix" @click="emit('close')">
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" />
            <span>Закрыть</span>
          </button>

          <!-- Кнопка "Оплатить" - показываем только если не оплачено полностью -->
          <button
            v-if="orderInfo && !orderInfo.isPaidFull"
            class="buttonGL buttonGL-textFix"
            @click="handleQuickPayment"
          >
            <svg-icon type="mdi" :path="mdiCurrencyRub" />
            <span>Оплатить</span>
          </button>

          <!-- Кнопка "Выдать" - только если оплачено полностью -->
          <button
            v-if="orderInfo && orderInfo.isPaidFull"
            class="buttonGL buttonGL-textFix"
            @click="handleDeliver"
          >
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
            <span>Выдать</span>
          </button>
        </div>
      </div>
    </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>
