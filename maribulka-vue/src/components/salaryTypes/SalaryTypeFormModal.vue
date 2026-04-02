<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheck, mdiClose } from '@mdi/js'
import AlertModal from '../AlertModal.vue'
import ValidAlertModal from '../ValidAlertModal.vue'
import PadLoader from '../ui/padLoader/PadLoader.vue'
import SwitchToggle from '../ui/SwitchToggle/SwitchToggle.vue'

const props = defineProps<{ salaryTypeId: number | null }>()
const emit = defineEmits(['close', 'save'])

const isCreating = ref(!props.salaryTypeId)

const alertMessage = ref('')
const validErrors = ref<string[]>([])
const showValidAlert = ref(false)

const form = ref({
  title: '',
  monthly_salary: false,
  salary_value: 0,
  percentage_of_the_order: false,
  the_percentage_value: 0,
  interest_dividends: false,
  value_dividend_percentages: 0,
  fixed_order: false,
  fixed_value_order: 0,
})

const isLoading = ref(true)
const loadProgress = ref(0)

onMounted(async () => {
  if (props.salaryTypeId) {
    const res = await fetch(`/api/salary-types.php?action=get&id=${props.salaryTypeId}`, { credentials: 'include' })
    const data = await res.json()
    if (data.success) {
      const s = data.data
      form.value = {
        title: s.title,
        monthly_salary: !!s.monthly_salary,
        salary_value: s.salary_value,
        percentage_of_the_order: !!s.percentage_of_the_order,
        the_percentage_value: s.the_percentage_value,
        interest_dividends: !!s.interest_dividends,
        value_dividend_percentages: s.value_dividend_percentages,
        fixed_order: !!s.fixed_order,
        fixed_value_order: s.fixed_value_order,
      }
    }
  }
  loadProgress.value = 100
  await new Promise(resolve => setTimeout(resolve, 250))
  isLoading.value = false
})

watch(() => props.salaryTypeId, (newId) => {
  isCreating.value = !newId
}, { immediate: true })

// Сброс значения при выключении переключателя
watch(() => form.value.monthly_salary, (val) => { if (!val) form.value.salary_value = 0 })
watch(() => form.value.percentage_of_the_order, (val) => { if (!val) form.value.the_percentage_value = 0 })
watch(() => form.value.interest_dividends, (val) => { if (!val) form.value.value_dividend_percentages = 0 })
watch(() => form.value.fixed_order, (val) => { if (!val) form.value.fixed_value_order = 0 })

function validate(): boolean {
  validErrors.value = []

  if (!form.value.title.trim()) {
    validErrors.value.push('Введите название типа зарплаты')
  }

  if (form.value.percentage_of_the_order && (form.value.the_percentage_value < 0 || form.value.the_percentage_value > 100)) {
    validErrors.value.push('Процент от заказа должен быть от 0 до 100')
  }

  if (form.value.interest_dividends && (form.value.value_dividend_percentages < 0 || form.value.value_dividend_percentages > 100)) {
    validErrors.value.push('Процент дивидендов должен быть от 0 до 100')
  }

  return validErrors.value.length === 0
}

async function onSave() {
  if (!validate()) {
    showValidAlert.value = true
    return
  }

  const action = isCreating.value ? 'create' : 'update'
  const body: any = {
    title: form.value.title,
    monthly_salary: form.value.monthly_salary ? 1 : 0,
    salary_value: form.value.monthly_salary ? form.value.salary_value : 0,
    percentage_of_the_order: form.value.percentage_of_the_order ? 1 : 0,
    the_percentage_value: form.value.percentage_of_the_order ? form.value.the_percentage_value : 0,
    interest_dividends: form.value.interest_dividends ? 1 : 0,
    value_dividend_percentages: form.value.interest_dividends ? form.value.value_dividend_percentages : 0,
    fixed_order: form.value.fixed_order ? 1 : 0,
    fixed_value_order: form.value.fixed_order ? form.value.fixed_value_order : 0,
  }
  if (!isCreating.value) body.id = props.salaryTypeId

  const res = await fetch(`/api/salary-types.php?action=${action}`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  })

  const data = await res.json()

  if (res.ok && data.success) {
    emit('save')
  } else {
    alertMessage.value = data.message || 'Ошибка при сохранении'
  }
}
</script>

<template>
  <PadLoader v-if="isLoading" :progress="loadProgress" />
  <ValidAlertModal :isVisible="showValidAlert" :messages="validErrors" @close="showValidAlert = false" />
  <AlertModal :isVisible="!!alertMessage" :message="alertMessage" @close="alertMessage = ''" />
  <Teleport v-if="!isLoading" to="body">
    <div class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ isCreating ? 'Новый тип зарплаты' : 'Редактирование' }}</div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Название *</label>
            <input class="modal-input" v-model="form.title" type="text" placeholder="Введите название типа зарплаты" />
          </div>
        </div>

        <div class="input-group">
          <label class="input-label">Начисления</label>

          <div class="salary-check-row">
            <SwitchToggle v-model="form.monthly_salary" />
            <span class="salary-check-label">Оклад в месяц</span>
            <input class="modal-input salary-check-input" v-model.number="form.salary_value" type="number"
              :disabled="!form.monthly_salary" placeholder="0" min="0" />
            <span class="salary-unit">руб</span>
          </div>

          <div class="salary-check-row">
            <SwitchToggle v-model="form.percentage_of_the_order" />
            <span class="salary-check-label">Процент от заказа</span>
            <input class="modal-input salary-check-input" v-model.number="form.the_percentage_value" type="number"
              :disabled="!form.percentage_of_the_order" placeholder="0" min="0" max="100" />
            <span class="salary-unit">%</span>
          </div>

          <div class="salary-check-row">
            <SwitchToggle v-model="form.interest_dividends" />
            <span class="salary-check-label">Проценты дивидендов</span>
            <input class="modal-input salary-check-input" v-model.number="form.value_dividend_percentages" type="number"
              :disabled="!form.interest_dividends" placeholder="0" min="0" max="100" />
            <span class="salary-unit">%</span>
          </div>

          <div class="salary-check-row">
            <SwitchToggle v-model="form.fixed_order" />
            <span class="salary-check-label">Фиксированное от заказа</span>
            <input class="modal-input salary-check-input" v-model.number="form.fixed_value_order" type="number"
              :disabled="!form.fixed_order" placeholder="0" min="0" />
            <span class="salary-unit">руб</span>
          </div>
        </div>

        <div class="ButtonFooter PosSpace">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiClose" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="onSave">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheck" class="btn-icon" />
            <span>Сохранить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
