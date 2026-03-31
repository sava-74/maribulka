<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline } from '@mdi/js'

const props = defineProps<{
  isVisible: boolean
  expense: any | null
}>()

const emit = defineEmits(['close'])

function formatDate(dateStr: string) {
  if (!dateStr) return '—'
  const part = dateStr.split('T')[0]?.split(' ')[0]
  if (!part) return '—'
  const [year, month, day] = part.split('-')
  return `${day}.${month}.${year}`
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Просмотр расхода</div>

        <div v-if="expense">
          <div class="info-row">
            <span class="info-label">ID:</span>
            <span class="info-value">{{ expense.id }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Дата:</span>
            <span class="info-value">{{ formatDate(expense.date) }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Категория:</span>
            <span class="info-value">{{ expense.category_name ?? '—' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Сумма:</span>
            <span class="info-value strong">{{ Math.round(parseFloat(expense.amount)) }} ₽</span>
          </div>
          <div class="info-row">
            <span class="info-label">Описание:</span>
            <span class="info-value">{{ expense.description || '—' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Выдал:</span>
            <span class="info-value">{{ expense.created_by_name || '—' }}</span>
          </div>
        </div>

        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Закрыть</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
