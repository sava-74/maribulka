<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiClose } from '@mdi/js'

interface Promotion {
  id: number
  name: string
  discount_percent: number
  start_date: string | null
  end_date: string | null
  is_active: number
}

defineProps<{ promotion: Promotion }>()
const emit = defineEmits(['close'])

function formatDate(d: string | null): string {
  if (!d) return 'бессрочно'
  const p = d.split('T')[0]?.split(' ')[0]?.split('-')
  return p ? `${p[2]}.${p[1]}.${p[0]}` : '—'
}
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ promotion.name }}</div>
        <div class="info-row">
          <span class="info-label">Скидка:</span>
          <span class="info-value discount">{{ promotion.discount_percent }}%</span>
        </div>
        <div class="info-row">
          <span class="info-label">Период:</span>
          <span class="info-value">{{ formatDate(promotion.start_date) }} — {{ formatDate(promotion.end_date) }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Статус:</span>
          <span class="info-value">{{ promotion.is_active ? 'Активна' : 'Неактивна' }}</span>
        </div>
        <div class="ButtonFooter PosCenter">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span><span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiClose" class="btn-icon" /><span>Закрыть</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
