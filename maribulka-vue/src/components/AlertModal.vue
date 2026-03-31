<script setup lang="ts">
import { ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline } from '@mdi/js'
import { useGenie } from '../composables/useGenie'

const props = defineProps<{
  isVisible: boolean
  message: string
  title?: string
}>()
const emit = defineEmits(['close'])

const panelRef = ref<HTMLElement | null>(null)
const { closing, close } = useGenie(panelRef, () => props.isVisible, () => emit('close'))
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" :class="{ 'overlay-leave': closing }">
      <div ref="panelRef" class="padGlass modal-sm" :class="closing ? 'fade-leave' : 'fade-enter'">
        <div class="modal-glassTitle">{{ title || 'Сообщение' }}</div>
        <p class="modal-message">{{ message }}</p>
        <div class="ButtonFooter PosCenter">
          <button class="btnGlass iconText" @click="close()">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Ок</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
