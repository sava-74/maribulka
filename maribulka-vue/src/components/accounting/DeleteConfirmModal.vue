<script setup lang="ts">
import { ref, computed } from 'vue'
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

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

const bookingInfo = computed(() => {
  if (!props.booking) return null
  return {
    client: props.booking.client_name,
    shootingDate: formatDate(props.booking.shooting_date),
    amount: parseFloat(props.booking.total_amount || 0).toFixed(2)
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

  const result = await bookingsStore.deleteBooking(props.booking.id)
  if (result.success) {
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Ошибка при удалении записи'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
      <div class="modal-glassTitle">Подтверждение удаления</div>

      <div v-if="bookingInfo" class="delete-info">
        <p><strong>Клиент:</strong> {{ bookingInfo.client }}</p>
        <p><strong>Дата съёмки:</strong> {{ bookingInfo.shootingDate }}</p>
        <p><strong>Сумма:</strong> {{ bookingInfo.amount }} ₽</p>
      </div>

      <p class="delete-warning">Вы уверены, что хотите удалить эту запись?</p>

      <div class="modal-actions">
        <button class="glass-button" @click="emit('close')">
          <svg-icon type="mdi" :path="mdiCloseCircleOutline" />
        </button>
        <button class="glass-button" @click="handleConfirm">
          <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
        </button>
        
      </div>
    </div>
  </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>
