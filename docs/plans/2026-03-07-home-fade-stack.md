# Home Fade Stack Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** При прокрутке панели домашней страницы плавно появляются/исчезают — новая панель проявляется по мере наезда на предыдущую.

**Architecture:** JS composable `useStackFade` слушает `scroll` на контейнере `.worck-table-home`, вычисляет `opacity` каждой панели по `scrollTop` и применяет через CSS-переменную `--panel-opacity`. Двухслойная структура панели (`home-panel-glass` + `home-panel-content`) нужна чтобы `opacity` и `backdrop-filter` не конфликтовали.

**Tech Stack:** Vue 3 + TypeScript, composable, CSS-переменные, scroll event

---

### Task 1: Обновить CSS — двухслойная структура + opacity

**Files:**
- Modify: `maribulka-vue/src/assets/home.css`

**Step 1: Заменить содержимое `home.css` на следующее**

```css
/* ========================================
   ДОМАШНЯЯ СТРАНИЦА — sticky-стопка панелей
   home.css
   ======================================== */

/* Обёртка всех панелей — создаёт пространство для скролла */
.home-stack {
  display: flex;
  flex-direction: column;
  min-height: calc(110px + 800px * 4 + 200px);
  padding-top: 110px;
  align-items: center;
}

/* Внешняя обёртка панели — sticky + opacity */
.home-panel {
  position: sticky;
  top: 110px;
  height: 800px;
  width: calc(100% - 40px);
  box-sizing: border-box;
  opacity: var(--panel-opacity, 1);
}

/* z-index: каждая следующая панель перекрывает предыдущую */
.home-panel:nth-child(1) { z-index: 10; }
.home-panel:nth-child(2) { z-index: 20; }
.home-panel:nth-child(3) { z-index: 30; }
.home-panel:nth-child(4) { z-index: 40; }

/* Внутренний стеклянный слой — backdrop-filter здесь, не на .home-panel */
.home-panel-glass {
  position: absolute;
  inset: 0;
  border-radius: var(--padRadius);
  backdrop-filter: blur(var(--padBlur));
  -webkit-backdrop-filter: blur(var(--padBlur));
  background: var(--glass-bg);
  border: 2px solid var(--glass-border);
  border-style: groove;
  box-shadow: var(--glass-shadow);
  overflow: hidden;
}

/* Контентный слой поверх стекла */
.home-panel-content {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

/* ========== МОБИЛЬНАЯ АДАПТАЦИЯ ========== */
@media (pointer: coarse) {
  .home-stack {
    padding-top: 80px;
    min-height: calc(80px + 600px * 4 + 200px);
  }

  .home-panel {
    top: 80px;
    height: 600px;
    width: 100%;
  }
}
```

**Важно:** `backdrop-filter` убирается с `.home-panel` и переносится в `.home-panel-glass`. Это позволяет `opacity` на `.home-panel` работать корректно — стеклянный эффект не ломается.

---

### Task 2: Создать composable `useStackFade`

**Files:**
- Create: `maribulka-vue/src/composables/useStackFade.ts`

**Step 1: Создать файл**

```typescript
import { onMounted, onUnmounted, type Ref } from 'vue'

const PANEL_HEIGHT = 800
const STICKY_TOP = 110
const FADE_DISTANCE = 400  // пикселей — зона перехода (половина панели)

export function useStackFade(
  scrollContainer: Ref<HTMLElement | null>,
  panels: Ref<HTMLElement[]>
) {
  function updateOpacity() {
    const container = scrollContainer.value
    if (!container || !panels.value.length) return

    const scrollTop = container.scrollTop

    panels.value.forEach((panel, index) => {
      let opacity = 1

      if (index === 0) {
        // Панель 1: исчезает когда панель 2 начинает наезжать
        const fadeStart = PANEL_HEIGHT - FADE_DISTANCE
        const fadeEnd = PANEL_HEIGHT
        if (scrollTop >= fadeEnd) {
          opacity = 0
        } else if (scrollTop > fadeStart) {
          opacity = 1 - (scrollTop - fadeStart) / FADE_DISTANCE
        }
      } else {
        // Панели 2, 3, 4: появляются при наезде, исчезают при следующем наезде
        const overlapPos = index * PANEL_HEIGHT  // момент полного перекрытия

        // Появление
        const fadeInStart = overlapPos - FADE_DISTANCE
        const fadeInEnd = overlapPos
        // Исчезновение (если есть следующая панель)
        const fadeOutStart = overlapPos + PANEL_HEIGHT - FADE_DISTANCE
        const fadeOutEnd = overlapPos + PANEL_HEIGHT

        if (scrollTop < fadeInStart) {
          opacity = 0
        } else if (scrollTop < fadeInEnd) {
          opacity = (scrollTop - fadeInStart) / FADE_DISTANCE
        } else if (index < panels.value.length - 1 && scrollTop > fadeOutEnd) {
          opacity = 0
        } else if (index < panels.value.length - 1 && scrollTop > fadeOutStart) {
          opacity = 1 - (scrollTop - fadeOutStart) / FADE_DISTANCE
        } else {
          opacity = 1
        }
      }

      panel.style.setProperty('--panel-opacity', String(Math.max(0, Math.min(1, opacity))))
    })
  }

  function onScroll() {
    updateOpacity()
  }

  onMounted(() => {
    const container = scrollContainer.value
    if (container) {
      container.addEventListener('scroll', onScroll, { passive: true })
      updateOpacity()
    }
  })

  onUnmounted(() => {
    const container = scrollContainer.value
    if (container) {
      container.removeEventListener('scroll', onScroll)
    }
  })
}
```

---

### Task 3: Обновить App.vue — добавить ref на scroll-контейнер

**Files:**
- Modify: `maribulka-vue/src/App.vue`

**Step 1: Добавить ref на scroll-контейнер и передать в Home**

В `<script setup>` добавить:
```typescript
const scrollContainerRef = ref<HTMLElement | null>(null)
```

В шаблоне изменить div с `.worck-table`:
```html
<div
  ref="scrollContainerRef"
  class="worck-table"
  :class="{ 'worck-table-home': navStore.currentPage === 'home' }"
>
  <LaunchPad ref="launchpadRef" :isVisible="showLaunchpad" :origin="launchpadOrigin" @close="showLaunchpad = false" />
  <Home v-if="navStore.currentPage === 'home'" :scroll-container="scrollContainerRef" />
</div>
```

---

### Task 4: Обновить Home.vue — принять prop и подключить composable

**Files:**
- Modify: `maribulka-vue/src/components/home/Home.vue`

**Step 1: Обновить `<script setup>`**

Заменить всю секцию `<script setup>`:

```typescript
// @ts-nocheck
import { ref, computed, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiTextBoxPlusOutline, mdiCameraPlusOutline } from '@mdi/js'
import { useAuthStore } from '../../stores/auth'
import { useHomeStore } from '../../stores/home'
import { useStackFade } from '../../composables/useStackFade'
import UploadPhotoModal from '../home/UploadPhotoModal.vue'
import EditStudioDescriptionModal from '../home/EditStudioDescriptionModal.vue'

const props = defineProps<{
  scrollContainer: HTMLElement | null
}>()

const authStore = useAuthStore()
const homeStore = useHomeStore()

const showUploadModal = ref(false)
const showEditDescriptionModal = ref(false)
const selectedPosition = ref(0)

// Refs на DOM-элементы панелей
const panel1 = ref<HTMLElement | null>(null)
const panel2 = ref<HTMLElement | null>(null)
const panel3 = ref<HTMLElement | null>(null)
const panel4 = ref<HTMLElement | null>(null)
const panelsRef = computed(() =>
  [panel1.value, panel2.value, panel3.value, panel4.value].filter(Boolean) as HTMLElement[]
)

// Scroll-контейнер как Ref для composable
import { toRef } from 'vue'
const scrollContainerRef = toRef(props, 'scrollContainer')

useStackFade(scrollContainerRef, panelsRef)

onMounted(() => {
  homeStore.fetchPhotos()
  homeStore.fetchDescription()
})

const descriptionHtml = computed(() => {
  const html = homeStore.description
  return html.replace(/<a /g, '<a target="_blank" rel="noopener noreferrer" ')
})

function handleAddPhoto(position: number) {
  selectedPosition.value = position
  showUploadModal.value = true
}

function handleDescriptionClick(event: MouseEvent) {
  let target = event.target as HTMLElement
  while (target && target.tagName !== 'A' && target !== event.currentTarget) {
    target = target.parentElement as HTMLElement
  }
  if (target && target.tagName === 'A') {
    const link = target as HTMLAnchorElement
    event.preventDefault()
    event.stopPropagation()
    if (link.href) {
      window.open(link.href, '_blank', 'noopener,noreferrer')
    }
  }
}
```

**Step 2: Обновить `<template>` — двухслойная структура панелей**

```html
<template>
  <div class="home-stack">

    <!-- Панель 1 -->
    <div ref="panel1" class="home-panel">
      <div class="home-panel-glass"></div>
      <div class="home-panel-content">
        Панель 1
      </div>
    </div>

    <!-- Панель 2 -->
    <div ref="panel2" class="home-panel">
      <div class="home-panel-glass"></div>
      <div class="home-panel-content">
        Панель 2
      </div>
    </div>

    <!-- Панель 3 -->
    <div ref="panel3" class="home-panel">
      <div class="home-panel-glass"></div>
      <div class="home-panel-content">
        Панель 3
      </div>
    </div>

    <!-- Панель 4 -->
    <div ref="panel4" class="home-panel">
      <div class="home-panel-glass"></div>
      <div class="home-panel-content">
        Панель 4
      </div>
    </div>

    <!-- Модалка загрузки фото -->
    <UploadPhotoModal
      :is-visible="showUploadModal"
      :position="selectedPosition"
      @close="showUploadModal = false"
    />

    <!-- Модалка редактирования описания -->
    <EditStudioDescriptionModal
      :is-visible="showEditDescriptionModal"
      @close="showEditDescriptionModal = false"
    />
  </div>
</template>
```

**Step 3: Проверить в браузере**

Открыть http://localhost:5173. Ожидаемое поведение:
- Панель 1 видна, панели 2-4 невидимы (opacity: 0)
- При прокрутке панель 2 плавно появляется по мере наезда на панель 1
- Одновременно панель 1 плавно исчезает
- Аналогично для 3 и 4
