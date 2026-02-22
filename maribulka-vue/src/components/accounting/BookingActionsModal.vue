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

const emit = defineEmits(['close', 'view', 'edit', 'payment', 'delete', 'confirmSession', 'cancel', 'deliver'])

// Проверка что заказ заблокирован (нельзя редактировать)
const isLocked = computed(() => {
  return props.booking?.is_locked == 1
})

// Кнопка "Редактировать" - только для статуса "new"
const canEdit = computed(() => {
  if (!props.booking) return false
  return props.booking.status === 'new' && !isLocked.value
})

// Кнопка "Оплата" - для "new" или "in_progress", если не fully_paid
const canPayment = computed(() => {
  if (!props.booking) return false
  if (isLocked.value) return false
  if (props.booking.payment_status === 'fully_paid') return false
  return props.booking.status === 'new' || props.booking.status === 'in_progress'
})

// Кнопка "Подтвердить съёмку" (new → in_progress)
const canConfirmSession = computed(() => {
  if (!props.booking) return false
  return props.booking.status === 'new' && !isLocked.value
})

// Кнопка "Выдать заказ" (in_progress → completed/partially/not_completed)
const canDeliver = computed(() => {
  if (!props.booking) return false
  return props.booking.status === 'in_progress' && !isLocked.value
})

// Кнопка "Отменить" / "Клиент не пришёл" - ТОЛЬКО для "new"
const canCancel = computed(() => {
  if (!props.booking) return false
  return props.booking.status === 'new' && !isLocked.value
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
            :disabled="!canEdit"
            @click="handleAction('edit')"
            title="Редактировать (только для 'new')"
          >
            <svg-icon type="mdi" :path="mdiFileEditOutline" :size="20"></svg-icon>
            <span>Редактировать</span>
          </button>

          <button
            class="glass-button-text"
            :disabled="!canPayment"
            @click="handleAction('payment')"
            title="Оплата (для 'new' или 'in_progress')"
          >
            <svg-icon type="mdi" :path="mdiCashMultiple" :size="20"></svg-icon>
            <span>Оплата</span>
          </button>

          <!-- НОВЫЙ БИЗНЕС-ПРОЦЕСС -->

          <button
            class="glass-button-text"
            :disabled="!canConfirmSession"
            @click="handleAction('confirmSession')"
            title="Подтвердить съёмку (new → in_progress)"
          >
            <svg-icon type="mdi" :path="mdiCameraOutline" :size="20"></svg-icon>
            <span>Подтвердить съёмку</span>
          </button>

          <button
            class="glass-button-text"
            :disabled="!canDeliver"
            @click="handleAction('deliver')"
            title="Выдать заказ (in_progress → completed)"
          >
            <svg-icon type="mdi" :path="mdiFolderPlayOutline" :size="20"></svg-icon>
            <span>Выдать заказ</span>
          </button>

          <button
            class="glass-button-text"
            :disabled="!canCancel"
            @click="handleAction('cancel')"
            title="Отменить / Клиент не пришёл"
          >
            <svg-icon type="mdi" :path="mdiCameraOffOutline" :size="20"></svg-icon>
            <span>Отменить</span>
          </button>

          <button
            class="glass-button-text action-danger"
            :disabled="isLocked"
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
