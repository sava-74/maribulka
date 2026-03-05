<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'

const props = defineProps<{
  isVisible: boolean
  message: string
  title?: string
}>()
const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('cancel')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ title || 'Подтверждение' }}</div>
        <p class="modal-message">{{ message }}</p>
        <div class="ButtonFooter PosCenter">
          <button class="btnGlass iconText" @click="emit('cancel')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="emit('confirm')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>  Ок  </span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
