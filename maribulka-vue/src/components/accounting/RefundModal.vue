<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCheck, mdilCancel } from '@mdi/light-js'
import { useFinanceStore } from '../../stores/finance'
import AlertModal from '../AlertModal.vue'
import '../../assets/responsive.css'

const props = defineProps<{
  isVisible: boolean
  booking: any | null
}>()
const emit = defineEmits(['close', 'refundCreated'])

const financeStore = useFinanceStore()

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

// Комментарий к возврату (опционально)
const refundComment = ref('')

// Баланс кассы (загружается при открытии модалки)
const cashBalance = ref<number | null>(null)
const loadingBalance = ref(false)

// Загрузка баланса кассы при открытии модалки
watch(() => props.isVisible, async (visible) => {
  if (visible) {
    loadingBalance.value = true
    const balanceData = await financeStore.fetchCashBalance()
    cashBalance.value = balanceData.balance
    loadingBalance.value = false
  } else {
    // Сбрасываем при закрытии
    cashBalance.value = null
    loadingBalance.value = false
  }
})

const refundInfo = computed(() => {
  if (!props.booking) return null

  const orderId = props.booking.order_number || `МБ-${props.booking.id}`
  const paidAmount = Math.round(parseFloat(props.booking.paid_amount) || 0)

  return {
    orderId,
    client: props.booking.client_name,
    paidAmount
  }
})

// Проверка достаточности средств в кассе
const hasSufficientCash = computed(() => {
  if (!refundInfo.value || cashBalance.value === null) return false
  return cashBalance.value >= refundInfo.value.paidAmount
})

const handleConfirm = async () => {
  if (!props.booking || !refundInfo.value) return

  // Проверка кассы
  if (!hasSufficientCash.value) {
    alertTitle.value = 'Недостаточно средств'
    alertMessage.value = `В кассе недостаточно средств для возврата ${refundInfo.value.paidAmount} ₽`
    showAlert.value = true
    return
  }

  // Создаём расход (категория ID=2 "Возвраты")
  const description = refundComment.value
    ? `Возврат по заказу ${refundInfo.value.orderId}: ${refundComment.value}`
    : `Возврат по заказу ${refundInfo.value.orderId}`

  const result = await financeStore.createExpense({
    date: new Date().toISOString().split('T')[0],
    amount: refundInfo.value.paidAmount,
    category: 2, // Возвраты
    description,
    booking_id: props.booking.id
  })

  if (result.success) {
    emit('refundCreated')
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось создать возврат'
    showAlert.value = true
  }
}

function handleClose() {
  refundComment.value = ''
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="handleClose">
      <div class="modal-glass">
        <div class="modal-glassTitle">Возврат средств</div>

        <div v-if="refundInfo" class="refund-info">
          <p><strong>ID заказа:</strong> {{ refundInfo.orderId }}</p>
          <p><strong>Клиент:</strong> {{ refundInfo.client }}</p>
          <p><strong>Сумма возврата:</strong> <span class="refund-amount">{{ refundInfo.paidAmount }} ₽</span></p>

          <div class="divider"></div>

          <!-- Комментарий (опционально) -->
          <div class="form-group">
            <label>Комментарий (опционально):</label>
            <textarea
              v-model="refundComment"
              placeholder="Причина возврата..."
              rows="3"
              class="form-textarea"
            ></textarea>
          </div>

          <div class="divider"></div>

          <!-- Статус баланса кассы -->
          <div v-if="loadingBalance" class="info-message">
            ⏳ Проверка баланса кассы...
          </div>
          <div v-else-if="!hasSufficientCash" class="warning-message">
            ⚠️ В кассе недостаточно средств для возврата!
          </div>
          <div v-else class="success-message">
            ✅ В кассе достаточно средств для возврата
          </div>
        </div>

        <div class="modal-actions">
          <button class="glass-button" @click="handleClose" title="Отмена">
            <svg-icon type="mdi" :path="mdilCancel" />
          </button>
          <button
            class="glass-button"
            @click="handleConfirm"
            :disabled="loadingBalance || !hasSufficientCash"
            title="Выполнить возврат"
          >
            <svg-icon type="mdi" :path="mdilCheck" />
          </button>
        </div>
      </div>
    </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>
