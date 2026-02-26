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
  console.log('🟣 PeriodSelector onMounted')
  if (!dateInput.value) {
    console.log('🟣 dateInput.value пусто!')
    return
  }

  flatpickrInstance = flatpickr(dateInput.value, {
    mode: 'range',
    locale: Russian,
    dateFormat: 'd.m.y',
    defaultDate: [props.periodStart, props.periodEnd],
    onChange: (selectedDates: Date[]) => {
      console.log('🟣 flatpickr onChange:', selectedDates)
      if (selectedDates.length === 2 && selectedDates[0] && selectedDates[1]) {
        emit('update', selectedDates[0], selectedDates[1])
      }
    }
  })
  console.log('🟣 flatpickr инициализирован:', flatpickrInstance)
})

// Применить пресет
function applyPreset(preset: typeof presets[0]) {
  console.log('🟣 applyPreset:', preset.label)
  const dates = preset.getValue()
  const start = dates[0]
  const end = dates[1]
  console.log('🟣 Даты пресета:', start, end)

  if (!start || !end) {
    console.log('🟣 Даты пресета undefined!')
    return
  }

  if (flatpickrInstance) {
    flatpickrInstance.setDate([start, end])
    console.log('🟣 flatpickr обновлён')
  }
  emit('update', start, end)
  console.log('🟣 emit update отправлен')
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
        class="buttonGL-text preset-button"
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
