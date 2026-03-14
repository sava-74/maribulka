<script setup lang="ts">
import { computed, ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline, mdiCurrencyRub } from '@mdi/js'
import { useBookingsStore } from '../../stores/bookings'
import AlertModal from '../AlertModal.vue'
import RefundModal from './RefundModal.vue'

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

// Refund modal
const showRefundModal = ref(false)

// Переключатель: кто отменил / клиент не пришёл
const cancelledBy = ref<'client' | 'photographer' | 'no_show'>('client')

const bookingInfo = computed(() => {
  if (!props.booking) return null

  // ID заказа из БД
  const orderId = props.booking.order_number || `МБ-${props.booking.id}`
  const paidAmount = Math.round(parseFloat(props.booking.paid_amount) || 0)
  const hasPayment = paidAmount > 0

  // Проверка: дата-время съёмки прошла?
  const shootingDateTime = new Date(props.booking.shooting_date)
  const now = new Date()
  const isSessionPassed = shootingDateTime < now

  // "Клиент не пришёл" доступна ТОЛЬКО если status='new' И дата прошла
  const canNoShow = props.booking.status === 'new' && isSessionPassed

  return {
    orderId,
    client: props.booking.client_name,
    shootingDate: formatDate(props.booking.shooting_date),
    paidAmount,
    hasPayment,
    canNoShow
  }
})

function formatDate(dateString: string) {
  if (!dateString) return ''
  const datePart = dateString.split('T')[0]?.split(' ')[0]
  if (!datePart) return ''
  const [year, month, day] = datePart.split('-')
  return `${day}.${month}.${year}`
}

// Кнопка "Отмена" - если НЕ было оплаты
const handleCancelWithoutRefund = async () => {
  if (!props.booking) return

  const result = await bookingsStore.cancelBooking(props.booking.id, cancelledBy.value)
  if (result.success) {
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось отменить запись'
    showAlert.value = true
  }
}

// Кнопка "Р" (возврат) - если была оплата
const handleOpenRefund = () => {
  showRefundModal.value = true
}

// После успешного возврата - отменяем заказ
const handleRefundCreated = async () => {
  showRefundModal.value = false

  const result = await bookingsStore.cancelBooking(props.booking!.id, cancelledBy.value)
  if (result.success) {
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось отменить запись'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay-main" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Отменить запись</div>

        <div v-if="bookingInfo" class="cancel-info">
          <p><strong>ID заказа:</strong> {{ bookingInfo.orderId }}</p>
          <p><strong>Клиент:</strong> {{ bookingInfo.client }}</p>
          <p><strong>Дата съёмки:</strong> {{ bookingInfo.shootingDate }}</p>

          <div class="divider"></div>

          <!-- Если была оплата - показываем сумму -->
          <div v-if="bookingInfo.hasPayment" class="payment-warning">
            <p><strong>⚠️ Возврат:</strong> {{ bookingInfo.paidAmount }} ₽</p>
            <p class="hint">Для отмены требуется возврат средств</p>
          </div>

          <div class="divider"></div>

          <div class="cancel-reason">
            <p><strong>Причина отмены:</strong></p>
            <div class="radio-group">
              <label class="radio-label">
                <input type="radio" name="cancelledBy" value="client" v-model="cancelledBy" />
                <span>Отменил клиент</span>
              </label>
              <label class="radio-label">
                <input type="radio" name="cancelledBy" value="photographer" v-model="cancelledBy" />
                <span>Отменил фотограф</span>
              </label>
              <!-- "Клиент не пришёл" доступна ТОЛЬКО если status='new' И дата съёмки прошла -->
              <label v-if="bookingInfo.canNoShow" class="radio-label">
                <input type="radio" name="cancelledBy" value="no_show" v-model="cancelledBy" />
                <span>Клиент не пришёл</span>
              </label>
            </div>
          </div>
        </div>

        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Закрыть</span>
          </button>

          <!-- Если НЕ было оплаты - кнопка "Отменить" -->
          <button
            v-if="bookingInfo && !bookingInfo.hasPayment"
            class="btnGlass iconText"
            @click="handleCancelWithoutRefund"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Отменить</span>
          </button>

          <!-- Если была оплата - кнопка "Возврат" -->
          <button
            v-if="bookingInfo && bookingInfo.hasPayment"
            class="btnGlass iconText"
            @click="handleOpenRefund"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCurrencyRub" class="btn-icon" />
            <span>Возврат</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Модалка возврата -->
    <RefundModal
      :isVisible="showRefundModal"
      :booking="booking"
      @close="showRefundModal = false"
      @refundCreated="handleRefundCreated"
    />

    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>
