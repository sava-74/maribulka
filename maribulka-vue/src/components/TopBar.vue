<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon';
import { 
  mdiAccountOutline, 
  mdiMenu, 
  mdiImageMultiple, 
  mdiWeatherNight, 
  mdiWeatherSunny,
  mdiHome 
} from '@mdi/js';
import { useAuthStore } from '../stores/auth'
import { useReferencesStore } from '../stores/references'


const theme = ref<'dark' | 'light'>('dark')
const themeIcon = computed(() => theme.value === 'dark' ? mdiWeatherNight : mdiWeatherSunny)

function toggleTheme() {
  theme.value = theme.value === 'dark' ? 'light' : 'dark'
  document.documentElement.setAttribute('data-theme', theme.value)
}

function onRipple(event: MouseEvent) {
  const button = event.currentTarget as HTMLElement
  const rect = button.getBoundingClientRect()
  const size = Math.max(rect.width, rect.height)
  const x = event.clientX - rect.left - size / 2
  const y = event.clientY - rect.top - size / 2
  const ripple = document.createElement('span')
  ripple.className = 'btn-ripple'
  ripple.style.width = `${size}px`
  ripple.style.height = `${size}px`
  ripple.style.left = `${x}px`
  ripple.style.top = `${y}px`
  button.appendChild(ripple)
  ripple.addEventListener('animationend', () => ripple.remove())
}

const auth = useAuthStore()
const emit = defineEmits(['open-login', 'open-launchpad'])

const outinIcon = computed(() => auth.isAdmin ? mdiMenu : mdiAccountOutline)

const referencesStore = useReferencesStore()

const handleAction = (event: MouseEvent) => {
  const btn = event.currentTarget as HTMLElement
  const r = btn.getBoundingClientRect()
  const origin = { x: r.left + r.width / 2, y: r.top + r.height / 2, w: r.width, h: r.height }
  if (auth.isAdmin) {
    emit('open-launchpad', origin)
  } else {
    emit('open-login', origin)
  }
}

onMounted(() => {
  referencesStore.fetchPromotions()
})

// Находим действующую акцию (текущая дата в диапазоне start_date - end_date)
/*const activePromotion = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return referencesStore.promotions.find(p => {
    if (!p.start_date || !p.end_date) return false

    const [startYear, startMonth, startDay] = p.start_date.split('-').map(Number)
    const [endYear, endMonth, endDay] = p.end_date.split('-').map(Number)

    const startDate = new Date(startYear!, startMonth! - 1, startDay!)
    const endDate = new Date(endYear!, endMonth! - 1, endDay!)

    startDate.setHours(0, 0, 0, 0)
    endDate.setHours(0, 0, 0, 0)

    return today >= startDate && today <= endDate
  })
})*/



</script>

<template>
  <header class="padGlass padGlass-top">
    <div class="padGlass-top-row">
      <div class="pad-icon-cell">
        <button class="btnGlass bigIcon" @click="handleAction($event); onRipple($event)">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="outinIcon" class="btn-icon-big" />
        </button>
      </div>
      <div class="pad-icon-cell">
        <button class="btnGlass bigIcon" @click="onRipple($event)">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiHome" class="btn-icon-big" />
        </button>
      </div>
      <div class="pad-icon-cell">
        <button class="btnGlass bigIcon" @click="onRipple($event)">
          <span class="inner-glow"></span>
          <span class="top-shine"></span>
          <svg-icon type="mdi" :path="mdiImageMultiple" class="btn-icon-big" />
        </button>
      </div>
      <div class="pad-icon-cell">
        <button class="btn-theme" @click="toggleTheme" aria-label="Переключить тему">
          <div class="btn-theme-indicator">
            <svg-icon type="mdi" :path="themeIcon" />
          </div>
        </button>
      </div>
    </div>
  </header>
</template>