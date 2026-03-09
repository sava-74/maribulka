<script setup lang="ts">
// @ts-nocheck
import { ref, computed, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiViewDashboardEditOutline } from '@mdi/js'
import { useAuthStore } from '../../stores/auth'
import { useHomeStore } from '../../stores/home'
import { useStackFade } from '../../composables/useStackFade'
import EditBlockModal from './EditBlockModal.vue'

const authStore = useAuthStore()
const homeStore = useHomeStore()

const scrollAreaRef = ref<HTMLElement | null>(null)

const panel1 = ref<HTMLElement | null>(null)
const panel2 = ref<HTMLElement | null>(null)
const panel3 = ref<HTMLElement | null>(null)
const panel4 = ref<HTMLElement | null>(null)
const panelsRef = computed(() =>
  [panel1.value, panel2.value, panel3.value, panel4.value].filter(Boolean) as HTMLElement[]
)

useStackFade(scrollAreaRef, panelsRef)

const editingBlock = ref<number | null>(null)

onMounted(() => {
  homeStore.fetchBlock(1)
  homeStore.fetchBlock(2)
  homeStore.fetchBlock(3)
  homeStore.fetchBlock(4)
})
</script>

<template>
  <div ref="scrollAreaRef" class="home-scroll-area">
    <div class="home-stack">

      <!-- Панель 1 -->
      <div ref="panel1" class="home-panel">
        <div class="home-panel-glass"></div>
        <div class="home-panel-content">
          <button
            v-if="authStore.isAdmin"
            class="btnGlass icon-only home-panel-edit-btn"
            @click="editingBlock = 1"
            aria-label="Редактировать"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiViewDashboardEditOutline" class="sb-icon" />
          </button>
          <div class="home-block-content" v-html="homeStore.blocks[1] ?? ''"></div>
        </div>
      </div>

      <!-- Панель 2 -->
      <div ref="panel2" class="home-panel">
        <div class="home-panel-glass"></div>
        <div class="home-panel-content">
          <button
            v-if="authStore.isAdmin"
            class="btnGlass icon-only home-panel-edit-btn"
            @click="editingBlock = 2"
            aria-label="Редактировать"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiViewDashboardEditOutline" class="sb-icon" />
          </button>
          <div class="home-block-content" v-html="homeStore.blocks[2] ?? ''"></div>
        </div>
      </div>

      <!-- Панель 3 -->
      <div ref="panel3" class="home-panel">
        <div class="home-panel-glass"></div>
        <div class="home-panel-content">
          <button
            v-if="authStore.isAdmin"
            class="btnGlass icon-only home-panel-edit-btn"
            @click="editingBlock = 3"
            aria-label="Редактировать"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiViewDashboardEditOutline" class="sb-icon" />
          </button>
          <div class="home-block-content" v-html="homeStore.blocks[3] ?? ''"></div>
        </div>
      </div>

      <!-- Панель 4 -->
      <div ref="panel4" class="home-panel">
        <div class="home-panel-glass"></div>
        <div class="home-panel-content">
          <button
            v-if="authStore.isAdmin"
            class="btnGlass icon-only home-panel-edit-btn"
            @click="editingBlock = 4"
            aria-label="Редактировать"
          >
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiViewDashboardEditOutline" class="sb-icon" />
          </button>
          <div class="home-block-content" v-html="homeStore.blocks[4] ?? ''"></div>
        </div>
      </div>

    </div>

    <!-- Модалка редактирования блока -->
    <EditBlockModal
      :is-visible="editingBlock !== null"
      :block-id="editingBlock ?? 1"
      @close="editingBlock = null"
    />
  </div>
</template>
