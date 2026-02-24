<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import flatpickr from 'flatpickr'
import { Russian } from 'flatpickr/dist/l10n/ru.js'
import 'flatpickr/dist/flatpickr.min.css'

const props = defineProps<{
  periodStart: Date
  periodEnd: Date
}>()

const emit = defineEmits<{
  (e: 'update', start: Date, end: Date): void
}>()

const dateInput = ref<HTMLInputElement | null>(null)
let flatpickrInstance: any = null

// Пресеты периодов
const presets = [
  {
    label: 'Текущий месяц',
    getValue: () => {
      const now = new Date()
      const start = new Date(now.getFullYear(), now.getMonth(), 1)
      const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
      return [start, end]
    }
  },
  {
    label: 'Прошлый месяц',
    getValue: () => {
      const now = new Date()
      const start = new Date(now.getFullYear(), now.getMonth() - 1, 1)
      const end = new Date(now.getFullYear(), now.getMonth(), 0)
      return [start, end]
    }
  },
  {
    label: 'Последние 3 месяца',
    getValue: () => {
      const now = new Date()
      const start = new Date(now.getFullYear(), now.getMonth() - 2, 1)
      const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
      return [start, end]
    }
  },
  {
    label: 'Текущий год',
    getValue: () => {
      const now = new Date()
      const start = new Date(now.getFullYear(), 0, 1)
      const end = new Date(now.getFullYear(), 11, 31)
      return [start, end]
    }
  }
]

onMounted(() => {
  if (!dateInput.value) return

  flatpickrInstance = flatpickr(dateInput.value, {
    mode: 'range',
    locale: Russian,
    dateFormat: 'd.m.y',
    defaultDate: [props.periodStart, props.periodEnd],
    onChange: (selectedDates: Date[]) => {
      if (selectedDates.length === 2 && selectedDates[0] && selectedDates[1]) {
        emit('update', selectedDates[0], selectedDates[1])
      }
    }
  })
})

// Применить пресет
function applyPreset(preset: typeof presets[0]) {
  const dates = preset.getValue()
  const start = dates[0]
  const end = dates[1]

  if (!start || !end) return

  if (flatpickrInstance) {
    flatpickrInstance.setDate([start, end])
  }
  emit('update', start, end)
}

// Обновление при изменении props
watch([() => props.periodStart, () => props.periodEnd], () => {
  if (flatpickrInstance) {
    flatpickrInstance.setDate([props.periodStart, props.periodEnd])
  }
})
</script>

<template>
  <div class="period-selector">
    <!-- Быстрые пресеты -->
    <div class="period-presets">
      <button
        v-for="preset in presets"
        :key="preset.label"
        class="glass-button-text preset-button"
        @click="applyPreset(preset)"
      >
        {{ preset.label }}
      </button>
    </div>

    <!-- Поле ввода для flatpickr -->
    <div class="period-input-wrapper">
      <label class="period-label">Произвольный период:</label>
      <input
        ref="dateInput"
        type="text"
        class="period-input"
        placeholder="Выберите период"
        readonly
      />
    </div>
  </div>
</template>

<style scoped>
.period-selector {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 20px;
}

.period-presets {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.preset-button {
  min-width: 140px;
  height: 40px;
}

.period-input-wrapper {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.period-label {
  font-size: 14px;
  color: var(--text-color);
  font-weight: 500;
}

.period-input {
  width: 100%;
  height: 40px;
  padding: 0 15px;
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
  background: var(--glass-bg);
  color: var(--text-color);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.period-input:hover {
  border-color: var(--accent-color);
}

.period-input:focus {
  outline: none;
  border-color: var(--accent-color);
  box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
}

/* Стили для flatpickr */
:deep(.flatpickr-calendar) {
  background: var(--glass-bg);
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

:deep(.flatpickr-months) {
  background: transparent;
}

:deep(.flatpickr-current-month) {
  color: var(--text-color);
}

:deep(.flatpickr-weekday) {
  color: var(--text-secondary);
}

:deep(.flatpickr-day) {
  color: var(--text-color);
  border-radius: var(--border-radius);
}

:deep(.flatpickr-day:hover) {
  background: var(--glass-hover);
  border-color: var(--accent-color);
}

:deep(.flatpickr-day.selected) {
  background: var(--accent-color);
  border-color: var(--accent-color);
  color: white;
}

:deep(.flatpickr-day.inRange) {
  background: rgba(74, 144, 226, 0.2);
  border-color: transparent;
}

:deep(.flatpickr-day.today) {
  border-color: var(--accent-color);
}
</style>
