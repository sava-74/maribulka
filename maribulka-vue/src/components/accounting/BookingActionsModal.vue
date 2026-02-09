<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilEye, mdilDelete } from '@mdi/light-js'
import { mdiFolderPlayOutline, mdiCameraOutline, mdiCameraOffOutline, mdiFileEditOutline, mdiCashMultiple } from '@mdi/js'
import { computed } from 'vue'
import '../../assets/modal.css'

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
    <div v-if="isVisible" class="booking-actions-overlay" @click.self="$emit('close')">
      <div
        class="booking-actions-modal"
        :style="{ left: position.x + 'px', top: position.y + 'px' }"
      >
        <div class="booking-actions-header">
          <span class="booking-title">{{ booking?.client_name }}</span>
          <span class="booking-type">{{ booking?.shooting_type_name }}</span>
        </div>

        <div class="booking-actions-buttons">
          <button
            class="action-btn"
            @click="handleAction('view')"
            title="Просмотр"
          >
            <svg-icon type="mdi" :path="mdilEye" :size="20"></svg-icon>
            <span>Просмотр</span>
          </button>

          <button
            class="action-btn"
            :disabled="isDelivered || booking?.status === 'completed'"
            @click="handleAction('edit')"
            title="Редактировать"
          >
            <svg-icon type="mdi" :path="mdiFileEditOutline" :size="20"></svg-icon>
            <span>Редактировать</span>
          </button>

          <button
            class="action-btn"
            :disabled="isDelivered || booking?.payment_status === 'fully_paid'"
            @click="handleAction('payment')"
            title="Оплата"
          >
            <svg-icon type="mdi" :path="mdiCashMultiple" :size="20"></svg-icon>
            <span>Оплата</span>
          </button>

          <button
            class="action-btn"
            :disabled="!canMarkCompleted"
            @click="handleAction('complete')"
            title="Съёмка состоялась"
          >
            <svg-icon type="mdi" :path="mdiCameraOutline" :size="20"></svg-icon>
            <span>Состоялась</span>
          </button>

          <button
            class="action-btn"
            :disabled="isDelivered || booking?.status === 'completed'"
            @click="handleAction('cancel')"
            title="Отменить"
          >
            <svg-icon type="mdi" :path="mdiCameraOffOutline" :size="20"></svg-icon>
            <span>Отменить</span>
          </button>

          <button
            class="action-btn"
            :disabled="!canDeliver"
            @click="handleAction('deliver')"
            title="Провести"
          >
            <svg-icon type="mdi" :path="mdiFolderPlayOutline" :size="20"></svg-icon>
            <span>Провести</span>
          </button>

          <button
            class="action-btn danger"
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

<style scoped>
.booking-actions-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
  background: rgba(0, 0, 0, 0.3);
}

.booking-actions-modal {
  position: absolute;
  background: rgba(30, 30, 40, 0.95);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 12px;
  min-width: 180px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
  z-index: 1001;
}

.booking-actions-header {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding-bottom: 10px;
  margin-bottom: 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.booking-title {
  font-weight: 600;
  color: #fff;
  font-size: 14px;
}

.booking-type {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
}

.booking-actions-buttons {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  background: transparent;
  border: none;
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.9);
  cursor: pointer;
  transition: all 0.2s;
  font-size: 13px;
  text-align: left;
}

.action-btn:hover:not(:disabled) {
  background: rgba(57, 255, 20, 0.15);
  color: #39FF14;
}

.action-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.action-btn.danger:hover:not(:disabled) {
  background: rgba(255, 68, 68, 0.15);
  color: #ff4444;
}
</style>
