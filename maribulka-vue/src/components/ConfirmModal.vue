<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdilCheck, mdilCancel } from '@mdi/light-js'

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
      <div class="modal-glass">
        <div class="modal-glassTitle">{{ title || 'Подтверждение' }}</div>
        <p class="modal-message">{{ message }}</p>
        <div class="modal-actions">
          <!-- Кнопка "Отмена" -->
          <button class="glass-button" @click="emit('cancel')">
            <svg-icon type="mdi" :path="mdilCancel" />
          </button>
          <!-- Кнопка "Подтвердить" -->
          <button class="glass-button" @click="emit('confirm')">
            <svg-icon type="mdi" :path="mdilCheck" />
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
