<template>
  <div ref="containerEl" class="calendar-container">
    <slot></slot>
  </div>
</template>

<script setup lang="ts">
import { provide, ref, onMounted } from 'vue'

const CALENDAR_MIN = 420
const SIDEBAR_MIN = 300
const RESIZER_WIDTH = 24

const containerEl = ref<HTMLElement | null>(null)

// Ширины панелей — инициализируются при mount по реальной ширине контейнера
const calendarWidth = ref(650)
const sidebarWidth = ref(350)
// Позиция разделителя от левого края контейнера
const resizerLeft = ref(650)
// Z-index панелей
const calendarZ = ref(1)
const sidebarZ = ref(1)
// snapState
const snapState = ref<'free' | 'snapping-left' | 'hidden-left' | 'snapping-right' | 'hidden-right'>('free')
// Скрытие сайдбара при режиме дня
const isSidebarByDayView = ref(false)

interface CalendarContext {
  calendarWidth: typeof calendarWidth
  sidebarWidth: typeof sidebarWidth
  resizerLeft: typeof resizerLeft
  calendarZ: typeof calendarZ
  sidebarZ: typeof sidebarZ
  snapState: typeof snapState
  isSidebarByDayView: typeof isSidebarByDayView
  CALENDAR_MIN: number
  SIDEBAR_MIN: number
  RESIZER_WIDTH: number
  setCalendarWidth: (w: number) => void
  setSidebarWidth: (w: number) => void
  setResizerLeft: (x: number) => void
  setCalendarZ: (z: number) => void
  setSidebarZ: (z: number) => void
  setSnapState: (s: typeof snapState.value) => void
  setSidebarByDayView: (v: boolean) => void
}

provide<CalendarContext>('calendar-context', {
  calendarWidth,
  sidebarWidth,
  resizerLeft,
  calendarZ,
  sidebarZ,
  snapState,
  isSidebarByDayView,
  CALENDAR_MIN,
  SIDEBAR_MIN,
  RESIZER_WIDTH,
  setCalendarWidth: (w) => { calendarWidth.value = w },
  setSidebarWidth: (w) => { sidebarWidth.value = w },
  setResizerLeft: (x) => { resizerLeft.value = x },
  setCalendarZ: (z) => { calendarZ.value = z },
  setSidebarZ: (z) => { sidebarZ.value = z },
  setSnapState: (s) => { snapState.value = s },
  setSidebarByDayView: (v) => { isSidebarByDayView.value = v },
})

onMounted(() => {
  if (!containerEl.value) return
  const W = containerEl.value.getBoundingClientRect().width
  // Calendar занимает ~65% контейнера, sidebar — остаток
  calendarWidth.value = Math.round(W * 0.65)
  sidebarWidth.value = W - calendarWidth.value - RESIZER_WIDTH
  resizerLeft.value = calendarWidth.value
})
</script>
