<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiClose } from '@mdi/js'

interface Client {
  id: number
  name: string
  phone: string
  total_bookings: number
  notes: string | null
  created_at: string | null
}

defineProps<{ client: Client }>()
const emit = defineEmits(['close'])

function formatDate(d: string | null): string {
  if (!d) return '—'
  const p = d.split('T')[0]?.split(' ')[0]?.split('-')
  return p ? `${p[2]}.${p[1]}.${p[0]}` : '—'
}
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ client.name }}</div>

        <div class="info-row">
          <span class="info-label">Телефон:</span>
          <span class="info-value">{{ client.phone }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Записей на съёмку:</span>
          <span class="info-value">{{ client.total_bookings }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Добавлен:</span>
          <span class="info-value">{{ formatDate(client.created_at) }}</span>
        </div>
        <div v-if="client.notes" class="info-row">
          <span class="info-label">Примечания:</span>
          <span class="info-value">{{ client.notes }}</span>
        </div>

        <div class="ButtonFooter PosCenter">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiClose" class="btn-icon" />
            <span>Закрыть</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
