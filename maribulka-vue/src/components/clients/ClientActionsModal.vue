<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiAccountPlusOutline, mdiEyeOutline, mdiFileEditOutline, mdiDeleteOutline, mdiClose } from '@mdi/js'
import ConfirmModal from '../ConfirmModal.vue'

interface Client {
  id: number
  name: string
  phone: string
  total_bookings: number
  notes: string | null
  created_at: string | null
}

const props = defineProps<{ client: Client; currentRole: number; isEmpty?: boolean }>()
const emit = defineEmits(['close', 'add', 'view', 'edit', 'delete'])

const canFull = computed(() => [1, 2, 3].includes(props.currentRole))

const hasRelations = ref(true)
const showConfirm = ref(false)

onMounted(async () => {
  if (props.isEmpty || !props.client?.id) return
  const res = await fetch(`/api/clients.php?check_relations=1&id=${props.client.id}`, { credentials: 'include' })
  const data = await res.json()
  hasRelations.value = data === true
})

const canDelete = computed(() => canFull.value && !props.isEmpty && !hasRelations.value)

async function onDeleteConfirm() {
  showConfirm.value = false
  const res = await fetch(`/api/clients.php?action=delete&id=${props.client.id}`, {
    method: 'DELETE',
    credentials: 'include',
  })
  const data = await res.json()
  if (data.success) emit('delete')
}
</script>

<template>
  <ConfirmModal
    :isVisible="showConfirm"
    title="Удалить клиента"
    :message="`Удалить клиента «${client.name}»? Это действие нельзя отменить.`"
    @confirm="onDeleteConfirm"
    @cancel="showConfirm = false"
  />
  <Teleport to="body">
    <div class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ isEmpty ? 'Клиенты' : client.name }}</div>
        <div class="ButtonFooter PosColumn">
          <button class="btnGlass iconTextStart" :disabled="props.isEmpty" @click="$emit('view')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiEyeOutline" class="btn-icon" />
            <span>Просмотр</span>
          </button>
          <button v-if="canFull || props.isEmpty" class="btnGlass iconTextStart" @click="$emit('add')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiAccountPlusOutline" class="btn-icon" />
            <span>Добавить клиента</span>
          </button>
          <button v-if="canFull && !props.isEmpty" class="btnGlass iconTextStart" @click="$emit('edit')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiFileEditOutline" class="btn-icon" />
            <span>Редактировать</span>
          </button>
          <button v-if="canDelete" class="btnGlass iconTextStart" @click="showConfirm = true">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiDeleteOutline" class="btn-icon" />
            <span>Удалить</span>
          </button>
          <button class="btnGlass iconTextStart" @click="$emit('close')">
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
