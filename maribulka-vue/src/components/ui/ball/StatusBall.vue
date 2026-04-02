<script setup lang="ts">
defineProps<{
  color?: 'default' | 'red'
}>()
</script>

<template>
  <span
    class="status-ball"
    :class="{ 'status-ball--red': color === 'red' }"
  ></span>
</template>
