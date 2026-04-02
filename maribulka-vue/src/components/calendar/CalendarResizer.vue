<script setup lang="ts">
import { ref, inject, computed } from 'vue'
import { mdiDotsVertical } from '@mdi/js'
import SvgIcon from '@jamescoyle/vue-icon'

interface CalendarContext {
  snapState: any
  resizerLeft: any
  CALENDAR_MIN: number
  SIDEBAR_MIN: number
  RESIZER_WIDTH: number
  setCalendarWidth: (w: number) => void
  setSidebarWidth: (w: number) => void
  setResizerLeft: (x: number) => void
  setCalendarZ: (z: number) => void
  setSidebarZ: (z: number) => void
  setSnapState: (s: string) => void
}

const ctx = inject<CalendarContext>('calendar-context')
if (!ctx) throw new Error('CalendarResizer must be inside CalendarContainer')

const { snapState, resizerLeft, CALENDAR_MIN, SIDEBAR_MIN, RESIZER_WIDTH,
        setCalendarWidth, setSidebarWidth, setResizerLeft,
        setCalendarZ, setSidebarZ, setSnapState } = ctx

const isDragging = ref(false)
let containerRect = { left: 0, width: 0 }

const containerEl = computed(() => document.querySelector('.calendar-container') as HTMLElement | null)
const resizerLeftPx = computed(() => `${resizerLeft.value}px`)

function startDrag() {
  const el = containerEl.value
  if (!el) return
  const r = el.getBoundingClientRect()
  containerRect = { left: r.left, width: r.width }
  isDragging.value = true
  document.addEventListener('mousemove', onDrag)
  document.addEventListener('mouseup', stopDrag)
  document.body.style.cursor = 'ew-resize'
  document.body.style.userSelect = 'none'
}

function onDrag(e: MouseEvent) {
  if (!isDragging.value) return

  const cursorX = e.clientX - containerRect.left  // позиция курсора от левого края контейнера
  const W = containerRect.width
  const sidebarW = W - cursorX - RESIZER_WIDTH     // ширина сайдбара при текущем курсоре

  if (snapState.value === 'free') {
    if (cursorX <= CALENDAR_MIN) {
      // Calendar достиг минимума — входим в snapping-left
      setSnapState('snapping-left')
      setCalendarWidth(CALENDAR_MIN)
      setCalendarZ(1)
      setSidebarZ(2)
      // Sidebar начинает наезжать: его ширина = W - cursorX - RESIZER_WIDTH
      setSidebarWidth(Math.max(0, W - cursorX - RESIZER_WIDTH))
      setResizerLeft(cursorX)
    } else if (sidebarW <= SIDEBAR_MIN) {
      // Sidebar достиг минимума — входим в snapping-right
      setSnapState('snapping-right')
      setSidebarWidth(SIDEBAR_MIN)
      setSidebarZ(1)
      setCalendarZ(2)
      setCalendarWidth(Math.max(0, cursorX))
      setResizerLeft(cursorX)
    } else {
      // Свободный resize
      setCalendarWidth(cursorX)
      setSidebarWidth(sidebarW)
      setResizerLeft(cursorX)
    }

  } else if (snapState.value === 'snapping-left') {
    if (cursorX >= CALENDAR_MIN) {
      // Вернулись за минимум — обратно в free
      setSnapState('free')
      setCalendarZ(1)
      setSidebarZ(1)
      setCalendarWidth(cursorX)
      setSidebarWidth(W - cursorX - RESIZER_WIDTH)
      setResizerLeft(cursorX)
    } else if (cursorX <= 0) {
      // Sidebar полностью перекрыл Calendar
      setSnapState('hidden-left')
      setSidebarWidth(W - RESIZER_WIDTH)
      setResizerLeft(0)
    } else {
      // Sidebar наезжает: растём влево, Calendar заморожен
      setSidebarWidth(W - cursorX - RESIZER_WIDTH)
      setResizerLeft(cursorX)
    }

  } else if (snapState.value === 'hidden-left') {
    if (cursorX > 0) {
      // Начали тянуть обратно
      setSnapState('snapping-left')
      setSidebarWidth(W - cursorX - RESIZER_WIDTH)
      setResizerLeft(cursorX)
    }

  } else if (snapState.value === 'snapping-right') {
    if (sidebarW >= SIDEBAR_MIN) {
      // Вернулись за минимум — обратно в free
      setSnapState('free')
      setCalendarZ(1)
      setSidebarZ(1)
      setCalendarWidth(cursorX)
      setSidebarWidth(sidebarW)
      setResizerLeft(cursorX)
    } else if (sidebarW <= 0) {
      // Calendar полностью перекрыл Sidebar
      setSnapState('hidden-right')
      setCalendarWidth(W - RESIZER_WIDTH)
      setResizerLeft(W - RESIZER_WIDTH)
    } else {
      // Calendar наезжает: растём вправо, Sidebar заморожен
      setCalendarWidth(cursorX)
      setResizerLeft(cursorX)
    }

  } else if (snapState.value === 'hidden-right') {
    if (sidebarW > 0) {
      // Начали тянуть обратно
      setSnapState('snapping-right')
      setCalendarWidth(cursorX)
      setResizerLeft(cursorX)
    }
  }
}

function stopDrag() {
  isDragging.value = false
  document.removeEventListener('mousemove', onDrag)
  document.removeEventListener('mouseup', stopDrag)
  document.body.style.cursor = ''
  document.body.style.userSelect = ''

  const W = containerRect.width

  // Если отпустили в snapping — snap-back анимация к ближайшему краю
  if (snapState.value === 'snapping-left') {
    setSnapState('hidden-left')
    setSidebarWidth(W - RESIZER_WIDTH)
    setResizerLeft(0)
  } else if (snapState.value === 'snapping-right') {
    setSnapState('hidden-right')
    setCalendarWidth(W - RESIZER_WIDTH)
    setResizerLeft(W - RESIZER_WIDTH)
  }
}
</script>

<template>
  <div
    class="calendar-resizer"
    :class="{ 'calendar-resizer--dragging': isDragging }"
    :style="{ left: resizerLeftPx }"
    @mousedown="startDrag"
  >
    <svg-icon type="mdi" :path="mdiDotsVertical" class="icon-svg" />
  </div>
</template>
