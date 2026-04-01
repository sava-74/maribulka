<script setup lang="ts">
defineProps<{
  isVisible: boolean
  salaryType: {
    id: number
    title: string
    monthly_salary: number
    salary_value: number
    percentage_of_the_order: number
    the_percentage_value: number
    interest_dividends: number
    value_dividend_percentages: number
    fixed_order: number
    fixed_value_order: number
  } | null
}>()

const emit = defineEmits<{
  close: []
  edit: []
}>()
</script>

<template>
  <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
    <div class="padGlass modal-md">
      <h2 class="modal-title">{{ salaryType?.title }}</h2>
      
      <div class="info-grid">
        <div class="info-item">
          <span class="info-label">Оклад в месяц:</span>
          <span class="info-value">
            {{ salaryType?.monthly_salary ? `${salaryType.salary_value} руб` : '—' }}
          </span>
        </div>
        
        <div class="info-item">
          <span class="info-label">Процент от заказа:</span>
          <span class="info-value">
            {{ salaryType?.percentage_of_the_order ? `${salaryType.the_percentage_value}%` : '—' }}
          </span>
        </div>
        
        <div class="info-item">
          <span class="info-label">Проценты дивидендов:</span>
          <span class="info-value">
            {{ salaryType?.interest_dividends ? `${salaryType.value_dividend_percentages}%` : '—' }}
          </span>
        </div>
        
        <div class="info-item">
          <span class="info-label">Фиксированное от заказа:</span>
          <span class="info-value">
            {{ salaryType?.fixed_order ? `${salaryType.fixed_value_order} руб` : '—' }}
          </span>
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btnGlass" @click="emit('close')">
          Закрыть
        </button>
        <button class="btnGlass btn-glass-primary" @click="emit('edit')">
          <svg-icon type="mdi" :path="'M20.71 7.04c.39-.39.39-1.04 0-1.41l-2.34-2.34c-.37-.39-1.02-.39-1.41 0l-1.84 1.83 3.75 3.75M3 17.25V21h3.75L17.81 9.93l-3.75-3.75L3 17.25z'" />
          Редактировать
        </button>
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

.modal-md {
  width: 100%;
  max-width: 500px;
  padding: 30px;
}

.modal-title {
  font-size: 22px;
  font-weight: 600;
  margin: 0 0 25px 0;
  text-align: center;
}

.info-grid {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 25px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  padding: 12px 15px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.info-label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
}

.info-value {
  font-size: 15px;
  color: white;
  font-weight: 500;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btnGlass {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 25px;
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
}

.btn-glass-primary {
  background: rgba(0, 217, 255, 0.2);
  border-color: rgba(0, 217, 255, 0.4);
}

.btn-glass-primary:hover {
  background: rgba(0, 217, 255, 0.3);
  border-color: rgba(0, 217, 255, 0.6);
}

:deep(.btnGlass svg) {
  width: 18px;
  height: 18px;
}
</style>
