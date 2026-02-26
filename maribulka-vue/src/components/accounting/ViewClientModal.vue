<script setup lang="ts">
import { computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline } from '@mdi/js'

const props = defineProps<{
  isVisible: boolean
  client: any | null
}>()
const emit = defineEmits(['close'])

const clientInfo = computed(() => {
  if (!props.client) return null

  return {
    id: props.client.id,
    name: props.client.name,
    phone: props.client.phone,
    notes: props.client.notes || '—',
    totalBookings: props.client.total_bookings || 0
  }
})
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-glass">
        <div class="modal-glassTitle">Информация о клиенте</div>

        <div v-if="clientInfo" class="delivery-info">
          <p><strong>ID:</strong> {{ clientInfo.id }}</p>
          <p><strong>ФИО:</strong> {{ clientInfo.name }}</p>
          <p><strong>Телефон:</strong> <a :href="`tel:${clientInfo.phone}`">{{ clientInfo.phone }}</a></p>
          <p><strong>Количество съёмок:</strong> {{ clientInfo.totalBookings }}</p>
          <p><strong>Примечание:</strong> {{ clientInfo.notes }}</p>
        </div>

        <div class="ButtonFooter PosRight">
          <button class="buttonGL" @click="emit('close')">
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
