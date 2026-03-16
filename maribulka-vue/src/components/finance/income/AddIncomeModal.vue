<script setup lang="ts">
import { ref } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useFinanceStore } from '../../../stores/finance'
import { useAuthStore } from '../../../stores/auth'
import AlertModal from '../../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  dateFrom: string
  dateTo: string
}>()

const emit = defineEmits(['close'])

const financeStore = useFinanceStore()
const authStore = useAuthStore()
const amount = ref('')
const notes = ref('')
const showAlert = ref(false)
const alertMessage = ref('')

const today = new Date().toISOString().split('T')[0]

async function handleSave() {
  if (!amount.value) return
  try {
    const res = await fetch('/api/income.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: authStore.userId,
        amount: parseFloat(amount.value),
        date: today,
        notes: notes.value || null
      })
    })
    const result = await res.json()
    if (result.success) {
      await financeStore.fetchIncome(props.dateFrom, props.dateTo)
      amount.value = ''
      notes.value = ''
      emit('close')
    } else {
      alertMessage.value = 'Ошибка: ' + (result.error || 'Не удалось внести платёж')
      showAlert.value = true
    }
  } catch {
    alertMessage.value = 'Ошибка сети'
    showAlert.value = true
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Внести в кассу</div>

        <div class="info-row">
          <span class="info-label">Дата:</span>
          <span class="info-value">{{ today }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Вносит:</span>
          <span class="info-value">{{ authStore.userName }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Сумма:</span>
          <input type="number" class="modal-input" v-model="amount" min="1" placeholder="0" />
        </div>
        <div class="info-row">
          <span class="info-label">Примечания:</span>
          <input type="text" class="modal-input" v-model="notes" placeholder="—" />
        </div>

        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" :disabled="!amount" @click="handleSave">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Сохранить</span>
          </button>
        </div>
      </div>
    </div>

    <AlertModal :is-visible="showAlert" :message="alertMessage" @close="showAlert = false" />
  </Teleport>
</template>
