<script setup lang="ts">
import { computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiAccountPlusOutline, mdiEyeOutline, mdiFileEditOutline, mdiShieldAccountOutline, mdiAccountOffOutline } from '@mdi/js'

interface User {
  id: number
  full_name: string | null
  login: string
  role: number
  fired_at?: string | null
}

const props = defineProps<{ user: User; currentRole: number; isEmpty?: boolean }>()
const emit = defineEmits(['close', 'add', 'view', 'edit', 'fire', 'permissions'])

// id=1 СисАдмин, id=2 Директор, id=3 Руководитель
const canCreate = computed(() => [1, 2, 3].includes(props.currentRole))

const canEdit = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if (props.currentRole === 1) return ![1, 2, 3].includes(props.user.role)
  if (props.currentRole === 2) return true
  if (props.currentRole === 3) return ![1, 2].includes(props.user.role)
  return false
})

const canFire = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if (props.currentRole === 1) return false
  if ([1, 2].includes(props.user.role)) return false
  if (props.currentRole === 2) return true
  if (props.currentRole === 3) return ![1, 2, 3].includes(props.user.role)
  return false
})

const canPermissions = computed(() => {
  if (props.isEmpty || props.user.fired_at) return false
  if ([1, 2].includes(props.user.role)) return false
  if (props.currentRole === 1) return ![1, 2, 3].includes(props.user.role)
  if (props.currentRole === 2) return true
  return false
})
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ user.full_name ?? user.login }}</div>
        <div class="ButtonFooter PosColumn">
          <button class="btnGlass iconTextStart" :disabled="props.isEmpty" @click="$emit('view')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiEyeOutline" class="btn-icon" />
            <span>Просмотр</span>
          </button>
          <button v-if="canCreate || props.isEmpty" class="btnGlass iconTextStart" @click="$emit('add')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiAccountPlusOutline" class="btn-icon" />
            <span>Добавить пользователя</span>
          </button>
          <button v-if="canEdit" class="btnGlass iconTextStart" @click="$emit('edit')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiFileEditOutline" class="btn-icon" />
            <span>Редактировать</span>
          </button>
          <button v-if="canPermissions" class="btnGlass iconTextStart" @click="$emit('permissions')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiShieldAccountOutline" class="btn-icon" />
            <span>Изменить права</span>
          </button>
          <button v-if="canFire" class="btnGlass iconTextStart" @click="$emit('fire')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiAccountOffOutline" class="btn-icon" />
            <span>Уволить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
