<script setup lang="ts">
import { computed, ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCurrencyRub, mdiCloseCircleOutline } from '@mdi/js'
import { useBookingsStore } from '../../stores/bookings'
import AlertModal from '../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  booking: any | null
}>()

const emit = defineEmits(['close', 'openPayment'])

const bookingsStore = useBookingsStore()

// Alert modal
const showAlert = ref(false)
const alertMessage = ref('')
const alertTitle = ref('Ошибка')

const orderInfo = computed(() => {
  if (!props.booking) return null
  const total = parseFloat(props.booking.total_amount) || 0
  const paid = parseFloat(props.booking.paid_amount) || 0
  const hasPrepayment = paid > 0

  // ID заказа из БД
  const orderId = props.booking.order_number || `МБ-${props.booking.id}`

  return {
    orderId,
    client: props.booking.client_name,
    shootingDate: props.booking.shooting_date,
    shootingType: props.booking.shooting_type_name,
    total: Math.round(total),
    paid: Math.round(paid),
    hasPrepayment
  }
})

const handleConfirm = async () => {
  if (!props.booking) return

  // Проверяем предоплату
  if (!orderInfo.value?.hasPrepayment) {
    // Открываем модалку оплаты
    emit('openPayment', props.booking.id)
    return
  }

  // Подтверждаем съёмку
  const result = await bookingsStore.confirmSession(props.booking.id)
  if (result.success) {
    emit('close')
  } else {
    alertTitle.value = 'Ошибка'
    alertMessage.value = result.error || 'Не удалось подтвердить съёмку'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay-main" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Подтвердить съёмку</div>

        <div v-if="orderInfo" class="confirm-info">
          <p><strong>ID заказа:</strong> {{ orderInfo.orderId }}</p>
          <p><strong>Клиент:</strong> {{ orderInfo.client }}</p>
          <p><strong>Дата съёмки:</strong> {{ orderInfo.shootingDate }}</p>
          <p><strong>Тип съёмки:</strong> {{ orderInfo.shootingType }}</p>

          <div class="divider"></div>

          <p><strong>Стоимость:</strong> {{ orderInfo.total }} ₽</p>
          <p><strong>Оплачено:</strong> {{ orderInfo.paid }} ₽</p>

          <div class="divider"></div>

          <p v-if="orderInfo.hasPrepayment" class="prepayment-ok">
            ✅ Предоплата внесена
          </p>
          <p v-else class="prepayment-warning">
            ⚠️ Требуется внести предоплату
          </p>

          <div class="info-box">
            <p><strong>Что произойдёт:</strong></p>
            <ul>
              <li v-if="!orderInfo.hasPrepayment">Откроется окно приёма платежа</li>
              <li v-else>Статус заказа изменится на "В работе"</li>
            </ul>
          </div>
        </div>

        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Закрыть</span>
          </button>

          <!-- Кнопка "Оплатить" - если нет предоплаты -->
          <button
            v-if="orderInfo && !orderInfo.hasPrepayment"
            class="btnGlass iconText"
            @click="handleConfirm"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCurrencyRub" class="btn-icon" />
            <span>Оплатить</span>
          </button>

          <!-- Кнопка "Подтвердить" - если есть предоплата -->
          <button
            v-if="orderInfo && orderInfo.hasPrepayment"
            class="btnGlass iconText"
            @click="handleConfirm"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Подтвердить</span>
          </button>
        </div>
      </div>
    </div>
    <AlertModal :isVisible="showAlert" :message="alertMessage" :title="alertTitle" @close="showAlert = false" />
  </Teleport>
</template>
