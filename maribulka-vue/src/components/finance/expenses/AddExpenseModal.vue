<script setup lang="ts">
import { ref, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import { useFinanceStore } from '../../../stores/finance'
import { useReferencesStore } from '../../../stores/references'
import { useAuthStore } from '../../../stores/auth'
import AlertModal from '../../AlertModal.vue'

const props = defineProps<{
  isVisible: boolean
  dateFrom: string
  dateTo: string
}>()

const emit = defineEmits(['close'])

const financeStore = useFinanceStore()
const refsStore = useReferencesStore()
const authStore = useAuthStore()

const today = new Date().toISOString().split('T')[0]
const categoryId = ref<number | null>(null)
const amount = ref('')
const description = ref('')
const showAlert = ref(false)
const alertMessage = ref('')

onMounted(async () => {
  if (refsStore.expenseCategories.length === 0) {
    await refsStore.fetchExpenseCategories()
  }
})

async function handleSave() {
  if (!amount.value || !categoryId.value) return
  try {
    const result = await financeStore.createExpense({
      date: today,
      category: categoryId.value,
      amount: parseFloat(amount.value),
      description: description.value || null,
      created_by: authStore.userId
    })
    if (result.success) {
      await financeStore.fetchExpenses(props.dateFrom, props.dateTo)
      categoryId.value = null
      amount.value = ''
      description.value = ''
      emit('close')
    } else {
      alertMessage.value = 'Ошибка: ' + (result.error || 'Не удалось создать расход')
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
        <div class="modal-glassTitle">Выдать расход</div>

        <div class="info-row">
          <span class="info-label">Дата:</span>
          <span class="info-value">{{ today }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Выдаёт:</span>
          <span class="info-value">{{ authStore.userName }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Категория:</span>
          <select class="modal-input" v-model="categoryId">
            <option :value="null" disabled>— выберите —</option>
            <option v-for="cat in refsStore.expenseCategories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>
        <div class="info-row">
          <span class="info-label">Сумма:</span>
          <input type="number" class="modal-input" v-model="amount" min="1" placeholder="0" />
        </div>
        <div class="info-row">
          <span class="info-label">Описание:</span>
          <input type="text" class="modal-input" v-model="description" placeholder="—" />
        </div>

        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" :disabled="!amount || !categoryId" @click="handleSave">
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
