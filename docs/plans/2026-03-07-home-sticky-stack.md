# Home Sticky Stack Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Реализовать эффект "складывающихся панелей" — при прокрутке каждая следующая панель наезжает на предыдущую и прилипает поверх неё.

**Architecture:** Субстиль `.worck-table-home` добавляет `overflow-y: auto` к рабочей области — создаёт scroll-контейнер только для домашней страницы, не трогая базовый `.worck-table`. Панели используют `position: sticky` с одинаковым `top` и нарастающим `z-index` — CSS делает всё сам без JS.

**Tech Stack:** Vue 3, CSS (position: sticky), существующие классы `padGlass`, `padGlass-work`

---

### Task 1: Добавить субстиль `.worck-table-home` в style.css

**Files:**
- Modify: `maribulka-vue/src/style.css` (после строки 121, после `.worck-table { ... }`)

**Step 1: Добавить субстиль после блока `.worck-table`**

Найти в файле блок:
```css
.worck-table{
  position: fixed;
  inset: 0;
}
```

Добавить ПОСЛЕ него:
```css
.worck-table-home {
  overflow-y: auto;
  overflow-x: hidden;
}
```

**Step 2: Проверить визуально**

Запустить `npm run dev` в папке `maribulka-vue`, открыть http://localhost:5173.
Пока изменений не видно — класс ещё не подключён в App.vue.

---

### Task 2: Создать CSS файл для домашней страницы

**Files:**
- Create: `maribulka-vue/src/assets/home.css`
- Modify: `maribulka-vue/src/main.ts`

**Step 1: Создать файл `maribulka-vue/src/assets/home.css`**

```css
/* ========================================
   ДОМАШНЯЯ СТРАНИЦА — sticky-стопка панелей
   home.css
   ======================================== */

/* Обёртка всех панелей — создаёт пространство для скролла */
.home-stack {
  display: flex;
  flex-direction: column;
  /* 4 панели по 800px + отступ сверху + запас снизу */
  min-height: calc(110px + 800px * 4 + 200px);
  padding-top: 110px;
  align-items: center;
}

/* Каждая sticky-панель */
.home-panel {
  position: sticky;
  top: 110px;
  height: 800px;
  width: calc(100% - 40px);
  box-sizing: border-box;
}

/* z-index: каждая следующая панель перекрывает предыдущую */
.home-panel:nth-child(1) { z-index: 10; }
.home-panel:nth-child(2) { z-index: 20; }
.home-panel:nth-child(3) { z-index: 30; }
.home-panel:nth-child(4) { z-index: 40; }

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

**Step 2: Импортировать в `maribulka-vue/src/main.ts`**

Добавить строку после `import './assets/animations.css'`:
```typescript
import './assets/home.css'
```

---

### Task 3: Подключить классы в App.vue и Home.vue

**Files:**
- Modify: `maribulka-vue/src/App.vue`
- Modify: `maribulka-vue/src/components/home/Home.vue`

**Step 1: В App.vue добавить динамический класс на `.worck-table`**

Найти строку:
```html
<div class="worck-table">
```

Заменить на:
```html
<div class="worck-table" :class="{ 'worck-table-home': navStore.currentPage === 'home' }">
```

**Step 2: В Home.vue обернуть панели в `.home-stack` и добавить класс `.home-panel`**

Заменить `<template>` на:
```html
<template>
  <div class="home-stack">

    <!-- Панель 1 -->
    <div class="home-panel padGlass padGlass-work">
      Панель 1
    </div>

    <!-- Панель 2 -->
    <div class="home-panel padGlass padGlass-work">
      Панель 2
    </div>

    <!-- Панель 3 -->
    <div class="home-panel padGlass padGlass-work">
      Панель 3
    </div>

    <!-- Панель 4 -->
    <div class="home-panel padGlass padGlass-work">
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

**Step 3: Проверить в браузере**

Открыть http://localhost:5173. Ожидаемое поведение:
- 4 панели видны вертикально
- При прокрутке вниз панель 2 наезжает на панель 1 и прилипает поверх неё
- Затем панель 3 наезжает на панель 2
- Затем панель 4 наезжает на панель 3
- При переходе на другую страницу (LaunchPad) скролл исчезает — `.worck-table-home` не применяется
