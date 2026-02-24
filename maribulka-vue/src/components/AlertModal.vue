<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCheck } from '@mdi/light-js'

const props = defineProps<{
  isVisible: boolean
  message: string
  title?: string
}>()
const emit = defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
        <div class="modal-glassTitle">{{ title || 'Сообщение' }}</div>
        <p class="modal-message">{{ message }}</p>
        <div class="modal-actions">
          <!-- Кнопка "OK" -->
          <button class="glass-button" @click="emit('close')">
            <svg-icon type="mdi" :path="mdilCheck" />
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
