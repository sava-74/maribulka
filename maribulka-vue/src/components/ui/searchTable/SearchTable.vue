<script setup lang="ts">
import { onUnmounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiMagnify } from '@mdi/js'

const props = defineProps<{
  modelValue: string
  count?: number
  placeholder?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

let debounceTimer: ReturnType<typeof setTimeout> | null = null

function onInput(e: Event) {
  const value = (e.target as HTMLInputElement).value
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    emit('update:modelValue', value)
  }, 400)
}

onUnmounted(() => {
  if (debounceTimer) clearTimeout(debounceTimer)
})
</script>

<template>
  <div class="search-table" :class="{ 'search-table--active': modelValue }">
    <svg-icon type="mdi" :path="mdiMagnify" class="search-table__icon" />
    <span v-if="modelValue && (count ?? 0) >= 1" class="search-table__count">[{{ count }}]</span>
    <input
      class="search-table__input"
      type="text"
      :value="modelValue"
      :placeholder="placeholder ?? 'Поиск...'"
      @input="onInput"
    />
  </div>
</template>
