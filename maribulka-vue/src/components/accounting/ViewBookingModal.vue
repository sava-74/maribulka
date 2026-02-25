<script setup lang="ts">
import { computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiPrinterOutline, mdiCheckCircleOutline } from '@mdi/js'
import '../../assets/responsive.css'

const props = defineProps<{
  isVisible: boolean
  booking: any | null
}>()
const emit = defineEmits(['close'])

const handlePrint = () => {
  window.print()
}

const orderInfo = computed(() => {
  if (!props.booking) return null

  const total = parseFloat(props.booking.total_amount) || 0
  const paid = parseFloat(props.booking.paid_amount) || 0
  const basePrice = parseFloat(props.booking.base_price) || 0
  const discount = props.booking.promo_discount_percent || 0

  // ID заказа из БД
  const orderId = props.booking.order_number || `МБ-${props.booking.id}`

  // Форматирование дат (парсим строку напрямую без учета часового пояса)
  const formatDate = (dateStr: string) => {
    if (!dateStr) return '—'
    const [datePart] = dateStr.split(' ')
    if (!datePart) return '—'
    const [year, month, day] = datePart.split('-')
    return `${day}.${month}.${year}`
  }

  const formatDateTime = (dateStr: string) => {
    if (!dateStr) return '—'
    const [datePart, timePart] = dateStr.split(' ')
    if (!datePart) return '—'
    const [year, month, day] = datePart.split('-')
    const time = timePart?.substring(0, 5) || '00:00'
    return `${day}.${month}.${year} ${time}`
  }

  // Определение статуса (НОВЫЙ БИЗНЕС-ПРОЦЕСС)
  const statusMap: Record<string, string> = {
    'new': 'Новый заказ',
    'in_progress': 'В работе',
    'completed': 'Выполнен',
    'completed_partially': 'Выполнен частично',
    'not_completed': 'Не выполнен',
    'cancelled_by_photographer': 'Отменён фотографом',
    'cancelled_by_client': 'Отменён клиентом',
    'client_no_show': 'Клиент не пришёл'
  }
  const statusText = statusMap[props.booking.status] || props.booking.status

  const paymentStatusMap: Record<string, string> = {
    'unpaid': 'Не оплачена',
    'partially_paid': 'Частично оплачена',
    'fully_paid': 'Полностью оплачена'
  }
  const paymentStatusText = paymentStatusMap[props.booking.payment_status] || props.booking.payment_status

  return {
    orderId,
    status: statusText,
    paymentStatus: paymentStatusText,
    client: props.booking.client_name,
    phone: props.booking.phone,
    shootingType: props.booking.shooting_type_name,
    quantity: props.booking.quantity,
    hasPromotion: !!props.booking.promotion_id,
    promotion: props.booking.promotion_name,
    bookingDate: formatDate(props.booking.booking_date),
    shootingDate: formatDateTime(props.booking.shooting_date),
    deliveryDate: formatDate(props.booking.delivery_date),
    processedAt: formatDateTime(props.booking.processed_at),
    basePrice: Math.round(basePrice),
    discount,
    total: Math.round(total),
    paid: Math.round(paid),
    remaining: Math.round(total - paid),
    notes: props.booking.notes || ''
  }
})
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass view-modal">
        <div class="modal-glassTitle">Информация о заказе</div>

        <div v-if="orderInfo" class="order-details">
          <!-- ID и статусы -->
          <div class="info-section">
            <div class="info-row">
              <span class="info-label">ID заказа:</span>
              <span class="info-value strong">{{ orderInfo.orderId }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Статус заказа:</span>
              <span class="info-value" :class="'status-' + orderInfo.status.toLowerCase()">
                {{ orderInfo.status }}
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">Статус оплаты:</span>
              <span class="info-value">{{ orderInfo.paymentStatus }}</span>
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
            <div class="info-row" v-if="orderInfo.hasPromotion">
              <span class="info-label">Акция:</span>
              <span class="info-value">{{ orderInfo.promotion }}</span>
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

          <!-- Финансы -->
          <div class="info-section">
            <h3>Финансы</h3>
            <div class="info-row">
              <span class="info-label">Базовая стоимость:</span>
              <span class="info-value">{{ orderInfo.basePrice }} ₽</span>
            </div>
            <div class="info-row" v-if="orderInfo.discount > 0">
              <span class="info-label">Скидка:</span>
              <span class="info-value discount">-{{ orderInfo.discount }}%</span>
            </div>
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
              <span class="info-value remaining">{{ orderInfo.remaining }} ₽</span>
            </div>
          </div>

          <!-- Примечания -->
          <div v-if="orderInfo.notes" class="notes-section">
            <div class="divider"></div>
            <div class="info-section">
              <h3>Примечания</h3>
              <div class="notes-text">{{ orderInfo.notes }}</div>
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <!-- Кнопка "Печать" -->
          <button class="glass-button" @click="handlePrint" title="Печать">
            <svg-icon type="mdi" :path="mdiPrinterOutline" />
          </button>
          <!-- Кнопка "Закрыть" -->
          <button class="glass-button" @click="emit('close')">
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
