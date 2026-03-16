<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useFinanceStore } from '../../../stores/finance'
import { useAuthStore } from '../../../stores/auth'
import AlertModal from '../../AlertModal.vue'
import SelectBox from '../../SelectBox.vue'

const props = defineProps<{
  isVisible: boolean
  dateFrom: string
  dateTo: string
}>()

const emit = defineEmits(['close'])

const financeStore = useFinanceStore()
const authStore = useAuthStore()

const selectedBookingId = ref<number | null>(null)
const showAlert = ref(false)
const alertMessage = ref('')

const today = new Date().toISOString().split('T')[0]

const bookingOptions = computed(() =>
  financeStore.refundableBookings.map((b: any) => ({
    value: b.id,
    label: `${b.order_number} — ${b.client_name}`
  }))
)

const selectedBooking = computed(() =>
  financeStore.refundableBookings.find((b: any) => b.id === selectedBookingId.value) ?? null
)

const refundAmount = computed(() =>
  selectedBooking.value ? Math.round(parseFloat(selectedBooking.value.paid_amount) || 0) : 0
)

onMounted(async () => {
  await financeStore.fetchRefundableBookings()
})


async function handleSave() {
  if (!selectedBookingId.value || refundAmount.value <= 0) return
  try {
    const result = await financeStore.createExpense({
      date: today,
      category: 2,
      amount: refundAmount.value,
      booking_id: selectedBookingId.value,
      description: `Возврат по заказу ${selectedBooking.value?.order_number ?? ''}`,
      created_by: authStore.userId
    })
    if (result.success) {
      await financeStore.fetchExpenses(props.dateFrom, props.dateTo)
      selectedBookingId.value = null
      emit('close')
    } else {
      alertMessage.value = 'Ошибка: ' + (result.error || 'Не удалось создать возврат')
      showAlert.value = true
    }
  } catch {
    alertMessage.value = 'Ошибка сети'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Возврат средств</div>

        <div class="info-row">
          <span class="info-label">Дата:</span>
          <span class="info-value">{{ today }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Заказ:</span>
          <SelectBox v-model="selectedBookingId" :options="bookingOptions" placeholder="— выберите заказ —" />
        </div>
        <div class="info-row">
          <span class="info-label">Сумма возврата:</span>
          <span class="info-value" style="font-weight: 600;">
            {{ refundAmount > 0 ? refundAmount + ' ₽' : '—' }}
          </span>
        </div>

        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button
            class="btnGlass iconText"
            :disabled="!selectedBookingId || refundAmount <= 0"
            @click="handleSave"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Оформить возврат</span>
          </button>
        </div>
      </div>
    </div>

    <AlertModal :is-visible="showAlert" :message="alertMessage" @close="showAlert = false" />
  </Teleport>
</template>
