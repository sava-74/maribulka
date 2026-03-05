<script setup lang="ts">
import { ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useGenie } from '../composables/useGenie'

const props = defineProps<{
  isVisible: boolean
  message: string
  title?: string
}>()
const emit = defineEmits(['confirm', 'cancel'])

const panelRef = ref<HTMLElement | null>(null)
const { closing, close } = useGenie(panelRef, () => props.isVisible, () => emit('cancel'))
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" :class="{ 'overlay-leave': closing }" @click.self="close()">
      <div ref="panelRef" class="padGlass modal-sm" :class="closing ? 'fade-leave' : 'fade-enter'">
        <div class="modal-glassTitle">{{ title || 'Подтверждение' }}</div>
        <p class="modal-message">{{ message }}</p>
        <div class="ButtonFooter PosCenter">
          <button class="btnGlass iconText" @click="close()">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="close(() => emit('confirm'))">
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
