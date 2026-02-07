<script setup lang="ts">
import { computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCheck } from '@mdi/light-js'
import { mdiPrinterOutline } from '@mdi/js'

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

  // Формируем ID заказа: МБ-{id}.{year}
  const createdAt = props.booking.created_at || ''
  const year = createdAt.slice(0, 4)
  const orderId = `МБ-${props.booking.id}.${year}`

  // Форматирование дат
  const formatDate = (dateStr: string) => {
    if (!dateStr) return '—'
    const date = new Date(dateStr)
    return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' })
  }

  const formatDateTime = (dateStr: string) => {
    if (!dateStr) return '—'
    const date = new Date(dateStr)
    return date.toLocaleString('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  // Определение статуса
  const statusMap: Record<string, string> = {
    'new': 'Новая',
    'completed': 'Завершена',
    'delivered': 'Проведено',
    'cancelled': 'Отменена',
    'cancelled_client': 'Отменил клиент',
    'cancelled_photographer': 'Отменил фотограф',
    'failed': 'Не состоялась'
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
    shootingDate: formatDate(props.booking.shooting_date),
    deliveryDate: formatDate(props.booking.delivery_date),
    processedAt: formatDateTime(props.booking.processed_at),
    basePrice: Math.round(basePrice),
    discount,
    total: Math.round(total),
    paid: Math.round(paid),
    remaining: Math.round(total - paid)
  }
})
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass view-modal">
        <h2>Информация о заказе</h2>

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
              <span class="info-value">{{ orderInfo.phone }}</span>
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
        </div>

        <div class="modal-actions">
          <button class="glass-button" @click="handlePrint" title="Печать">
            <svg-icon type="mdi" :path="mdiPrinterOutline" />
          </button>
          <button class="glass-button" @click="emit('close')">
            <svg-icon type="mdi" :path="mdilCheck" />
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.view-modal {
  max-width: 600px;
  max-height: 80vh;
  overflow-y: auto;
}

.order-details {
  margin: 2px 0;
  color: #333;
}

.info-section {
  margin-bottom: 2px;
}

.info-section h3 {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 2px;
  color: #1f2937;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 2px 0;
  font-size: 13px;
}

.info-label {
  color: #6b7280;
  font-weight: 500;
}

.info-value {
  color: #111827;
  text-align: right;
  max-width: 60%;
  word-wrap: break-word;
  font-size: 13px;
}

.info-value.strong {
  font-weight: 600;
  font-size: 15px;
}

.info-value.discount {
  color: #dc2626;
}

.info-value.paid {
  color: #059669;
  font-weight: 600;
}

.info-value.remaining {
  color: #dc2626;
  font-weight: 600;
}

.info-value.status-проведено {
  color: #059669;
  font-weight: 600;
}

.info-value.status-отменена {
  color: #dc2626;
  font-weight: 600;
}

.divider {
  height: 1px;
  background: rgba(0, 0, 0, 0.1);
  margin: 2px 0;
}

/* Scrollbar styling */
.view-modal::-webkit-scrollbar {
  width: 8px;
}

.view-modal::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 4px;
}

.view-modal::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 4px;
}

.view-modal::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.3);
}

/* Стили для печати А5 */
@media print {
  @page {
    size: A5 portrait;
    margin: 10mm;
  }

  body * {
    visibility: hidden;
  }

  .modal-overlay,
  .modal-overlay * {
    visibility: visible;
  }

  .modal-overlay {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    background: white !important;
  }

  .modal-glass {
    position: static;
    width: 100%;
    max-width: none;
    max-height: none;
    background: white !important;
    box-shadow: none !important;
    border: none !important;
    padding: 0 !important;
  }

  .view-modal {
    max-height: none;
    overflow: visible;
  }

  .modal-actions {
    display: none !important;
  }

  .order-details {
    color: black !important;
  }

  .info-label,
  .info-value {
    color: black !important;
  }

  h2 {
    color: black !important;
    font-size: 16pt;
    margin-bottom: 10pt;
  }

  .info-section h3 {
    color: black !important;
    font-size: 12pt;
  }

  .info-row {
    font-size: 10pt;
    padding: 1pt 0;
  }

  .divider {
    background: black !important;
  }
}
</style>
