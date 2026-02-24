<script setup lang="ts">
import { ref, watch } from 'vue'
import PeriodSelector from './PeriodSelector.vue'

const props = defineProps<{
  isVisible: boolean
  periodStart: Date
  periodEnd: Date
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'update', start: Date, end: Date): void
}>()

const tempStart = ref<Date>(props.periodStart)
const tempEnd = ref<Date>(props.periodEnd)

// Обновление временных значений при открытии модалки
watch(() => props.isVisible, (visible) => {
  console.log('🟡 PeriodSelectorModal isVisible изменилось:', visible)
  if (visible) {
    tempStart.value = props.periodStart
    tempEnd.value = props.periodEnd
    console.log('🟡 Модалка открыта, период:', tempStart.value, tempEnd.value)
  }
})

function handleUpdate(start: Date, end: Date) {
  console.log('🟡 PeriodSelectorModal handleUpdate:', start, end)
  tempStart.value = start
  tempEnd.value = end
}

function handleApply() {
  console.log('🟡 PeriodSelectorModal handleApply:', tempStart.value, tempEnd.value)
  emit('update', tempStart.value, tempEnd.value)
  emit('close')
}

function handleCancel() {
  console.log('🟡 PeriodSelectorModal handleCancel')
  emit('close')
}

// Закрытие по клику на backdrop
function handleBackdropClick(event: MouseEvent) {
  if (event.target === event.currentTarget) {
    handleCancel()
  }
}
</script>

<template>
  <div v-if="isVisible" class="modal-overlay" @click="handleBackdropClick">
    <div class="modal-glass" @click.stop>
      <!-- Заголовок -->
      <h2>Выбор периода</h2>

      <!-- Тело модалки -->
      <PeriodSelector
        :period-start="tempStart"
        :period-end="tempEnd"
        @update="handleUpdate"
      />

      <!-- Футер с кнопками -->
      <div class="modal-actions">
        <button class="glass-button-text" @click="handleCancel">
          Отмена
        </button>
        <button class="glass-button-text" @click="handleApply">
          Применить
        </button>
      </div>
    </div>
  </div>
</template>
