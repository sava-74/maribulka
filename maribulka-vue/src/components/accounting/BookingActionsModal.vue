<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilEye, mdilDelete } from '@mdi/light-js'
import { mdiFolderPlayOutline, mdiCameraOutline, mdiCameraOffOutline, mdiFileEditOutline, mdiCashMultiple } from '@mdi/js'
import { computed } from 'vue'
import '../../assets/modal.css'
import '../../assets/buttons.css'

const props = defineProps<{
  isVisible: boolean
  booking: any
  position: { x: number; y: number }
}>()

const emit = defineEmits(['close', 'view', 'edit', 'payment', 'delete', 'complete', 'cancel', 'deliver'])

// Проверка что заказ проведён или отменён (нельзя редактировать)
const isDelivered = computed(() => {
  const status = props.booking?.status
  return status === 'delivered' || status === 'cancelled_client' || status === 'cancelled_photographer'
})

// Проверка что можно отметить "Съёмка состоялась"
const canMarkCompleted = computed(() => {
  if (!props.booking) return false
  const status = props.booking.status
  if (status === 'cancelled') return false
  if (status !== 'new' && status !== 'failed') return false
  const shootingDate = new Date(props.booking.shooting_date)
  shootingDate.setHours(0, 0, 0, 0)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return shootingDate.getTime() <= today.getTime()
})

// Проверка что можно выдать (статус "состоялась" и не проведено)
const canDeliver = computed(() => {
  if (!props.booking) return false
  if (isDelivered.value) return false
  return props.booking.status === 'completed'
})

function handleAction(action: string) {
  emit(action as any)
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-glass actions-modal">
        <div class="actions-header">
          <span class="actions-title">{{ booking?.client_name }}</span>
          <span class="actions-type">{{ booking?.shooting_type_name }}</span>
        </div>

        <div class="actions-buttons">
          <button
            class="glass-button-text"
            @click="handleAction('view')"
            title="Просмотр"
          >
            <svg-icon type="mdi" :path="mdilEye" :size="20"></svg-icon>
            <span>Просмотр</span>
          </button>

          <button
            class="glass-button-text"
            :disabled="isDelivered || booking?.status === 'completed'"
            @click="handleAction('edit')"
            title="Редактировать"
          >
            <svg-icon type="mdi" :path="mdiFileEditOutline" :size="20"></svg-icon>
            <span>Редактировать</span>
          </button>

          <button
            class="glass-button-text"
            :disabled="isDelivered || booking?.payment_status === 'fully_paid'"
            @click="handleAction('payment')"
            title="Оплата"
          >
            <svg-icon type="mdi" :path="mdiCashMultiple" :size="20"></svg-icon>
            <span>Оплата</span>
          </button>

          <button
            class="glass-button-text"
            :disabled="!canMarkCompleted"
            @click="handleAction('complete')"
            title="Съёмка состоялась"
          >
            <svg-icon type="mdi" :path="mdiCameraOutline" :size="20"></svg-icon>
            <span>Состоялась</span>
          </button>

          <button
            class="glass-button-text"
            :disabled="isDelivered || booking?.status === 'completed'"
            @click="handleAction('cancel')"
            title="Отменить"
          >
            <svg-icon type="mdi" :path="mdiCameraOffOutline" :size="20"></svg-icon>
            <span>Отменить</span>
          </button>

          <button
            class="glass-button-text"
            :disabled="!canDeliver"
            @click="handleAction('deliver')"
            title="Провести"
          >
            <svg-icon type="mdi" :path="mdiFolderPlayOutline" :size="20"></svg-icon>
            <span>Провести</span>
          </button>

          <button
            class="glass-button-text action-danger"
            :disabled="isDelivered || booking?.status === 'completed'"
            @click="handleAction('delete')"
            title="Удалить"
          >
            <svg-icon type="mdi" :path="mdilDelete" :size="20"></svg-icon>
            <span>Удалить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
