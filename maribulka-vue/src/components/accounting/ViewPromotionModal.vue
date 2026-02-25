<script setup lang="ts">
import { computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline } from '@mdi/js'

const props = defineProps<{
  isVisible: boolean
  promotion: any | null
}>()
const emit = defineEmits(['close'])

function formatDate(dateStr: string | null) {
  if (!dateStr) return '∞'
  const [year, month, day] = dateStr.split('-')
  return `${day}.${month}.${year!.slice(-2)}`
}

const promotionInfo = computed(() => {
  if (!props.promotion) return null

  return {
    id: props.promotion.id,
    name: props.promotion.name,
    discountPercent: Math.round(parseFloat(props.promotion.discount_percent)),
    startDate: formatDate(props.promotion.start_date),
    endDate: formatDate(props.promotion.end_date)
  }
})
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
        <div class="modal-glassTitle">Просмотр акции</div>

        <div v-if="promotionInfo" class="info-section">
          <div class="info-row">
            <span class="info-label">ID:</span>
            <span class="info-value">{{ promotionInfo.id }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Название:</span>
            <span class="info-value">{{ promotionInfo.name }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Скидка:</span>
            <span class="info-value">{{ promotionInfo.discountPercent }}%</span>
          </div>
          <div class="info-row">
            <span class="info-label">Дата начала:</span>
            <span class="info-value">{{ promotionInfo.startDate }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Дата окончания:</span>
            <span class="info-value">{{ promotionInfo.endDate }}</span>
          </div>
        </div>

        <div class="modal-actions">
          <button class="glass-button" @click="emit('close')">
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
