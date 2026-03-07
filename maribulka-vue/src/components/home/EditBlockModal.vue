<script setup lang="ts">
import { ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import RichTextEditor from '../RichTextEditor.vue'
import { useHomeStore } from '../../stores/home'

interface Props {
  isVisible: boolean
  blockId: number
}

const props = defineProps<Props>()
const emit = defineEmits<{ close: [] }>()

const homeStore = useHomeStore()
const content = ref('')

watch(() => props.isVisible, (visible) => {
  if (visible) {
    content.value = homeStore.blocks[props.blockId] ?? ''
  }
})

async function handleSave() {
  const result = await homeStore.saveBlock(props.blockId, content.value)
  if (result.success) {
    emit('close')
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="padGlass modal-sm" style="width: 90vw; max-width: 900px; min-width: auto;">
        <div class="modal-glassTitle">Редактирование блока {{ blockId }}</div>
        <RichTextEditor v-model="content" placeholder="Введите содержимое блока..." />
        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="handleSave">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Сохранить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
