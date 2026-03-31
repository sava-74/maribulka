<script setup lang="ts">
import { computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline } from '@mdi/js'

const props = defineProps<{
  isVisible: boolean
  shootingType: any | null
}>()
const emit = defineEmits(['close'])

const shootingTypeInfo = computed(() => {
  if (!props.shootingType) return null

  return {
    id: props.shootingType.id,
    name: props.shootingType.name,
    basePrice: Math.round(parseFloat(props.shootingType.base_price)),
    duration: props.shootingType.duration_minutes || 30,
    description: props.shootingType.description || '—'
  }
})
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay">
      <div class="modal-glass">
        <div class="modal-glassTitle">Просмотр типа съёмки</div>

        <div v-if="shootingTypeInfo" class="info-section">
          <div class="info-row">
            <span class="info-label">ID:</span>
            <span class="info-value">{{ shootingTypeInfo.id }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Название:</span>
            <span class="info-value">{{ shootingTypeInfo.name }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Цена:</span>
            <span class="info-value">{{ shootingTypeInfo.basePrice }} ₽</span>
          </div>
          <div class="info-row">
            <span class="info-label">Длительность:</span>
            <span class="info-value">{{ shootingTypeInfo.duration }} мин</span>
          </div>
          <div class="info-row">
            <span class="info-label">Описание:</span>
            <span class="info-value">{{ shootingTypeInfo.description }}</span>
          </div>
        </div>

        <div class="ButtonFooter PosRight">
          <button class="buttonGL buttonGL-textFix" @click="emit('close')">
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
            <span>Закрыть</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
