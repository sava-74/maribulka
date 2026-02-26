<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiFolderPlayOutline, mdiCameraOutline, mdiCameraOffOutline, mdiFileEditOutline, mdiCashMultiple, mdiEyeOutline, mdiTrashCanOutline} from '@mdi/js'
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

// Кнопка "Удалить" - ТОЛЬКО для "new" БЕЗ оплаты
const canDelete = computed(() => {
  if (!props.booking) return false
  if (props.booking.status !== 'new') return false
  if (isLocked.value) return false
  const paidAmount = parseFloat(props.booking.paid_amount) || 0
  return paidAmount === 0
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
            class="buttonGL buttonGL-text"
            @click="handleAction('view')"
            title="Просмотр"
          >
            <svg-icon type="mdi" :path="mdiEyeOutline"></svg-icon>
            <span>Просмотр</span>
          </button>

          <button
            class="buttonGL buttonGL-text"
            :disabled="!canEdit"
            @click="handleAction('edit')"
            title="Редактировать (только для 'new')"
          >
            <svg-icon type="mdi" :path="mdiFileEditOutline"></svg-icon>
            <span>Редактировать</span>
          </button>

          <button
            class="buttonGL buttonGL-text"
            :disabled="!canPayment"
            @click="handleAction('payment')"
            title="Оплата (для 'new' или 'in_progress')"
          >
            <svg-icon type="mdi" :path="mdiCashMultiple" ></svg-icon>
            <span>Оплата</span>
          </button>

          <!-- НОВЫЙ БИЗНЕС-ПРОЦЕСС -->

          <button
            class="buttonGL buttonGL-text"
            :disabled="!canConfirmSession"
            @click="handleAction('confirmSession')"
            title="Подтвердить съёмку (new → in_progress)"
          >
            <svg-icon type="mdi" :path="mdiCameraOutline" ></svg-icon>
            <span>Подтвердить съёмку</span>
          </button>

          <button
            class="buttonGL buttonGL-text"
            :disabled="!canDeliver"
            @click="handleAction('deliver')"
            title="Выдать заказ (in_progress → completed)"
          >
            <svg-icon type="mdi" :path="mdiFolderPlayOutline" ></svg-icon>
            <span>Выдать заказ</span>
          </button>

          <button
            class="buttonGL buttonGL-text"
            :disabled="!canCancel"
            @click="handleAction('cancel')"
            title="Отменить / Клиент не пришёл"
          >
            <svg-icon type="mdi" :path="mdiCameraOffOutline" ></svg-icon>
            <span>Отменить</span>
          </button>

          <button
            class="buttonGL-text action-danger"
            :disabled="!canDelete"
            @click="handleAction('delete')"
            title="Удалить (только для 'new' без оплаты)"
          >
            <svg-icon type="mdi" :path="mdiTrashCanOutline" ></svg-icon>
            <span>Удалить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
