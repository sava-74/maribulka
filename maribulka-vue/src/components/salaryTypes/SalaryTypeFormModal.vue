<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import ValidAlertModal from '../ValidAlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  isCreating: boolean
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
  success: []
}>()

const form = ref({
  title: '',
  monthly_salary: 0,
  salary_value: 0,
  percentage_of_the_order: 0,
  the_percentage_value: 0,
  interest_dividends: 0,
  value_dividend_percentages: 0,
  fixed_order: 0,
  fixed_value_order: 0,
})

const errors = ref<Record<string, string>>({})
const showAlert = ref(false)
const alertTitle = ref('')
const alertMessage = ref('')
const isSaving = ref(false)

watch(() => props.salaryType, (val) => {
  if (val) {
    form.value = {
      title: val.title,
      monthly_salary: val.monthly_salary,
      salary_value: val.salary_value,
      percentage_of_the_order: val.percentage_of_the_order,
      the_percentage_value: val.the_percentage_value,
      interest_dividends: val.interest_dividends,
      value_dividend_percentages: val.value_dividend_percentages,
      fixed_order: val.fixed_order,
      fixed_value_order: val.fixed_value_order,
    }
  } else {
    resetForm()
  }
}, { immediate: true })

function resetForm() {
  form.value = {
    title: '',
    monthly_salary: 0,
    salary_value: 0,
    percentage_of_the_order: 0,
    the_percentage_value: 0,
    interest_dividends: 0,
    value_dividend_percentages: 0,
    fixed_order: 0,
    fixed_value_order: 0,
  }
  errors.value = {}
}

function validate(): boolean {
  errors.value = {}
  
  if (!form.value.title || !form.value.title.trim()) {
    errors.value.title = 'Введите название'
  }
  
  // Проверка числовых значений
  const numericFields = [
    { key: 'salary_value', label: 'Значение оклада' },
    { key: 'the_percentage_value', label: 'Процент от заказа' },
    { key: 'value_dividend_percentages', label: 'Процент дивидендов' },
    { key: 'fixed_value_order', label: 'Фиксированное значение' },
  ]
  
  for (const field of numericFields) {
    const val = form.value[field.key as keyof typeof form.value] as number
    if (val < 0) {
      errors.value[field.key] = `${field.label} не может быть отрицательным`
    }
    if (field.key.includes('percentage') || field.key.includes('dividend')) {
      if (val > 100) {
        errors.value[field.key] = `${field.label} не может быть больше 100%`
      }
    }
  }
  
  return Object.keys(errors.value).length === 0
}

async function save() {
  if (!validate()) return
  
  isSaving.value = true
  
  try {
    const url = props.isCreating
      ? '/api/salary-types.php?action=create'
      : '/api/salary-types.php?action=update'
    
    const method = props.isCreating ? 'POST' : 'PUT'
    
    const body = props.isCreating
      ? { ...form.value }
      : { ...form.value, id: props.salaryType?.id }
    
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
      credentials: 'include',
    })
    
    const data = await res.json()
    
    if (res.ok && data.success) {
      emit('success')
      emit('close')
    } else {
      showAlert.value = true
      alertTitle.value = 'Ошибка'
      alertMessage.value = data.message || 'Не удалось сохранить'
    }
  } catch (e) {
    showAlert.value = true
    alertTitle.value = 'Ошибка'
    alertMessage.value = 'Ошибка сети'
  } finally {
    isSaving.value = false
  }
}

// Функции для обработки checkbox + input
function toggleMonthlySalary() {
  if (form.value.monthly_salary === 0) {
    form.value.monthly_salary = 1
  } else {
    form.value.monthly_salary = 0
    form.value.salary_value = 0
  }
}

function togglePercentage() {
  if (form.value.percentage_of_the_order === 0) {
    form.value.percentage_of_the_order = 1
  } else {
    form.value.percentage_of_the_order = 0
    form.value.the_percentage_value = 0
  }
}

function toggleDividends() {
  if (form.value.interest_dividends === 0) {
    form.value.interest_dividends = 1
  } else {
    form.value.interest_dividends = 0
    form.value.value_dividend_percentages = 0
  }
}

function toggleFixed() {
  if (form.value.fixed_order === 0) {
    form.value.fixed_order = 1
  } else {
    form.value.fixed_order = 0
    form.value.fixed_value_order = 0
  }
}
</script>

<template>
  <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
    <div class="padGlass modal-md">
      <h2 class="modal-title">{{ isCreating ? 'Новый тип зарплаты' : 'Редактирование' }}</h2>
      
      <div class="form-group">
        <label>Название *</label>
        <input
          v-model="form.title"
          type="text"
          placeholder="Введите название типа зарплаты"
          :class="{ error: errors.title }"
        />
        <span v-if="errors.title" class="error-text">{{ errors.title }}</span>
      </div>
      
      <div class="form-section">
        <h3 class="section-title">Варианты начислений</h3>
        <p class="section-desc">Можно выбрать несколько вариантов одновременно</p>
        
        <!-- Оклад в месяц -->
        <div class="checkbox-row">
          <label class="checkbox-label">
            <input
              type="checkbox"
              :checked="form.monthly_salary === 1"
              @change="toggleMonthlySalary"
            />
            <span>Оклад в месяц:</span>
          </label>
          <input
            v-model.number="form.salary_value"
            type="number"
            :disabled="form.monthly_salary === 0"
            placeholder="Сумма"
            class="input-small"
            min="0"
          />
          <span class="field-label">руб</span>
        </div>
        <span v-if="errors.salary_value" class="error-text">{{ errors.salary_value }}</span>
        
        <!-- Процент от заказа -->
        <div class="checkbox-row">
          <label class="checkbox-label">
            <input
              type="checkbox"
              :checked="form.percentage_of_the_order === 1"
              @change="togglePercentage"
            />
            <span>Процент от заказа:</span>
          </label>
          <input
            v-model.number="form.the_percentage_value"
            type="number"
            :disabled="form.percentage_of_the_order === 0"
            placeholder="Процент"
            class="input-small"
            min="0"
            max="100"
          />
          <span class="field-label">%</span>
        </div>
        <span v-if="errors.the_percentage_value" class="error-text">{{ errors.the_percentage_value }}</span>
        
        <!-- Проценты дивидендов -->
        <div class="checkbox-row">
          <label class="checkbox-label">
            <input
              type="checkbox"
              :checked="form.interest_dividends === 1"
              @change="toggleDividends"
            />
            <span>Проценты дивидендов:</span>
          </label>
          <input
            v-model.number="form.value_dividend_percentages"
            type="number"
            :disabled="form.interest_dividends === 0"
            placeholder="Процент"
            class="input-small"
            min="0"
            max="100"
          />
          <span class="field-label">%</span>
        </div>
        <span v-if="errors.value_dividend_percentages" class="error-text">{{ errors.value_dividend_percentages }}</span>
        
        <!-- Фиксированное от заказа -->
        <div class="checkbox-row">
          <label class="checkbox-label">
            <input
              type="checkbox"
              :checked="form.fixed_order === 1"
              @change="toggleFixed"
            />
            <span>Фиксированное от заказа:</span>
          </label>
          <input
            v-model.number="form.fixed_value_order"
            type="number"
            :disabled="form.fixed_order === 0"
            placeholder="Сумма"
            class="input-small"
            min="0"
          />
          <span class="field-label">руб</span>
        </div>
        <span v-if="errors.fixed_value_order" class="error-text">{{ errors.fixed_value_order }}</span>
      </div>
      
      <div class="modal-footer">
        <button class="btnGlass" @click="emit('close')" :disabled="isSaving">
          Отмена
        </button>
        <button class="btnGlass btn-glass-primary" @click="save" :disabled="isSaving">
          {{ isSaving ? 'Сохранение...' : 'Сохранить' }}
        </button>
      </div>
    </div>
  </div>
  
  <ValidAlertModal
    :is-visible="showAlert"
    :title="alertTitle"
    :message="alertMessage"
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

.modal-md {
  width: 100%;
  max-width: 550px;
  padding: 30px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-title {
  font-size: 22px;
  font-weight: 600;
  margin: 0 0 25px 0;
  text-align: center;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.8);
}

.form-group input[type="text"] {
  width: 100%;
  padding: 12px 15px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  color: white;
  font-size: 15px;
  transition: border-color 0.2s;
}

.form-group input[type="text"]:focus {
  outline: none;
  border-color: #00d9ff;
}

.form-group input[type="text"].error {
  border-color: #ff5252;
}

.error-text {
  display: block;
  margin-top: 5px;
  font-size: 12px;
  color: #ff5252;
}

.form-section {
  background: rgba(255, 255, 255, 0.03);
  padding: 20px;
  border-radius: 12px;
  margin-bottom: 20px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 5px 0;
  color: rgba(255, 255, 255, 0.9);
}

.section-desc {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.5);
  margin: 0 0 15px 0;
}

.checkbox-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.8);
  cursor: pointer;
  min-width: 180px;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.input-small {
  width: 120px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  color: white;
  font-size: 14px;
}

.input-small:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.input-small:focus {
  outline: none;
  border-color: #00d9ff;
}

.field-label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
  min-width: 35px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 25px;
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

.btn-glass-primary {
  background: rgba(0, 217, 255, 0.2);
  border-color: rgba(0, 217, 255, 0.4);
}

.btn-glass-primary:hover:not(:disabled) {
  background: rgba(0, 217, 255, 0.3);
  border-color: rgba(0, 217, 255, 0.6);
}
</style>
