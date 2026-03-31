<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCashPlus, mdiEyeOutline, mdiTrashCanOutline } from '@mdi/js'

const props = defineProps<{
  income: any | null
  isEmpty?: boolean
}>()

const emit = defineEmits(['close', 'add', 'view', 'delete'])
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ income?.client_name }}</div>
        <p class="modal-message">{{ income?.order_number ?? 'Добавить' }}</p>

        <div class="ButtonFooter PosCenter PosColumn">
          <button class="btnGlass iconTextStart" @click="emit('add')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCashPlus" class="btn-icon" />
            <span>Внести</span>
          </button>
          <button class="btnGlass iconTextStart" :disabled="props.isEmpty" @click="emit('view')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiEyeOutline" class="btn-icon" />
            <span>Просмотр</span>
          </button>
          <button class="btnGlass iconTextStart" :disabled="props.isEmpty" @click="emit('delete')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiTrashCanOutline" class="btn-icon" />
            <span>Удалить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
