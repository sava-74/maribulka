<script setup lang="ts">
import { ref, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheck, mdiClose } from '@mdi/js'
import AlertModal from '../AlertModal.vue'
import ValidAlertModal from '../ValidAlertModal.vue'
import PadLoader from '../ui/padLoader/PadLoader.vue'

const props = defineProps<{ clientId: number | null }>()
const emit = defineEmits(['close', 'save'])

const isCreating = ref(!props.clientId)
const alertMessage = ref('')
const validErrors = ref<string[]>([])
const showValidAlert = ref(false)
const isLoading = ref(true)
const loadProgress = ref(0)

const form = ref({
  name: '',
  phone: '',
  notes: '',
})

onMounted(async () => {
  if (props.clientId) {
    const res = await fetch(`/api/clients.php?action=get&id=${props.clientId}`, { credentials: 'include' })
    const data = await res.json()
    if (data.success) {
      form.value = {
        name: data.data.name ?? '',
        phone: data.data.phone ?? '',
        notes: data.data.notes ?? '',
      }
    }
  }
  loadProgress.value = 100
  await new Promise(resolve => setTimeout(resolve, 250))
  isLoading.value = false
})

function formatPhone(event: Event) {
  const input = event.target as HTMLInputElement
  let value = input.value.replace(/\D/g, '')
  if (value.length > 0 && value[0] !== '7') value = '7' + value
  if (value.length > 11) value = value.substring(0, 11)
  let formatted = '+7'
  if (value.length > 1) formatted += '(' + value.substring(1, 4)
  if (value.length >= 5) formatted += ')' + value.substring(4, 7)
  if (value.length >= 8) formatted += '-' + value.substring(7, 9)
  if (value.length >= 10) formatted += '-' + value.substring(9, 11)
  form.value.phone = formatted
}

function filterFio(event: Event) {
  const input = event.target as HTMLInputElement
  const cleaned = input.value.replace(/[^а-яёА-ЯЁ\s-]/g, '')
  if (cleaned !== input.value) alertMessage.value = 'Используйте кириллицу'
  input.value = cleaned
  form.value.name = cleaned
}

function validate(): boolean {
  validErrors.value = []
  if (!form.value.name.trim()) validErrors.value.push('Укажите ФИО клиента')
  if (!form.value.phone.trim()) validErrors.value.push('Укажите телефон')
  return validErrors.value.length === 0
}

async function onSave() {
  if (!validate()) { showValidAlert.value = true; return }

  const action = isCreating.value ? 'create' : 'update'
  const body: any = { name: form.value.name.trim(), phone: form.value.phone.trim(), notes: form.value.notes || null }
  if (!isCreating.value) body.id = props.clientId

  const res = await fetch(`/api/clients.php?action=${action}`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  })
  const data = await res.json()
  if (data.success) {
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
        <div class="modal-glassTitle">{{ isCreating ? 'Новый клиент' : 'Редактировать клиента' }}</div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">ФИО *</label>
            <input class="modal-input" v-model="form.name" type="text" placeholder="Полное имя" @input="filterFio" />
          </div>
        </div>

        <div class="input-row">
          <div class="input-field">
            <label class="input-label">Телефон *</label>
            <input class="modal-input" v-model="form.phone" type="tel" placeholder="+7(999)999-99-99" maxlength="16" @input="formatPhone" />
          </div>
        </div>

        <div class="input-group">
          <label class="input-label">Примечания</label>
          <textarea class="modal-input" v-model="form.notes" rows="3" placeholder="Примечания"></textarea>
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
