<script setup lang="ts">
import { ref } from 'vue'
import ValidAlertModal from '../ValidAlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  salaryType: {
    id: number
    title: string
  } | null
}>()

const emit = defineEmits<{
  close: []
  success: []
}>()

const isDeleting = ref(false)
const showAlert = ref(false)
const alertTitle = ref('')
const alertMessage = ref<string[]>([])

async function confirmDelete() {
  if (!props.salaryType) return
  
  isDeleting.value = true
  
  try {
    // Сначала проверяем связи
    const checkRes = await fetch(
      `/api/salary-types.php?check_relations=1&id=${props.salaryType.id}`,
      { credentials: 'include' }
    )
    const hasRelations = await checkRes.json()
    
    if (hasRelations) {
      showAlert.value = true
      alertTitle.value = 'Удаление невозможно'
      alertMessage.value = [`Тип зарплаты "${props.salaryType.title}" удалить нельзя. Этот тип зарплаты используется у сотрудников.`]
      isDeleting.value = false
      return
    }
    
    // Если связей нет, удаляем
    const res = await fetch(
      `/api/salary-types.php?action=delete&id=${props.salaryType.id}`,
      {
        method: 'DELETE',
        credentials: 'include',
      }
    )
    
    const data = await res.json()
    
    if (res.ok && data.success) {
      emit('success')
      emit('close')
    } else {
      showAlert.value = true
      alertTitle.value = 'Ошибка'
      alertMessage.value = [data.message || 'Не удалось удалить']
    }
  } catch (e) {
    showAlert.value = true
    alertTitle.value = 'Ошибка'
    alertMessage.value = ['Ошибка сети']
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
    <div class="padGlass modal-sm">
      <h2 class="modal-title modal-title-danger">Удаление типа зарплаты</h2>
      
      <p class="modal-text">
        Вы действительно хотите удалить тип зарплаты<br>
        <strong>"{{ salaryType?.title }}"</strong>?
      </p>
      
      <p class="modal-warning">
        <svg-icon type="mdi" :path="'M12 9V11H14V9H12M12 13H14V15H12V13M1 21H23L12 2L1 21M12 6L19.53 19H4.47L12 6Z'" />
        Это действие нельзя отменить
      </p>
      
      <div class="modal-footer">
        <button class="btnGlass" @click="emit('close')" :disabled="isDeleting">
          Отмена
        </button>
        <button class="btnGlass btn-glass-danger" @click="confirmDelete" :disabled="isDeleting">
          {{ isDeleting ? 'Удаление...' : 'Удалить' }}
        </button>
      </div>
    </div>
  </div>
  
  <ValidAlertModal
    :is-visible="showAlert"
    :title="alertTitle"
    :messages="alertMessage"
    @close="showAlert = false"
  />
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
  max-width: 450px;
  padding: 30px;
}

.modal-title {
  font-size: 22px;
  font-weight: 600;
  margin: 0 0 20px 0;
  text-align: center;
}

.modal-title-danger {
  color: #ff5252;
}

.modal-text {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.8);
  text-align: center;
  margin: 0 0 20px 0;
  line-height: 1.5;
}

.modal-text strong {
  color: white;
  font-size: 18px;
}

.modal-warning {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  background: rgba(255, 82, 82, 0.1);
  border-radius: 8px;
  font-size: 14px;
  color: #ff5252;
  margin-bottom: 25px;
}

.modal-warning :deep(svg) {
  width: 20px;
  height: 20px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btnGlass {
  padding: 12px 25px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: white;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.2s;
}

.btnGlass:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btnGlass:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.15);
}

.btn-glass-danger {
  background: rgba(255, 82, 82, 0.2);
  border-color: rgba(255, 82, 82, 0.4);
}

.btn-glass-danger:hover:not(:disabled) {
  background: rgba(255, 82, 82, 0.3);
  border-color: rgba(255, 82, 82, 0.6);
}
</style>
