<script setup lang="ts">
import './padLoader.css'

defineProps<{ progress: number }>()
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Загрузка...</div>
        <div class="pad-loader-track">
          <div class="pad-loader-fill" :style="{ width: progress + '%' }"></div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
