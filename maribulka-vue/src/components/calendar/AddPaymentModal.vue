<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useBookingsStore } from '../../stores/bookings'
import AlertModal from '../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  booking: any | null
}>()
const emit = defineEmits(['close'])

const bookingsStore = useBookingsStore()

const paymentAmount = ref(0)

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Watch for booking changes to suggest remaining amount
watch(() => props.booking, (newBooking) => {
  if (newBooking) {
    const total = parseFloat(newBooking.total_amount) || 0
    const paid = parseFloat(newBooking.paid_amount) || 0
    const remaining = total - paid
    paymentAmount.value = remaining > 0 ? remaining : 0
  }
}, { immediate: true })

const bookingInfo = computed(() => {
  if (!props.booking) return null
  const total = parseFloat(props.booking.total_amount) || 0
  const paid = parseFloat(props.booking.paid_amount) || 0
  const remaining = total - paid
  return {
    client: props.booking.client_name,
    total: Math.round(total),
    paid: Math.round(paid),
    remaining: Math.round(remaining)
  }
})

const handleSubmit = async () => {
  if (!props.booking) return

  if (paymentAmount.value <= 0) {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Сумма оплаты должна быть больше 0'
    showAlert.value = true
    return
  }

  const result = await bookingsStore.addPayment(props.booking.id, paymentAmount.value)
  if (result.success) {
    paymentAmount.value = 0
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Ошибка при добавлении оплаты'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay">
      <div class="padGlass modal-sm">
      <div class="modal-glassTitle">Добавить оплату</div>

      <div v-if="bookingInfo" class="payment-info">
        <p><strong>Клиент:</strong> {{ bookingInfo.client }}</p>
        <p><strong>Общая сумма:</strong> {{ bookingInfo.total }} ₽</p>
        <p><strong>Оплачено:</strong> {{ bookingInfo.paid }} ₽</p>
        <p><strong>Осталось:</strong> {{ bookingInfo.remaining }} ₽</p>
      </div>

      <div class="input-group">
        <label class="input-label">Сумма оплаты (₽)</label>
        <input
          v-model.number="paymentAmount"
          type="number"
          class="modal-input"
          min="0"
          step="0.01"
          placeholder="Введите сумму"
          @keyup.enter="handleSubmit"
        />
      </div>

      <div class="ButtonFooter PosRight">
        <!-- Кнопка "Отмена" -->
        <button class="btnGlass iconText" @click="emit('close')">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
          <span>Отмена</span>
        </button>
        <!-- Кнопка "OK" -->
        <button class="btnGlass iconText" @click="handleSubmit">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
          <span>Сохранить</span>
        </button>        
      </div>
    </div>
  </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>
