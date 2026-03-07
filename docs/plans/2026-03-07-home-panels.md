# Home Page — 4 панели-заглушки Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Добавить в Home.vue 4 вертикальные панели `.padGlass.padGlass-work` с текстом-заглушкой.

**Architecture:** Только изменение `<template>` в Home.vue — 4 блока с существующими CSS-классами. Скрипт не трогаем.

**Tech Stack:** Vue 3, существующие классы `padGlass`, `padGlass-work` из `padGlass.css`

---

### Task 1: Добавить 4 панели в Home.vue

**Files:**
- Modify: `maribulka-vue/src/components/home/Home.vue`

**Step 1: Заменить `<template>` в Home.vue**

Открыть файл `maribulka-vue/src/components/home/Home.vue`.

Заменить секцию `<template>` на:

```html
<template>
  <div>

    <!-- Панель 1 -->
    <div class="padGlass padGlass-work">
      Панель 1
    </div>

    <!-- Панель 2 -->
    <div class="padGlass padGlass-work">
      Панель 2
    </div>

    <!-- Панель 3 -->
    <div class="padGlass padGlass-work">
      Панель 3
    </div>

    <!-- Панель 4 -->
    <div class="padGlass padGlass-work">
      Панель 4
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

**Step 2: Проверить в браузере**

```bash
cd maribulka-vue
npm run dev
```

Открыть http://localhost:5173 — должны появиться 4 панели с glass-эффектом вертикально.

**Step 3: Commit**

```bash
git add maribulka-vue/src/components/home/Home.vue
git commit -m "home: добавить 4 панели-заглушки"
```
