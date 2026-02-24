<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCheck, mdilCancel } from '@mdi/light-js'
import { mdiCashMultiple } from '@mdi/js'
import { useFinanceStore } from '../../stores/finance'
import AlertModal from '../AlertModal.vue'
import '../../assets/responsive.css'

const props = defineProps<{
  isVisible: boolean
  income: any | null
}>()
const emit = defineEmits(['close'])

const financeStore = useFinanceStore()
const payments = ref<any[]>([])
const paymentAmount = ref('')
const showConfirmModal = ref(false)
const showAlert = ref(false)
const alertMessage = ref('')

// Загружаем историю платежей при открытии модалки
watch(() => props.isVisible, async (visible) => {
  if (visible && props.income?.booking_id) {
    await financeStore.fetchIncomeByBooking(props.income.booking_id)
    payments.value = financeStore.incomeByBooking
  }
  if (visible && props.income) {
    const total = parseFloat(props.income.total_amount) || 0
    const paid = parseFloat(props.income.paid_amount) || 0
    paymentAmount.value = Math.round(total - paid).toString()
  }
})

// Форматирование дат (вынесено из computed)
const formatDate = (dateStr: string) => {
  if (!dateStr) return '—'
  const [datePart] = dateStr.split(' ')
  if (!datePart) return '—'
  const [year, month, day] = datePart.split('-')
  return `${day}.${month}.${year}`
}

const orderInfo = computed(() => {
  if (!props.income) return null

  const total = parseFloat(props.income.total_amount) || 0
  const paid = parseFloat(props.income.paid_amount) || 0

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
    if (!props.income.booking_id || !props.income.booking_created_at) {
      return props.income.order_number || '—'
    }

    const bookingId = props.income.booking_id
    const createdAt = props.income.booking_created_at
    const [datePart] = createdAt.split(' ')
    if (!datePart) return `МБ-${bookingId}`

    const [year, month, day] = datePart.split('-')
    const magicNumber = parseInt(day) * parseInt(month)
    const shortYear = year.slice(-2)

    return `МБ${bookingId}${magicNumber}${shortYear}`
  }

  // Статусы
  const statusMap: Record<string, string> = {
    'new': 'Новая съёмка',
    'completed': 'Съёмка состоялась',
    'delivered': 'Заказ выдан',
    'cancelled': 'Отменена',
    'cancelled_client': 'Отменил клиент',
    'cancelled_photographer': 'Отменил фотограф',
    'failed': 'Съёмка не состоялась'
  }
  const statusText = statusMap[props.income.booking_status] || props.income.booking_status

  const paymentStatusMap: Record<string, string> = {
    'unpaid': 'Не оплачена',
    'partially_paid': 'Частично оплачена',
    'fully_paid': 'Полностью оплачена'
  }
  const paymentStatusText = paymentStatusMap[props.income.payment_status] || props.income.payment_status

  // Цвет статуса оплаты
  const paymentStatusColor =
    props.income.payment_status === 'fully_paid' ? '#2E8B57' :
    props.income.payment_status === 'partially_paid' ? '#FFA500' : '#DC143C'

  return {
    orderId: formatOrderId(),
    status: statusText,
    paymentStatus: paymentStatusText,
    paymentStatusColor,
    client: props.income.client_name,
    phone: props.income.phone,
    shootingType: props.income.shooting_type_name,
    quantity: props.income.quantity || 1,
    bookingDate: formatDate(props.income.booking_date),
    shootingDate: formatDateTime(props.income.shooting_date),
    deliveryDate: formatDate(props.income.delivery_date),
    processedAt: formatDateTime(props.income.processed_at),
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
    case 'advance': return '#FFA500' // Оранжевый
    case 'balance': return '#4682B4' // Голубой
    case 'full_payment': return '#2E8B57' // Зелёный
    default: return '#333'
  }
}

function showPaymentConfirm() {
  showConfirmModal.value = true
}

async function handlePayment() {
  if (!props.income?.booking_id || !paymentAmount.value) return

  // Создаём платёж через API
  const paymentData = {
    booking_id: props.income.booking_id,
    amount: parseFloat(paymentAmount.value),
    category: 'balance', // Доплата по умолчанию
    date: new Date().toISOString().split('T')[0] // Сегодня в формате YYYY-MM-DD
  }

  try {
    const response = await fetch('/api/income.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(paymentData)
    })

    const result = await response.json()

    if (result.success) {
      // Обновляем данные в таблице (чтобы получить новый paid_amount)
      await financeStore.fetchIncome(financeStore.currentMonth)

      // Обновляем список платежей
      await financeStore.fetchIncomeByBooking(props.income.booking_id)
      payments.value = financeStore.incomeByBooking

      // Пересчитываем остаток долга для поля ввода
      const updatedIncome = financeStore.income.find(i => i.booking_id === props.income.booking_id)
      if (updatedIncome) {
        const total = parseFloat(updatedIncome.total_amount) || 0
        const paid = parseFloat(updatedIncome.paid_amount) || 0
        paymentAmount.value = Math.round(total - paid).toString()

        // Обновляем props.income для orderInfo
        Object.assign(props.income, updatedIncome)
      }

      // Закрываем модалку подтверждения
      showConfirmModal.value = false

      // НЕ закрываем главную модалку - пользователь видит обновлённые данные
    } else {
      console.error('Ошибка создания платежа:', result.error)
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
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass view-modal">
        <div class="modal-glassTitle">Информация о платеже</div>

        <div v-if="orderInfo" class="order-details">
          <!-- ID и статусы -->
          <div class="info-section">
            <div class="info-row">
              <span class="info-label">ID заказа:</span>
              <span class="info-value strong">{{ orderInfo.orderId }}</span>
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
          </div>

          <div class="divider"></div>

          <!-- Информация о клиенте и съёмке -->
          <div class="info-section">
            <h3>Клиент и съёмка</h3>
            <div class="info-row">
              <span class="info-label">Клиент:</span>
              <span class="info-value">{{ orderInfo.client }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Телефон:</span>
              <span class="info-value"><a :href="`tel:${orderInfo.phone}`">{{ orderInfo.phone }}</a></span>
            </div>
            <div class="info-row">
              <span class="info-label">Тип съёмки:</span>
              <span class="info-value">{{ orderInfo.shootingType }}</span>
            </div>
            <div class="info-row" v-if="orderInfo.quantity > 1">
              <span class="info-label">Количество:</span>
              <span class="info-value">{{ orderInfo.quantity }}</span>
            </div>
          </div>

          <div class="divider"></div>

          <!-- Даты -->
          <div class="info-section">
            <h3>Даты</h3>
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
            <div class="info-row" v-if="orderInfo.processedAt !== '—'">
              <span class="info-label">Дата выдачи (факт):</span>
              <span class="info-value">{{ orderInfo.processedAt }}</span>
            </div>
          </div>

          <div class="divider"></div>

          <!-- История платежей -->
          <div class="info-section">
            <h3>История платежей</h3>
            <div v-if="payments.length > 0">
              <div v-for="payment in payments" :key="payment.id" class="info-row">
                <span class="info-label">{{ formatDate(payment.date) }}:</span>
                <span
                  class="info-value"
                  :style="{ color: getPaymentCategoryColor(payment.category), fontWeight: 600 }"
                >
                  {{ formatPaymentCategory(payment.category) }} - {{ Math.round(parseFloat(payment.amount)) }} ₽
                </span>
              </div>
            </div>
            <div v-else class="info-row">
              <span class="info-value">Нет платежей</span>
            </div>
          </div>

          <div class="divider"></div>

          <!-- Финансы -->
          <div class="info-section">
            <h3>Финансы</h3>
            <div class="info-row">
              <span class="info-label">Итоговая сумма:</span>
              <span class="info-value strong">{{ orderInfo.total }} ₽</span>
            </div>
            <div class="info-row">
              <span class="info-label">Оплачено:</span>
              <span class="info-value paid">{{ orderInfo.paid }} ₽</span>
            </div>
            <div class="info-row" v-if="orderInfo.remaining > 0">
              <span class="info-label">Остаток:</span>
              <input
                type="number"
                v-model="paymentAmount"
                class="payment-input"
                :max="orderInfo.remaining"
                min="1"
              /> ₽
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <!-- Кнопка "Оплатить" (если статус != fully_paid) -->
          <button
            v-if="orderInfo && orderInfo.remaining > 0"
            class="glass-button"
            @click="showPaymentConfirm"
            title="Оплатить"
          >
            <svg-icon type="mdi" :path="mdiCashMultiple" />
          </button>
          <!-- Кнопка "Закрыть" -->
          <button class="glass-button" @click="emit('close')">
            <svg-icon type="mdi" :path="mdilCheck" />
          </button>
        </div>
      </div>
    </div>

    <!-- Модалка подтверждения платежа -->
    <div v-if="showConfirmModal" class="modal-overlay" @click.self="showConfirmModal = false">
      <div class="modal-glass" style="max-width: 400px;">
        <div class="modal-glassTitle">Принять платёж?</div>
        <p style="text-align: center; margin: 20px 0; color: #333; font-size: 16px;">
          Принять платёж на сумму <strong>{{ paymentAmount }} ₽</strong>?
        </p>
        <div class="modal-actions">
          <button class="glass-button" @click="showConfirmModal = false">
            <svg-icon type="mdi" :path="mdilCancel" />
          </button>
          <button class="glass-button" @click="handlePayment">
            <svg-icon type="mdi" :path="mdilCheck" />
          </button>
        </div>
      </div>
    </div>

    <!-- Модалка ошибки -->
    <AlertModal
      :is-visible="showAlert"
      :message="alertMessage"
      @close="showAlert = false"
    />
  </Teleport>
</template>
