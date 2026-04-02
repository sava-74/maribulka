<script setup lang="ts">
import { ref, computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {
  mdiCalendarMonth,
  mdiTableLarge,
  mdiCurrencyRub,
  mdiTrendingDown,
  mdiChartBar,
  mdiAccountGroup,
  mdiAccountMultiple,
  mdiCamera,
  mdiTagMultiple,
  mdiShapeOutline,
  mdiCashEdit,
  mdiCog,
  mdiLogout,
} from '@mdi/js'
import { useAuthStore } from '../../stores/auth'
import { useNavigationStore } from '../../stores/navigation'
import ConfirmModal from '../ConfirmModal.vue'
import { useGenie } from '../../composables/useGenie'

const props = defineProps<{
  isVisible: boolean
  origin?: { x: number, y: number, w: number, h: number }
}>()
const emit = defineEmits(['close'])

const genieStyle = computed(() => {
  if (!props.origin) return {}
  const cx = window.innerWidth / 2
  const cy = window.innerHeight / 2
  return {
    '--genie-dx': `${props.origin.x - cx}px`,
    '--genie-dy': `${props.origin.y - cy}px`,
  }
})

const auth = useAuthStore()
const navStore = useNavigationStore()
const showLogoutConfirm = ref(false)
const panelRef = ref<HTMLElement | null>(null)
const { closing, close } = useGenie(panelRef, () => props.isVisible, () => {
  showLogoutConfirm.value = false
  emit('close')
})

function openCalendar() {
  navStore.navigateTo('calendar')
  close()
}

function openBookings() {
  navStore.navigateTo('bookings')
  close()
}

function openIncome() {
  navStore.navigateTo('income')
  close()
}

function openExpenses() {
  navStore.navigateTo('expenses')
  close()
}

function openUsers() {
  navStore.navigateTo('users')
  close()
}

function openClients() {
  navStore.navigateTo('clients')
  close()
}

function openShootingTypes() {
  navStore.navigateTo('shooting_types')
  close()
}

function openPromotions() {
  navStore.navigateTo('promotions')
  close()
}

function openExpenseCategories() {
  navStore.navigateTo('expense_categories')
  close()
}

function openSalaryTypes() {
  navStore.navigateTo('salary_types')
  close()
}

defineExpose({ close })

const onLogoutConfirm = () => {
  showLogoutConfirm.value = false
  auth.logout()
  close()
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
</script>

<template>
  <template v-if="isVisible">
  <div class="modal-overlay-launch" :class="{ 'overlay-leave': closing }">
      <div ref="panelRef" class="padGlass padGlass-work" :class="closing ? 'genie-leave' : 'genie-enter'" :style="genieStyle">

        <!-- Профиль вошедшего -->
        <div class="modal-glassTitle" v-if="auth.userName">
          {{ auth.userProfession ? `${auth.userProfession} — ${auth.userName}` : auth.userName }}
        </div>

        <!-- Секция: Учёт -->
        <div class="pad-title">Учёт</div>
        <div class="pad-icon-grid">
          <div class="pad-icon-cell">
            <button class="btnGlass bigIcon" @click="onRipple($event); openCalendar()">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiCalendarMonth" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Календарь</p>
          </div>
          <div class="pad-icon-cell">
            <button class="btnGlass bigIcon" @click="onRipple($event); openBookings()">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiTableLarge" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Записи</p>
          </div>
          <div class="pad-icon-cell" v-if="auth.can('income', 'view')">
            <button class="btnGlass bigIcon" @click="onRipple($event); openIncome()">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiCurrencyRub" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Приход</p>
          </div>
          <div class="pad-icon-cell" v-if="auth.can('expenses', 'view')">
            <button class="btnGlass bigIcon" @click="onRipple($event); openExpenses()">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiTrendingDown" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Расходы</p>
          </div>
          <div class="pad-icon-cell" v-if="auth.can('reports', 'view')">
            <button class="btnGlass bigIcon" @click="onRipple($event)">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiChartBar" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Отчёты</p>
          </div>
        </div>

        <!-- Секция: Справочники -->
        <div class="pad-title">Справочники</div>
        <div class="pad-icon-grid">
          <div class="pad-icon-cell" v-if="auth.can('clients', 'view')">
            <button class="btnGlass bigIcon" @click="onRipple($event); openClients()">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiAccountGroup" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Клиенты</p>
          </div>
          <div class="pad-icon-cell" v-if="auth.can('shooting_types', 'view')">
            <button class="btnGlass bigIcon" @click="onRipple($event); openShootingTypes()">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiCamera" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Типы съёмок</p>
          </div>
          <div class="pad-icon-cell" v-if="auth.can('promotions', 'view')">
            <button class="btnGlass bigIcon" @click="onRipple($event); openPromotions()">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiTagMultiple" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Акции</p>
          </div>
          <div class="pad-icon-cell" v-if="auth.can('expense_categories', 'view')">
            <button class="btnGlass bigIcon" @click="onRipple($event); openExpenseCategories()">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiShapeOutline" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Категории расходов</p>
          </div>
          <div class="pad-icon-cell" v-if="auth.can('salary_types', 'view')">
            <button class="btnGlass bigIcon" @click="onRipple($event); openSalaryTypes()">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiCashEdit" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Зарплаты</p>
          </div>
          <div class="pad-icon-cell" v-if="auth.can('users', 'view')">
            <button class="btnGlass bigIcon" @click="onRipple($event); openUsers()">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiAccountMultiple" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Пользователи</p>
          </div>
        </div>

        <!-- Секция: Администрирование -->
        <div class="pad-title">Администрирование</div>
        <div class="pad-icon-grid">
          <div class="pad-icon-cell" v-if="auth.can('settings', 'view')">
            <button class="btnGlass bigIcon" @click="onRipple($event)">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiCog" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Настройки</p>
          </div>
          <div class="pad-icon-cell">
            <button class="btnGlass bigIcon" @click="showLogoutConfirm = true; onRipple($event)">
              <span class="inner-glow"></span>
              <span class="top-shine"></span>
              <svg-icon type="mdi" :path="mdiLogout" class="btn-icon-big" />
            </button>
            <p class="pad-icon-label">Выход</p>
          </div>
        </div>

      </div>
    </div>
    <ConfirmModal
      :isVisible="showLogoutConfirm"
      title="Подтверждение"
      message="Выйти из системы?"
      @confirm="onLogoutConfirm"
      @cancel="showLogoutConfirm = false"
    />
  </template>
</template>
