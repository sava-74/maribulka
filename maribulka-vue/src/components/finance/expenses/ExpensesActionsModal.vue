<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCashMinus, mdiEyeOutline, mdiUndoVariant, mdiTrashCanOutline } from '@mdi/js'

const props = defineProps<{
  expense: any | null
}>()

const emit = defineEmits(['close', 'add', 'view', 'refund', 'delete'])
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ expense?.category_name ?? 'Расход' }}</div>
        <div style="color: var(--text-secondary); font-size: 0.85em; margin-top: -8px; margin-bottom: 4px;">
          {{ expense?.description ?? '—' }}
        </div>

        <div class="ButtonFooter PosCenter" style="flex-direction: column; gap: 8px;">
          <button class="btnGlass iconTextStart" @click="emit('view')">
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
          <button class="btnGlass iconTextStart" @click="emit('delete')">
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
