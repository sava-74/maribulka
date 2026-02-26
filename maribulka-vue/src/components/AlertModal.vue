<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline } from '@mdi/js'

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
          <button class="buttonGL" @click="emit('close')">
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
            <span class="textButton">Ок</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
