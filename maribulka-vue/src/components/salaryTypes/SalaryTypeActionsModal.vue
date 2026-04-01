<script setup lang="ts">
defineProps<{
  isVisible: boolean
  salaryType: {
    id: number
    title: string
  } | null
}>()

const emit = defineEmits<{
  close: []
  view: []
  edit: []
  delete: []
}>()
</script>

<template>
  <div v-if="isVisible" class="modal-overlay" @click.self="close">
    <div class="padGlass modal-sm">
      <h2 class="modal-title">Действия</h2>
      <p class="modal-subtitle">{{ salaryType?.title }}</p>

      <div class="modal-actions">
        <button class="btnGlass" @click="view">
          <svg-icon type="mdi" :path="'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 13c-3.03 0-5.5-2.47-5.5-5.5S8.97 6.5 12 6.5s5.5 2.47 5.5 5.5-2.47 5.5-5.5 5.5zm0-9C9.79 8.5 8.5 9.79 8.5 12s1.29 3.5 3.5 3.5 3.5-1.29 3.5-3.5S14.21 8.5 12 8.5z'" />
          Просмотр
        </button>

        <button class="btnGlass" @click="edit">
          <svg-icon type="mdi" :path="'M20.71 7.04c.39-.39.39-1.04 0-1.41l-2.34-2.34c-.37-.39-1.02-.39-1.41 0l-1.84 1.83 3.75 3.75M3 17.25V21h3.75L17.81 9.93l-3.75-3.75L3 17.25z'" />
          Редактировать
        </button>

        <button class="btnGlass btn-glass-danger" @click="delete">
          <svg-icon type="mdi" :path="'M19 4h-3.5l-1-1h-5l-1 1H5v2h14M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12z'" />
          Удалить
        </button>
      </div>

      <div class="modal-footer">
        <button class="btnGlass" @click="close">Отмена</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-sm {
  width: 100%;
  max-width: 400px;
  padding: 30px;
}

.modal-title {
  font-size: 22px;
  font-weight: 600;
  margin: 0 0 10px 0;
  text-align: center;
}

.modal-subtitle {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.7);
  margin: 0 0 25px 0;
  text-align: center;
}

.modal-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btnGlass {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: white;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.2s;
}

.btnGlass:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.3);
}

.btn-glass-danger {
  background: rgba(255, 82, 82, 0.2);
  border-color: rgba(255, 82, 82, 0.4);
}

.btn-glass-danger:hover {
  background: rgba(255, 82, 82, 0.3);
  border-color: rgba(255, 82, 82, 0.6);
}

:deep(.btnGlass svg) {
  width: 20px;
  height: 20px;
}
</style>
