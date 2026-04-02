<script setup lang="ts">
import { ref, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheck, mdiClose } from '@mdi/js'
import AlertModal from '../AlertModal.vue'
import ValidAlertModal from '../ValidAlertModal.vue'
import PadLoader from '../ui/padLoader/PadLoader.vue'
import SwitchToggle from '../ui/SwitchToggle/SwitchToggle.vue'
import DatePicker from '../ui/datePicker/DatePicker.vue'

const props = defineProps<{ promotionId: number | null }>()
const emit = defineEmits(['close', 'save'])

const isCreating = ref(!props.promotionId)
const alertMessage = ref('')
const validErrors = ref<string[]>([])
const showValidAlert = ref(false)
const isLoading = ref(true)
const loadProgress = ref(0)

const form = ref({
  name: '',
  discount_percent: 0,
  start_date: '',
  end_date: '',
  is_active: true,
})

onMounted(async () => {
  if (props.promotionId) {
    const res = await fetch(`/api/promotions.php?action=get&id=${props.promotionId}`, { credentials: 'include' })
    const data = await res.json()
    if (data.success) {
      const p = data.data
      form.value = {
        name: p.name ?? '',
        discount_percent: p.discount_percent ?? 0,
        start_date: p.start_date ?? '',
        end_date: p.end_date ?? '',
        is_active: !!p.is_active,
      }
    }
  }
  loadProgress.value = 100
  await new Promise(resolve => setTimeout(resolve, 250))
  isLoading.value = false
})

function validate(): boolean {
  validErrors.value = []
  if (!form.value.name.trim()) validErrors.value.push('Укажите название акции')
  if (form.value.discount_percent <= 0 || form.value.discount_percent > 100) validErrors.value.push('Скидка должна быть от 1 до 100%')
  if (form.value.start_date && form.value.end_date && form.value.start_date > form.value.end_date) validErrors.value.push('Дата начала не может быть позже даты окончания')
  return validErrors.value.length === 0
}

async function onSave() {
  if (!validate()) { showValidAlert.value = true; return }

  const action = isCreating.value ? 'create' : 'update'
  const body: any = {
    name: form.value.name.trim(),
    discount_percent: form.value.discount_percent,
    start_date: form.value.start_date || null,
    end_date: form.value.end_date || null,
    is_active: form.value.is_active ? 1 : 0,
  }
  if (!isCreating.value) body.id = props.promotionId

  const res = await fetch(`/api/promotions.php?action=${action}`, {
    method: 'POST', credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  })
  const data = await res.json()
  if (data.success) { emit('save') } else { alertMessage.value = data.message || 'Ошибка при сохранении' }
}
</script>

<template>
  <PadLoader v-if="isLoading" :progress="loadProgress" />
  <ValidAlertModal :isVisible="showValidAlert" :messages="validErrors" @close="showValidAlert = false" />
  <AlertModal :isVisible="!!alertMessage" :message="alertMessage" @close="alertMessage = ''" />
  <Teleport v-if="!isLoading" to="body">
    <div class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ isCreating ? 'Новая акция' : 'Редактировать акцию' }}</div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Название *</label>
            <input class="modal-input" v-model="form.name" type="text" placeholder="Название акции" />
          </div>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Скидка, % *</label>
            <input class="modal-input" v-model.number="form.discount_percent" type="number" min="1" max="100" placeholder="0" />
          </div>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Дата начала</label>
            <DatePicker v-model="form.start_date" mode="single" placeholder="Начало (не обязательно)" :showToday="false" />
          </div>
          <div class="input-field">
            <label class="input-label">Дата окончания</label>
            <DatePicker v-model="form.end_date" mode="single" placeholder="Конец (не обязательно)" :showToday="false" />
          </div>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Активна</label>
            <div style="padding-top: 4px">
              <SwitchToggle v-model="form.is_active" />
            </div>
          </div>
        </div>

        <div class="ButtonFooter PosSpace">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span><span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiClose" class="btn-icon" /><span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="onSave">
            <span class="inner-glow"></span><span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheck" class="btn-icon" /><span>Сохранить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
