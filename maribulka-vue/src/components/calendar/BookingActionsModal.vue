<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import {
  mdiFolderPlayOutline,
  mdiCameraOutline,
  mdiCameraOffOutline,
  mdiFileEditOutline,
  mdiCashMultiple,
  mdiEyeOutline,
  mdiTrashCanOutline,
  mdiCashRefund,
} from '@mdi/js'
import { computed } from 'vue'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const isProUser = computed(() => auth.userRole === 'prouser')

const props = defineProps<{
  booking: any
}>()

const emit = defineEmits(['close', 'view', 'edit', 'payment', 'delete', 'confirmSession', 'cancel', 'deliver', 'refund'])

const isLocked = computed(() => props.booking?.is_locked == 1)

const canEdit = computed(() => {
  if (!props.booking) return false
  return props.booking.status === 'new' && !isLocked.value
})

const canPayment = computed(() => {
  if (!props.booking) return false
  if (isLocked.value) return false
  if (props.booking.payment_status === 'fully_paid') return false
  return props.booking.status === 'new' || props.booking.status === 'in_progress'
})

const canConfirmSession = computed(() => {
  if (!props.booking) return false
  return props.booking.status === 'new' && !isLocked.value
})

const canDeliver = computed(() => {
  if (!props.booking) return false
  return props.booking.status === 'in_progress' && !isLocked.value
})

const canCancel = computed(() => {
  if (!props.booking) return false
  return props.booking.status === 'new' && !isLocked.value
})

const canDelete = computed(() => {
  if (!props.booking) return false
  if (props.booking.status !== 'new') return false
  if (isLocked.value) return false
  const paidAmount = parseFloat(props.booking.paid_amount) || 0
  return paidAmount === 0
})

const canRefund = computed(() => {
  if (!props.booking) return false
  const paidAmount = parseFloat(props.booking.paid_amount) || 0
  const isCancelled = ['cancelled', 'cancelled_client', 'cancelled_photographer', 'failed'].includes(props.booking.status)
  return isCancelled && paidAmount > 0
})
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ booking?.client_name }}</div>
        <div style="color: var(--text-secondary); font-size: 0.85em; margin-top: -8px; margin-bottom: 4px;">
          {{ booking?.shooting_type_name }}
        </div>
 
        <div class="ButtonFooter PosCenter" style="flex-direction: column; gap: 8px;">
          <button class="btnGlass iconTextStart" @click="$emit('view')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiEyeOutline" class="btn-icon" />
            <span>Просмотр</span>
          </button>

          <button class="btnGlass iconTextStart" v-if="!isProUser" :disabled="!canEdit" @click="$emit('edit')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiFileEditOutline" class="btn-icon" />
            <span>Редактировать</span>
          </button>

          <button class="btnGlass iconTextStart" :disabled="!canPayment" @click="$emit('payment')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCashMultiple" class="btn-icon" />
            <span>Оплата</span>
          </button>

          <button class="btnGlass iconTextStart" v-if="!isProUser" :disabled="!canConfirmSession" @click="$emit('confirmSession')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCameraOutline" class="btn-icon" />
            <span>Подтвердить съёмку</span>
          </button>

          <button class="btnGlass iconTextStart" v-if="!isProUser" :disabled="!canDeliver" @click="$emit('deliver')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiFolderPlayOutline" class="btn-icon" />
            <span>Выдать заказ</span>
          </button>

          <button class="btnGlass iconTextStart" v-if="!isProUser" :disabled="!canCancel" @click="$emit('cancel')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCameraOffOutline" class="btn-icon" />
            <span>Отменить</span>
          </button>

          <button class="btnGlass iconTextStart" v-if="!isProUser && canRefund" @click="$emit('refund')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCashRefund" class="btn-icon" />
            <span>Возврат средств</span>
          </button>

          <button class="btnGlass iconTextStart" v-if="!isProUser" :disabled="!canDelete" @click="$emit('delete')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiTrashCanOutline" class="btn-icon" />
            <span>Удалить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
