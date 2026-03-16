<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'

import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline, mdiCashMultiple } from '@mdi/js'
import { useFinanceStore } from '../../../stores/finance'
import AlertModal from '../../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  income: any | null
  dateFrom: string
  dateTo: string
}>()
const emit = defineEmits(['close'])

const financeStore = useFinanceStore()
const localIncome = ref<any>(null)
const payments = computed(() => financeStore.incomeByBooking)
const paymentAmount = ref('')
const showConfirmModal = ref(false)
const showAlert = ref(false)
const alertMessage = ref('')

watch(() => props.income, (val) => {
  if (val) localIncome.value = { ...val }
}, { immediate: true })

// Загружаем историю платежей при открытии модалки
onMounted(async () => {
  if (props.income?.booking_id) {
    await financeStore.fetchIncomeByBooking(props.income.booking_id)
  }
  if (props.income) {
    const total = parseFloat(props.income.total_amount) || 0
    const paid = parseFloat(props.income.paid_amount) || 0
    paymentAmount.value = Math.round(total - paid).toString()
  }
})

// Форматирование даты
const formatDate = (dateStr: string) => {
  if (!dateStr) return '—'
  const [datePart] = dateStr.split(' ')
  if (!datePart) return '—'
  const [year, month, day] = datePart.split('-')
  return `${day}.${month}.${year}`
}

const bookingStatusMap: Record<string, string> = {
  'new': '🔵 Новый',
  'in_progress': '🟠 В работе',
  'completed': '🟢 Выполнен',
  'completed_partially': '🟡 Выполнен частично',
  'not_completed': '🟤 Не выполнен',
  'cancelled_by_client': '⚪ Отменён клиентом',
  'cancelled_by_photographer': '⚪ Отменён фотографом',
  'client_no_show': '⚪ Клиент не пришёл',
}

const paymentStatusMap: Record<string, string> = {
  'unpaid': '🔴 Не оплачено',
  'partially_paid': '🟡 Частично',
  'fully_paid': '🟢 Оплачено',
}

const orderInfo = computed(() => {
  if (!localIncome.value) return null

  const total = parseFloat(localIncome.value.total_amount) || 0
  const paid = parseFloat(localIncome.value.paid_amount) || 0

  const formatDateTime = (dateStr: string) => {
    if (!dateStr) return '—'
    const [datePart, timePart] = dateStr.split(' ')
    if (!datePart) return '—'
    const [year, month, day] = datePart.split('-')
    const time = timePart?.substring(0, 5) || '00:00'
    return `${day}.${month}.${year} ${time}`
  }

  // ID заказа: МБ{id}{magicNumber}{year}
  const formatOrderId = () => {
    if (!localIncome.value.booking_id || !localIncome.value.booking_created_at) {
      return localIncome.value.order_number || '—'
    }
    const bookingId = localIncome.value.booking_id
    const createdAt = localIncome.value.booking_created_at
    const [datePart] = createdAt.split(' ')
    if (!datePart) return `МБ-${bookingId}`
    const [year, month, day] = datePart.split('-')
    const magicNumber = parseInt(day) * parseInt(month)
    const shortYear = year.slice(-2)
    return `МБ${bookingId}${magicNumber}${shortYear}`
  }

  const statusText = bookingStatusMap[localIncome.value.booking_status] || localIncome.value.booking_status
  const paymentStatusText = paymentStatusMap[localIncome.value.payment_status] || localIncome.value.payment_status

  const paymentStatusColor =
    localIncome.value.payment_status === 'fully_paid' ? '#2E8B57' :
    localIncome.value.payment_status === 'partially_paid' ? '#FFA500' : '#DC143C'

  return {
    orderId: formatOrderId(),
    status: statusText,
    paymentStatus: paymentStatusText,
    paymentStatusColor,
    client: localIncome.value.client_name,
    phone: localIncome.value.phone,
    shootingType: localIncome.value.shooting_type_name,
    quantity: localIncome.value.quantity || 1,
    bookingDate: formatDate(localIncome.value.booking_date),
    shootingDate: formatDateTime(localIncome.value.shooting_date),
    deliveryDate: formatDate(localIncome.value.delivery_date),
    processedAt: formatDateTime(localIncome.value.processed_at),
    total: Math.round(total),
    paid: Math.round(paid),
    remaining: Math.round(total - paid)
  }
})

// Форматирование категории платежа
function formatPaymentCategory(category: string) {
  switch (category) {
    case 'advance': return 'Аванс'
    case 'balance': return 'Доплата'
    case 'full_payment': return 'Оплачено'
    default: return category
  }
}

// Цвет категории платежа
function getPaymentCategoryColor(category: string) {
  switch (category) {
    case 'advance': return '#FFA500'
    case 'balance': return '#4682B4'
    case 'full_payment': return '#2E8B57'
    default: return 'inherit'
  }
}

function showPaymentConfirm() {
  showConfirmModal.value = true
}

async function handlePayment() {
  if (!props.income?.booking_id || !paymentAmount.value) return

  const paymentData = {
    booking_id: props.income.booking_id,
    amount: parseFloat(paymentAmount.value),
    category: 'balance',
    date: new Date().toISOString().split('T')[0]
  }

  try {
    const response = await fetch('/api/income.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(paymentData)
    })

    const result = await response.json()

    if (result.success) {
      await financeStore.fetchIncome(props.dateFrom, props.dateTo)

      await financeStore.fetchIncomeByBooking(props.income.booking_id)

      const updatedIncome = financeStore.income.find((i: any) => i.booking_id === props.income.booking_id)
      if (updatedIncome) {
        localIncome.value = { ...updatedIncome }
        const total = parseFloat(updatedIncome.total_amount) || 0
        const paid = parseFloat(updatedIncome.paid_amount) || 0
        paymentAmount.value = Math.round(total - paid).toString()
      }

      showConfirmModal.value = false
    } else {
      alertMessage.value = 'Ошибка: ' + (result.error || 'Не удалось создать платёж')
      showAlert.value = true
      showConfirmModal.value = false
    }
  } catch (error) {
    console.error('Ошибка запроса:', error)
    alertMessage.value = 'Ошибка сети'
    showAlert.value = true
    showConfirmModal.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <!-- Основная модалка просмотра — 2-й порядок, z-index 999 -->
    <div v-if="isVisible" class="modal-overlay-main" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Информация о платеже</div>

        <!-- Взнос в кассу (без привязки к заказу) -->
        <div v-if="localIncome && !localIncome.booking_id">
          <div class="info-row">
            <span class="info-label">Дата:</span>
            <span class="info-value">{{ formatDate(localIncome.date) }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Вносит:</span>
            <span class="info-value">{{ localIncome.client_name || '—' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Сумма:</span>
            <span class="info-value" style="font-weight:600">{{ Math.round(parseFloat(localIncome.amount)) }} ₽</span>
          </div>
          <div class="info-row">
            <span class="info-label">Примечания:</span>
            <span class="info-value">{{ localIncome.notes || '—' }}</span>
          </div>
        </div>

        <!-- Платёж по заказу -->
        <div v-else-if="orderInfo">
          <!-- ID и статусы -->
          <div class="info-row">
            <span class="info-label">ID заказа:</span>
            <span class="info-value" style="font-weight:600">{{ orderInfo.orderId }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Статус заказа:</span>
            <span class="info-value">{{ orderInfo.status }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Статус оплаты:</span>
            <span class="info-value" :style="{ color: orderInfo.paymentStatusColor, fontWeight: 600 }">
              {{ orderInfo.paymentStatus }}
            </span>
          </div>

          <!-- Клиент и съёмка -->
          <div class="info-row" style="margin-top:8px">
            <span class="info-label" style="font-weight:600">Клиент и съёмка</span>
          </div>
          <div class="info-row">
            <span class="info-label">Клиент:</span>
            <span class="info-value">{{ orderInfo.client }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Телефон:</span>
            <span class="info-value">
              <a :href="`tel:${orderInfo.phone}`">{{ orderInfo.phone }}</a>
            </span>
          </div>
          <div class="info-row">
            <span class="info-label">Тип съёмки:</span>
            <span class="info-value">{{ orderInfo.shootingType }}</span>
          </div>
          <div v-if="orderInfo.quantity > 1" class="info-row">
            <span class="info-label">Количество:</span>
            <span class="info-value">{{ orderInfo.quantity }}</span>
          </div>

          <!-- Даты -->
          <div class="info-row" style="margin-top:8px">
            <span class="info-label" style="font-weight:600">Даты</span>
          </div>
          <div class="info-row">
            <span class="info-label">Дата создания:</span>
            <span class="info-value">{{ orderInfo.bookingDate }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Дата съёмки:</span>
            <span class="info-value">{{ orderInfo.shootingDate }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Дата выдачи (план):</span>
            <span class="info-value">{{ orderInfo.deliveryDate }}</span>
          </div>
          <div v-if="orderInfo.processedAt !== '—'" class="info-row">
            <span class="info-label">Дата выдачи (факт):</span>
            <span class="info-value">{{ orderInfo.processedAt }}</span>
          </div>

          <!-- История платежей -->
          <div class="info-row" style="margin-top:8px">
            <span class="info-label" style="font-weight:600">История платежей</span>
          </div>
          <div v-if="payments.length > 0">
            <div v-for="payment in payments" :key="payment.id" class="info-row">
              <span class="info-label">{{ formatDate(payment.date) }}:</span>
              <span
                class="info-value"
                :style="{ color: getPaymentCategoryColor(payment.category), fontWeight: 600 }"
              >
                {{ formatPaymentCategory(payment.category) }} — {{ Math.round(parseFloat(payment.amount)) }} ₽
              </span>
            </div>
          </div>
          <div v-else class="info-row">
            <span class="info-value">Нет платежей</span>
          </div>

          <!-- Финансы -->
          <div class="info-row" style="margin-top:8px">
            <span class="info-label" style="font-weight:600">Финансы</span>
          </div>
          <div class="info-row">
            <span class="info-label">Итоговая сумма:</span>
            <span class="info-value" style="font-weight:600">{{ orderInfo.total }} ₽</span>
          </div>
          <div class="info-row">
            <span class="info-label">Оплачено:</span>
            <span class="info-value" :style="{ color: '#2E8B57', fontWeight: 600 }">{{ orderInfo.paid }} ₽</span>
          </div>
          <div v-if="orderInfo.remaining > 0" class="info-row">
            <span class="info-label">Остаток:</span>
            <input
              type="number"
              v-model="paymentAmount"
              class="modal-input"
              :max="orderInfo.remaining"
              min="1"
            /> ₽
          </div>
        </div>

        <div class="ButtonFooter PosRight">
          <button
            v-if="localIncome?.booking_id && orderInfo && orderInfo.remaining > 0"
            class="btnGlass iconText"
            @click="showPaymentConfirm"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCashMultiple" class="btn-icon" />
            <span>Оплатить</span>
          </button>
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Закрыть</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Модалка подтверждения платежа — 1-й порядок, z-index 9999 -->
    <div v-if="showConfirmModal" class="modal-overlay" @click.self="showConfirmModal = false">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Принять платёж?</div>
        <p>Принять платёж на сумму <strong>{{ paymentAmount }} ₽</strong>?</p>
        <div class="ButtonFooter PosCenter">
          <button class="btnGlass iconText" @click="showConfirmModal = false">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="handlePayment">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Принять</span>
          </button>
        </div>
      </div>
    </div>

    <!-- AlertModal для ошибок -->
    <AlertModal
      :is-visible="showAlert"
      :message="alertMessage"
      @close="showAlert = false"
    />
  </Teleport>
</template>
