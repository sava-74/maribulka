<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiFileEditOutline, mdiShieldAccountOutline, mdiAccountOffOutline } from '@mdi/js'

interface User {
  id: number
  full_name: string | null
  login: string
  role: string
}

const props = defineProps<{ user: User; isAdmin: boolean }>()
const emit = defineEmits(['close', 'edit', 'fire', 'permissions'])
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay" @click.self="$emit('close')">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ user.full_name ?? user.login }}</div>
        <div class="ButtonFooter PosColumn">
          <button class="btnGlass iconTextStart" @click="$emit('edit')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiFileEditOutline" class="btn-icon" />
            <span>Редактировать</span>
          </button>
          <button v-if="isAdmin" class="btnGlass iconTextStart" @click="$emit('permissions')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiShieldAccountOutline" class="btn-icon" />
            <span>Изменить права</span>
          </button>
          <button class="btnGlass iconTextStart" @click="$emit('fire')">
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
