<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiClose, mdiAccountOffOutline } from '@mdi/js'

interface User {
  id: number
  full_name: string | null
  login: string
}

const props = defineProps<{ user: User }>()
const emit = defineEmits(['close', 'confirm'])
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">Уволить сотрудника</div>
        <p class="modal-message">
          Уволить <strong>{{ user.full_name ?? user.login }}</strong>?<br>
          Это действие необратимо. Сотрудник не сможет войти в систему.
          При повторном приёме создаётся новая запись.
        </p>
        <div class="ButtonFooter PosCenter">
          <button class="btnGlass iconText" @click="$emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiClose" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="$emit('confirm')">
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
