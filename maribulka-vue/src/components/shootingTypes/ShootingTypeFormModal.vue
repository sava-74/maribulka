<script setup lang="ts">
import { ref, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheck, mdiClose } from '@mdi/js'
import AlertModal from '../AlertModal.vue'
import ValidAlertModal from '../ValidAlertModal.vue'
import PadLoader from '../ui/padLoader/PadLoader.vue'
import SwitchToggle from '../ui/SwitchToggle/SwitchToggle.vue'

const props = defineProps<{ shootingTypeId: number | null }>()
const emit = defineEmits(['close', 'save'])

const isCreating = ref(!props.shootingTypeId)
const alertMessage = ref('')
const validErrors = ref<string[]>([])
const showValidAlert = ref(false)
const isLoading = ref(true)
const loadProgress = ref(0)

const form = ref({
  name: '',
  base_price: 0,
  duration_minutes: 30,
  description: '',
  is_active: true,
})

onMounted(async () => {
  if (props.shootingTypeId) {
    const res = await fetch(`/api/shooting-types.php?action=get&id=${props.shootingTypeId}`, { credentials: 'include' })
    const data = await res.json()
    if (data.success) {
      const s = data.data
      form.value = {
        name: s.name ?? '',
        base_price: s.base_price ?? 0,
        duration_minutes: s.duration_minutes ?? 30,
        description: s.description ?? '',
        is_active: !!s.is_active,
      }
    }
  }
  loadProgress.value = 100
  await new Promise(resolve => setTimeout(resolve, 250))
  isLoading.value = false
})

function validate(): boolean {
  validErrors.value = []
  if (!form.value.name.trim()) validErrors.value.push('Укажите название')
  if (form.value.base_price <= 0) validErrors.value.push('Базовая цена должна быть больше 0')
  if (form.value.duration_minutes <= 0) validErrors.value.push('Длительность должна быть больше 0')
  return validErrors.value.length === 0
}

async function onSave() {
  if (!validate()) { showValidAlert.value = true; return }

  const action = isCreating.value ? 'create' : 'update'
  const body: any = {
    name: form.value.name.trim(),
    base_price: form.value.base_price,
    duration_minutes: form.value.duration_minutes,
    description: form.value.description || null,
    is_active: form.value.is_active ? 1 : 0,
  }
  if (!isCreating.value) body.id = props.shootingTypeId

  const res = await fetch(`/api/shooting-types.php?action=${action}`, {
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
        <div class="modal-glassTitle">{{ isCreating ? 'Новый тип съёмки' : 'Редактировать тип съёмки' }}</div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Название *</label>
            <input class="modal-input" v-model="form.name" type="text" placeholder="Название типа съёмки" />
          </div>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Базовая цена, руб *</label>
            <input class="modal-input" v-model.number="form.base_price" type="number" min="1" placeholder="0" />
          </div>
          <div class="input-field">
            <label class="input-label">Длительность, мин *</label>
            <input class="modal-input" v-model.number="form.duration_minutes" type="number" min="1" placeholder="30" />
          </div>
        </div>

        <div class="input-group">
          <label class="input-label">Описание</label>
          <textarea class="modal-input" v-model="form.description" rows="3" placeholder="Описание"></textarea>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Активен</label>
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
