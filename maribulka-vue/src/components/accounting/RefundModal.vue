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

// Загрузка баланса кассы при открытии модалки
watch(() => props.isVisible, async (visible) => {
  if (visible) {
    const balanceData = await financeStore.fetchCashBalance()
    cashBalance.value = balanceData.balance
  } else {
    // Сбрасываем при закрытии
    cashBalance.value = null
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
        <h2>Возврат средств</h2>

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

          <!-- Предупреждение о недостатке средств -->
          <div v-if="!hasSufficientCash" class="warning-message">
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
            :disabled="!hasSufficientCash"
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

<style scoped>
.refund-info {
  margin: 20px 0;
}

.refund-amount {
  font-weight: bold;
  color: #ff5722;
  font-size: 1.1em;
}

.divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.2);
  margin: 15px 0;
}

.form-group {
  margin: 15px 0;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
}

.form-textarea {
  width: 100%;
  padding: 10px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  color: var(--text-color);
  font-family: inherit;
  font-size: 14px;
  resize: vertical;
}

.form-textarea:focus {
  outline: none;
  border-color: rgba(255, 255, 255, 0.4);
  background: rgba(255, 255, 255, 0.08);
}

.warning-message {
  padding: 10px;
  background: rgba(255, 87, 34, 0.2);
  border-left: 3px solid #ff5722;
  border-radius: 4px;
  color: #ff9800;
  font-weight: 500;
}

.success-message {
  padding: 10px;
  background: rgba(76, 175, 80, 0.2);
  border-left: 3px solid #4caf50;
  border-radius: 4px;
  color: #4caf50;
  font-weight: 500;
}
</style>
