<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiClose } from '@mdi/js'

interface ExpenseCategory { id: number; name: string; is_active: number }

defineProps<{ category: ExpenseCategory }>()
const emit = defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ category.name }}</div>
        <div class="info-row">
          <span class="info-label">Статус:</span>
          <span class="info-value">{{ category.is_active ? 'Активна' : 'Неактивна' }}</span>
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
