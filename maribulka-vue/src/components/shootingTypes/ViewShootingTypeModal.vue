<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiClose } from '@mdi/js'

interface ShootingType {
  id: number
  name: string
  base_price: number
  duration_minutes: number
  description: string | null
  is_active: number
}

defineProps<{ shootingType: ShootingType }>()
const emit = defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ shootingType.name }}</div>
        <div class="info-row">
          <span class="info-label">Базовая цена:</span>
          <span class="info-value">{{ shootingType.base_price }} руб</span>
        </div>
        <div class="info-row">
          <span class="info-label">Длительность:</span>
          <span class="info-value">{{ shootingType.duration_minutes }} мин</span>
        </div>
        <div class="info-row">
          <span class="info-label">Статус:</span>
          <span class="info-value">{{ shootingType.is_active ? 'Активен' : 'Неактивен' }}</span>
        </div>
        <div v-if="shootingType.description" class="info-row">
          <span class="info-label">Описание:</span>
          <span class="info-value">{{ shootingType.description }}</span>
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
