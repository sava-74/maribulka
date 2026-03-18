<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiAccountPlusOutline, mdiFileEditOutline, mdiShieldAccountOutline, mdiAccountOffOutline } from '@mdi/js'

interface User {
  id: number
  full_name: string | null
  login: string
  role: string
}

const props = defineProps<{ user: User; isAdmin: boolean; isEmpty?: boolean }>()
const emit = defineEmits(['close', 'add', 'edit', 'fire', 'permissions'])
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay" @click.self="$emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ user.full_name ?? user.login }}</div>
        <div class="ButtonFooter PosColumn">
          <button class="btnGlass iconTextStart" @click="$emit('add')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiAccountPlusOutline" class="btn-icon" />
            <span>Добавить пользователя</span>
          </button>
          <button class="btnGlass iconTextStart" :disabled="props.isEmpty" @click="$emit('edit')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiFileEditOutline" class="btn-icon" />
            <span>Редактировать</span>
          </button>
          <button v-if="isAdmin && !props.isEmpty && user.role !== 'admin'" class="btnGlass iconTextStart" @click="$emit('permissions')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiShieldAccountOutline" class="btn-icon" />
            <span>Изменить права</span>
          </button>
          <button v-if="!props.isEmpty && user.role !== 'admin'" class="btnGlass iconTextStart" @click="$emit('fire')">
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
