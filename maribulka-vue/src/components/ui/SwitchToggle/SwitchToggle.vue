<script setup lang="ts">
defineProps<{ modelValue: boolean }>()
defineEmits<{ (e: 'update:modelValue', val: boolean): void }>()
</script>

<template>
  <button
    class="switch-toggle"
    :class="{ 'is-on': modelValue }"
    @click="$emit('update:modelValue', !modelValue)"
    type="button"
    role="switch"
    :aria-checked="modelValue"
  >
    <span class="switch-indicator"></span>
  </button>
</template>
