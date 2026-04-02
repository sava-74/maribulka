<script setup lang="ts">
import { ref, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheck, mdiClose } from '@mdi/js'
import AlertModal from '../AlertModal.vue'
import ValidAlertModal from '../ValidAlertModal.vue'
import PadLoader from '../ui/padLoader/PadLoader.vue'
import SwitchToggle from '../ui/SwitchToggle/SwitchToggle.vue'

const props = defineProps<{ categoryId: number | null }>()
const emit = defineEmits(['close', 'save'])

const isCreating = ref(!props.categoryId)
const alertMessage = ref('')
const validErrors = ref<string[]>([])
const showValidAlert = ref(false)
const isLoading = ref(true)
const loadProgress = ref(0)

const form = ref({ name: '', is_active: true })

onMounted(async () => {
  if (props.categoryId) {
    const res = await fetch(`/api/expense-categories.php?action=get&id=${props.categoryId}`, { credentials: 'include' })
    const data = await res.json()
    if (data.success) {
      form.value = { name: data.data.name ?? '', is_active: !!data.data.is_active }
    }
  }
  loadProgress.value = 100
  await new Promise(resolve => setTimeout(resolve, 250))
  isLoading.value = false
})

async function onSave() {
  validErrors.value = []
  if (!form.value.name.trim()) { validErrors.value.push('Укажите название категории'); showValidAlert.value = true; return }

  const action = isCreating.value ? 'create' : 'update'
  const body: any = { name: form.value.name.trim(), is_active: form.value.is_active ? 1 : 0 }
  if (!isCreating.value) body.id = props.categoryId

  const res = await fetch(`/api/expense-categories.php?action=${action}`, {
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
        <div class="modal-glassTitle">{{ isCreating ? 'Новая категория' : 'Редактировать категорию' }}</div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Название *</label>
            <input class="modal-input" v-model="form.name" type="text" placeholder="Название категории" />
          </div>
          <div class="input-field input-field-auto">
            <label class="input-label">Активна</label>
            <SwitchToggle v-model="form.is_active" />
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
