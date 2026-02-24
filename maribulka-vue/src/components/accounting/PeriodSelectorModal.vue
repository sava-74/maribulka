<script setup lang="ts">
import { ref, watch } from 'vue'
import PeriodSelector from './PeriodSelector.vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiClose } from '@mdi/js'

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
  if (visible) {
    tempStart.value = props.periodStart
    tempEnd.value = props.periodEnd
  }
})

function handleUpdate(start: Date, end: Date) {
  tempStart.value = start
  tempEnd.value = end
}

function handleApply() {
  emit('update', tempStart.value, tempEnd.value)
  emit('close')
}

function handleCancel() {
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
  <Transition name="modal">
    <div v-if="isVisible" class="modal-backdrop" @click="handleBackdropClick">
      <div class="modal-content period-modal">
        <!-- Заголовок -->
        <div class="modal-header">
          <h2 class="modal-title">Выбор периода</h2>
          <button class="modal-close" @click="handleCancel" title="Закрыть">
            <svg-icon type="mdi" :path="mdiClose"></svg-icon>
          </button>
        </div>

        <!-- Тело модалки -->
        <div class="modal-body">
          <PeriodSelector
            :period-start="tempStart"
            :period-end="tempEnd"
            @update="handleUpdate"
          />
        </div>

        <!-- Футер с кнопками -->
        <div class="modal-footer">
          <button class="glass-button-text" @click="handleCancel">
            Отмена
          </button>
          <button class="glass-button-text primary" @click="handleApply">
            Применить
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.period-modal {
  max-width: 600px;
  width: 90%;
}

.modal-body {
  padding: 0;
  max-height: 70vh;
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid var(--border-color);
}

.primary {
  background: var(--accent-color);
  color: white;
}

.primary:hover {
  background: var(--accent-hover);
}
</style>
