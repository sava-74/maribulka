<script setup lang="ts">
import { computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline } from '@mdi/js'

const props = defineProps<{
  isVisible: boolean
  expense: any | null
}>()
const emit = defineEmits(['close'])

// Форматирование даты
const formatDate = (dateStr: string) => {
  if (!dateStr) return '—'
  const [datePart] = dateStr.split(' ')
  if (!datePart) return '—'
  const [year, month, day] = datePart.split('-')
  return `${day}.${month}.${year}`
}

const expenseInfo = computed(() => {
  if (!props.expense) return null

  return {
    date: formatDate(props.expense.date),
    amount: Math.round(parseFloat(props.expense.amount)),
    category: props.expense.category_name || '—',
    description: props.expense.description || '—',
    bookingId: props.expense.booking_id
  }
})
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass view-modal">
        <div class="modal-glassTitle">Информация о расходе</div>

        <div v-if="expenseInfo" class="order-details">
          <div class="info-section">
            <div class="info-row">
              <span class="info-label">Дата:</span>
              <span class="info-value">{{ expenseInfo.date }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Сумма:</span>
              <span class="info-value strong">{{ expenseInfo.amount }} ₽</span>
            </div>
            <div class="info-row">
              <span class="info-label">Категория:</span>
              <span class="info-value">{{ expenseInfo.category }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Описание:</span>
              <span class="info-value">{{ expenseInfo.description }}</span>
            </div>
            <div class="info-row" v-if="expenseInfo.bookingId">
              <span class="info-label">Связано с заказом:</span>
              <span class="info-value">#{{ expenseInfo.bookingId }}</span>
            </div>
          </div>
        </div>

        <div class="ButtonFooter PosRight">
          <button class="buttonGL buttonGL-textFix" @click="emit('close')">
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
            <span>Закрыть</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
