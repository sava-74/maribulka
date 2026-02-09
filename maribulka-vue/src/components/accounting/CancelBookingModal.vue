<script setup lang="ts">
import { computed, ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCheck, mdilCancel } from '@mdi/light-js'
import { useBookingsStore } from '../../stores/bookings'
import AlertModal from '../AlertModal.vue'
import '../../assets/responsive.css'

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

// Переключатель: кто отменил
const cancelledBy = ref<'client' | 'photographer'>('client')

const bookingInfo = computed(() => {
  if (!props.booking) return null

  // Формируем ID заказа: МБ-{id}.{year}
  const createdAt = props.booking.created_at || ''
  const year = createdAt.slice(0, 4)
  const orderId = `МБ-${props.booking.id}.${year}`

  return {
    orderId,
    client: props.booking.client_name,
    shootingDate: formatDate(props.booking.shooting_date)
  }
})

function formatDate(dateString: string) {
  if (!dateString) return ''
  const datePart = dateString.split('T')[0]?.split(' ')[0]
  if (!datePart) return ''
  const [year, month, day] = datePart.split('-')
  return `${day}.${month}.${year}`
}

const handleConfirm = async () => {
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
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
        <h2>Отменить запись</h2>

        <div v-if="bookingInfo" class="cancel-info">
          <p><strong>ID заказа:</strong> {{ bookingInfo.orderId }}</p>
          <p><strong>Клиент:</strong> {{ bookingInfo.client }}</p>
          <p><strong>Дата съёмки:</strong> {{ bookingInfo.shootingDate }}</p>

          <div class="divider"></div>

          <div class="cancel-reason">
            <p><strong>Кто отменил:</strong></p>
            <div class="radio-group">
              <label class="radio-label">
                <input type="radio" name="cancelledBy" value="client" v-model="cancelledBy" />
                <span>Отменил клиент</span>
              </label>
              <label class="radio-label">
                <input type="radio" name="cancelledBy" value="photographer" v-model="cancelledBy" />
                <span>Отменил фотограф</span>
              </label>
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="glass-button" @click="handleConfirm">
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
