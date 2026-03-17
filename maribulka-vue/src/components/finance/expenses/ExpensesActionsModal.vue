<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCashMinus, mdiEyeOutline, mdiUndoVariant, mdiTrashCanOutline } from '@mdi/js'

const props = defineProps<{
  expense: any | null
  isEmpty?: boolean
}>()

const emit = defineEmits(['close', 'add', 'view', 'refund', 'delete'])
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ expense?.category_name ?? 'Расход' }}</div>
        <p class="modal-message">{{ expense?.description ?? 'Добавить' }}</p>

        <div class="ButtonFooter PosCenter PosColumn">
          <button class="btnGlass iconTextStart" :disabled="props.isEmpty" @click="emit('view')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiEyeOutline" class="btn-icon" />
            <span>Просмотр</span>
          </button>
          <button class="btnGlass iconTextStart" @click="emit('add')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCashMinus" class="btn-icon" />
            <span>Выдать</span>
          </button>
          <button class="btnGlass iconTextStart" @click="emit('refund')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiUndoVariant" class="btn-icon" />
            <span>Возврат</span>
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
