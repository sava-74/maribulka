<script setup lang="ts">
import { computed, inject } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiPlus } from '@mdi/js'

// Inject calendar context
interface CalendarContext {
  sidebarWidth: any
  sidebarZ: any
  snapState: any
  isSidebarByDayView: any
}

const context = inject<CalendarContext>('calendar-context')
if (!context) throw new Error('CalendarSidebar must be inside CalendarContainer')

const { sidebarWidth, sidebarZ, snapState, isSidebarByDayView } = context

// position: absolute, right: 0 — правый край всегда у правой границы контейнера
// width меняется через контекст
const sidebarStyle = computed(() => ({
  width: `${sidebarWidth.value}px`,
  zIndex: sidebarZ.value,
}))

interface Booking {
  id: number
  client_name: string
  shooting_date: string
  shooting_type_name: string
  total_price: number
  paid_amount: number
  payment_status: string
  status: string
}

const props = defineProps<{
  date: string
  bookings: Booking[]
}>()

defineEmits<{
  add: []
  select: [booking: Booking]
}>()

const formattedDate = computed(() => {
  if (!props.date) return ''
  const d = new Date(props.date + 'T00:00:00')
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })
})

function getTime(booking: Booking): string {
  const d = new Date(booking.shooting_date)
  return `${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`
}

function isCancelled(booking: Booking): boolean {
  return ['cancelled', 'cancelled_client', 'cancelled_photographer', 'failed'].includes(booking.status)
}

function getDebt(booking: Booking): number {
  return Math.max(0, booking.total_price - booking.paid_amount)
}
</script>

<template>
  <div
    class="padGlass padGlass-work calendar-sidebar"
    :class="{ 'calendar-sidebar--hidden': isSidebarByDayView, 'calendar-sidebar--animating': snapState === 'hidden-left' || snapState === 'hidden-right' }"
    :style="sidebarStyle"
  >
    <div class="calendar-sidebar__header">{{ formattedDate || 'Выберите день' }}</div>
    <div class="calendar-sidebar__list">
      <template v-if="bookings.length">
        <div
          v-for="b in bookings"
          :key="b.id"
          class="calendar-sidebar__item"
          :class="{ 'calendar-sidebar__item--cancelled': isCancelled(b) }"
          @click="$emit('select', b)"
        >
          <span class="calendar-sidebar__time">{{ getTime(b) }}</span>
          <span class="calendar-sidebar__name">{{ b.client_name }}</span>
          <span class="calendar-sidebar__type">{{ b.shooting_type_name }}</span>
          <span v-if="b.payment_status === 'unpaid'" class="calendar-sidebar__debt">
            долг {{ getDebt(b).toLocaleString('ru-RU') }} ₽
          </span>
          <span v-else-if="b.payment_status === 'partially_paid'" class="calendar-sidebar__debt">
            оплачено {{ b.paid_amount.toLocaleString('ru-RU') }} ₽
          </span>
          <span v-else-if="b.payment_status === 'fully_paid'" class="calendar-sidebar__paid">
            оплачено
          </span>
        </div>
      </template>
      <div v-else class="calendar-sidebar__empty">Нет записей</div>
    </div>
    <div class="ButtonFooter PosCenter calendar-sidebar__footer">
      <button class="btnGlass iconText" @click="$emit('add')">
        <span class="inner-glow"></span>
        <span class="top-shine"></span>
        <svg-icon type="mdi" :path="mdiPlus" class="btn-icon" />
        <span>Добавить</span>
      </button>
    </div>
  </div>
</template>
