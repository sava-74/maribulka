<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon';
import { mdiAccountOutline, mdiAccountOffOutline } from '@mdi/js';
import { useAuthStore } from '../stores/auth'
import ConfirmModal from './ConfirmModal.vue'
import { useReferencesStore } from '../stores/references'



const auth = useAuthStore()
const emit = defineEmits(['open-login'])

const referencesStore = useReferencesStore()

const showConfirm = ref(false)

const handleAction = () => {
  if (auth.isAdmin) {
    showConfirm.value = true
  } else {
    emit('open-login')
  }
}

const handleLogout = async () => {
  await auth.logout()
  showConfirm.value = false
}

onMounted(() => {
  referencesStore.fetchPromotions()

})

// Находим действующую акцию (текущая дата в диапазоне start_date - end_date)
const activePromotion = computed(() => {
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
})



</script>

<template>
  <header class="top-bar">
    <div class="logo-area">
      <img src="/img/owner.jpg" alt="Марибулька" class="owner-photo">
      <h1 class="site-name">Фотостудия Марии</h1>
      <h1 v-if="activePromotion" class="promotion-text">
        Акция "{{ activePromotion.name }}" {{ Math.round(activePromotion.discount_percent) }}%
      </h1>
    </div>

    <!-- Иконка меняется динамически: mdilLogin или mdilLogout -->
    <button class="buttonGL" @click="handleAction">
      <svg-icon type="mdi" :path="auth.isAdmin ? mdiAccountOffOutline : mdiAccountOutline" />
    </button>
    <ConfirmModal
      :isVisible="showConfirm"
      message="Выйти из системы?"
      title="Подтверждение"
      @confirm="handleLogout"
      @cancel="showConfirm = false"
    />
  </header>
</template>