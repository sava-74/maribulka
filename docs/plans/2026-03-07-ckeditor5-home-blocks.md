# CKEditor 5 — Редактор блоков домашней страницы

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Заменить TipTap на CKEditor 5, добавить 4 независимых редактируемых блока на домашней странице с загрузкой картинок на сервер и обтеканием текстом.

**Architecture:** CKEditor 5 (GPL) с SimpleUploadAdapter загружает картинки в `/media/editor/` через `api/upload-image.php`. Контент 4 панелей хранится в таблице `home_blocks` (id 1-4). Кнопка редактирования (glass-btn icon-only) видна только администратору, открывает модалку с редактором для конкретного блока.

**Tech Stack:** Vue 3 + TypeScript, CKEditor 5 GPL (`ckeditor5`, `@ckeditor/ckeditor5-vue`), PHP 8.4, MySQL, Pinia

---

### Task 1: Миграция БД — таблица home_blocks

**Files:**
- Create: `api/migrations/004_home_blocks.sql`
- Create: `api/setup_home_blocks.php`

**Step 1: Создать SQL миграцию**

```sql
-- api/migrations/004_home_blocks.sql
CREATE TABLE IF NOT EXISTS home_blocks (
  id TINYINT UNSIGNED NOT NULL,
  content LONGTEXT NOT NULL DEFAULT '',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO home_blocks (id, content) VALUES
  (1, '<p>Блок 1</p>'),
  (2, '<p>Блок 2</p>'),
  (3, '<p>Блок 3</p>'),
  (4, '<p>Блок 4</p>');
```

**Step 2: Создать setup-скрипт для запуска миграции на сервере**

```php
<?php
// api/setup_home_blocks.php
// Запускается один раз: GET /api/setup_home_blocks.php
require_once 'session.php';
initSession();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Только для администратора']);
    exit();
}

require_once 'database.php';
$pdo = Database::getInstance()->getConnection();

$sql = file_get_contents(__DIR__ . '/migrations/004_home_blocks.sql');
$pdo->exec($sql);

echo json_encode(['success' => true, 'message' => 'Таблица home_blocks создана']);
```

**Step 3: Проверить на сервере**

После деплоя открыть: `https://xn--80aac1alfd7a3a5g.xn--p1ai/api/setup_home_blocks.php`
Ожидается: `{"success":true,"message":"Таблица home_blocks создана"}`

---

### Task 2: PHP API — home_blocks.php

**Files:**
- Create: `api/home_blocks.php`

**Step 1: Создать endpoint**

```php
<?php
// api/home_blocks.php
header('Content-Type: application/json; charset=utf-8');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'http://localhost:5173',
    'https://xn--80aac1alfd7a3a5g.xn--p1ai',
    'http://xn--80aac1alfd7a3a5g.xn--p1ai'
];
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once 'database.php';
$pdo = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// GET ?id=1 — получить блок
if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id < 1 || $id > 4) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id должен быть от 1 до 4']);
        exit();
    }
    $stmt = $pdo->prepare("SELECT content FROM home_blocks WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'content' => $row['content'] ?? '']);
}

// POST { id: 1, content: "..." } — сохранить блок (только admin)
elseif ($method === 'POST') {
    require_once 'session.php';
    initSession();
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    $content = trim($input['content'] ?? '');

    if ($id < 1 || $id > 4) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id должен быть от 1 до 4']);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE home_blocks SET content = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$content, $id]);

    echo json_encode(['success' => true, 'message' => 'Сохранено']);
}

else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
}
```

**Step 2: Проверить локально**

```bash
# В браузере или curl:
curl http://localhost:5173/api/home_blocks.php?id=1
# Ожидается: {"success":true,"content":"<p>Блок 1</p>"}
```

---

### Task 3: PHP API — upload-image.php

**Files:**
- Create: `api/upload-image.php`

**Step 1: Создать endpoint загрузки**

```php
<?php
// api/upload-image.php
// CKEditor SimpleUploadAdapter ожидает: POST multipart с полем "upload"
// Возвращает: { "url": "..." } при успехе или { "error": { "message": "..." } }
header('Content-Type: application/json; charset=utf-8');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'http://localhost:5173',
    'https://xn--80aac1alfd7a3a5g.xn--p1ai',
    'http://xn--80aac1alfd7a3a5g.xn--p1ai'
];
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

// Только для администратора
require_once 'session.php';
initSession();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => ['message' => 'Доступ запрещён']]);
    exit();
}

if (!isset($_FILES['upload'])) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'Файл не передан']]);
    exit();
}

$file = $_FILES['upload'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['error' => ['message' => 'Недопустимый тип файла']]);
    exit();
}
if ($file['size'] > $maxSize) {
    echo json_encode(['error' => ['message' => 'Файл слишком большой (максимум 5MB)']]);
    exit();
}

// Папка: /media/editor/YYYY-MM/
$subDir = date('Y-m');
$uploadDir = '/home/s/sava7424/maribulka.rf/media/editor/' . $subDir . '/';
$localUploadDir = __DIR__ . '/../media/editor/' . $subDir . '/'; // для локальной разработки

// Используем правильный путь в зависимости от окружения
$targetDir = is_dir('/home/s/sava7424') ? $uploadDir : $localUploadDir;

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('img_', true) . '.' . strtolower($ext);
$targetPath = $targetDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['error' => ['message' => 'Ошибка сохранения файла']]);
    exit();
}

$url = '/media/editor/' . $subDir . '/' . $filename;
echo json_encode(['url' => $url]);
```

---

### Task 4: Pinia store — обновить home.ts

**Files:**
- Modify: `maribulka-vue/src/stores/home.ts`

**Step 1: Добавить методы для работы с блоками**

Добавить в конец секции `// существующие данные` новые ref и функции:

```typescript
// Блоки домашней страницы (4 независимых)
const blocks = ref<Record<number, string>>({})

async function fetchBlock(id: number): Promise<void> {
  try {
    const response = await fetch(`${API_URL}/home_blocks.php?id=${id}`)
    const data = await response.json()
    if (data.success) {
      blocks.value[id] = data.content
    }
  } catch (err) {
    console.error(`Ошибка загрузки блока ${id}:`, err)
  }
}

async function saveBlock(id: number, content: string): Promise<{ success: boolean; message?: string }> {
  try {
    const response = await fetch(`${API_URL}/home_blocks.php`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, content })
    })
    const data = await response.json()
    if (data.success) {
      blocks.value[id] = content
      return { success: true }
    }
    return { success: false, message: data.message }
  } catch (err) {
    return { success: false, message: 'Ошибка сети' }
  }
}
```

Добавить `blocks`, `fetchBlock`, `saveBlock` в `return { ... }`.

---

### Task 5: Установить CKEditor 5

**Step 1: Установить пакеты**

```bash
cd maribulka-vue
npm install ckeditor5 @ckeditor/ckeditor5-vue
```

**Step 2: Проверить что установилось**

```bash
npm list ckeditor5 @ckeditor/ckeditor5-vue
```
Ожидается: версии без ошибок.

**Step 3: Добавить CSS CKEditor в main.ts**

```typescript
// maribulka-vue/src/main.ts
import 'ckeditor5/ckeditor5.css'
```

---

### Task 6: Новый RichTextEditor.vue на CKEditor 5

**Files:**
- Modify: `maribulka-vue/src/components/RichTextEditor.vue` (полная замена)

**Step 1: Заменить содержимое файла**

```vue
<script setup lang="ts">
// @ts-nocheck
import { ref, watch } from 'vue'
import { CKEditor } from '@ckeditor/ckeditor5-vue'
import {
  ClassicEditor,
  Bold, Italic, Underline, Strikethrough,
  Heading,
  BulletedList, NumberedList,
  Alignment,
  Link,
  Image, ImageUpload, ImageResize, ImageStyle, ImageToolbar,
  SimpleUploadAdapter,
  Undo
} from 'ckeditor5'

interface Props {
  modelValue: string
  placeholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Начните вводить текст...'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const editorData = ref(props.modelValue)

watch(() => props.modelValue, (val) => {
  if (val !== editorData.value) editorData.value = val
})

function onInput(event: any, editor: any) {
  emit('update:modelValue', editor.getData())
}

const editorConfig = {
  plugins: [
    Bold, Italic, Underline, Strikethrough,
    Heading,
    BulletedList, NumberedList,
    Alignment,
    Link,
    Image, ImageUpload, ImageResize, ImageStyle, ImageToolbar,
    SimpleUploadAdapter,
    Undo
  ],
  toolbar: {
    items: [
      'heading', '|',
      'bold', 'italic', 'underline', 'strikethrough', '|',
      'bulletedList', 'numberedList', '|',
      'alignment', '|',
      'link', 'uploadImage', '|',
      'undo', 'redo'
    ]
  },
  heading: {
    options: [
      { model: 'paragraph', title: 'Обычный', class: 'ck-heading_paragraph' },
      { model: 'heading1', view: 'h1', title: 'Заголовок 1', class: 'ck-heading_heading1' },
      { model: 'heading2', view: 'h2', title: 'Заголовок 2', class: 'ck-heading_heading2' },
      { model: 'heading3', view: 'h3', title: 'Заголовок 3', class: 'ck-heading_heading3' }
    ]
  },
  image: {
    toolbar: [
      'imageStyle:inline',
      'imageStyle:alignLeft',
      'imageStyle:alignRight',
      '|',
      'imageStyle:block',
      '|',
      'resizeImage'
    ],
    styles: {
      options: ['inline', 'alignLeft', 'alignRight', 'block']
    },
    resizeOptions: [
      { name: 'resizeImage:original', value: null, label: 'Оригинал' },
      { name: 'resizeImage:25', value: '25', label: '25%' },
      { name: 'resizeImage:50', value: '50', label: '50%' },
      { name: 'resizeImage:75', value: '75', label: '75%' }
    ]
  },
  simpleUpload: {
    uploadUrl: '/api/upload-image.php',
    withCredentials: true
  },
  link: {
    defaultProtocol: 'https://',
    addTargetToExternalLinks: true,
    decorators: {
      openInNewTab: {
        mode: 'manual',
        label: 'Открыть в новой вкладке',
        attributes: { target: '_blank', rel: 'noopener noreferrer' }
      }
    }
  },
  placeholder: props.placeholder,
  language: 'ru'
}
</script>

<template>
  <div class="ck-editor-wrapper">
    <CKEditor
      :editor="ClassicEditor"
      v-model="editorData"
      :config="editorConfig"
      @input="onInput"
    />
  </div>
</template>
```

---

### Task 7: CSS — стили редактора и float-изображений

**Files:**
- Create: `maribulka-vue/src/assets/editor.css`
- Modify: `maribulka-vue/src/main.ts` (добавить импорт)

**Step 1: Создать editor.css**

```css
/* ========================================
   РЕДАКТОР CKEditor 5 — glass стиль + float изображений
   editor.css
   ======================================== */

/* Обёртка редактора */
.ck-editor-wrapper {
  width: 100%;
}

/* Тулбар в glass стиле */
.ck.ck-toolbar {
  background: var(--glass-bg) !important;
  border: 1px solid var(--glass-border) !important;
  border-radius: var(--padRadius) var(--padRadius) 0 0 !important;
}

.ck.ck-toolbar .ck-toolbar__items .ck-button {
  color: var(--text-primary) !important;
  border-radius: 6px !important;
}

.ck.ck-toolbar .ck-button:hover,
.ck.ck-toolbar .ck-button.ck-on {
  background: var(--glass-border) !important;
}

/* Область редактирования */
.ck.ck-editor__editable {
  background: var(--glass-bg) !important;
  border: 1px solid var(--glass-border) !important;
  border-top: none !important;
  border-radius: 0 0 var(--padRadius) var(--padRadius) !important;
  color: var(--text-primary) !important;
  min-height: 300px;
  padding: 16px !important;
}

.ck.ck-editor__editable:focus {
  box-shadow: none !important;
  border-color: var(--accent, #00b4d8) !important;
}

/* Float изображения — для отображения контента на сайте */
.home-panel-content .image-style-align-left,
.description-content .image-style-align-left {
  float: left;
  margin: 0 16px 8px 0;
  clear: left;
}

.home-panel-content .image-style-align-right,
.description-content .image-style-align-right {
  float: right;
  margin: 0 0 8px 16px;
  clear: right;
}

.home-panel-content .image-style-block,
.description-content .image-style-block {
  display: block;
  margin: 8px auto;
  clear: both;
}

/* Clearfix после блоков с float */
.home-panel-content::after,
.description-content::after {
  content: '';
  display: table;
  clear: both;
}
```

**Step 2: Добавить импорт в main.ts**

```typescript
import './assets/editor.css'
```

---

### Task 8: Pinia store — удалить description, оставить только blocks

**Files:**
- Modify: `maribulka-vue/src/stores/home.ts`

**Step 1: Удалить устаревшие методы fetchDescription и updateDescription**

Удалить: `description`, `fetchDescription()`, `updateDescription()` и их экспорт из `return {}`.

Они заменены на `blocks`, `fetchBlock()`, `saveBlock()` из Task 4.

---

### Task 9: Модалка EditBlockModal.vue

**Files:**
- Create: `maribulka-vue/src/components/home/EditBlockModal.vue`
- Delete: `maribulka-vue/src/components/home/EditStudioDescriptionModal.vue` (устарела)

**Step 1: Создать новую модалку**

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '@mdi/js'
import RichTextEditor from '../RichTextEditor.vue'
import { useHomeStore } from '../../stores/home'

interface Props {
  isVisible: boolean
  blockId: number
}

const props = defineProps<Props>()
const emit = defineEmits<{ close: [] }>()

const homeStore = useHomeStore()
const content = ref('')

watch(() => props.isVisible, (visible) => {
  if (visible) {
    content.value = homeStore.blocks[props.blockId] ?? ''
  }
})

async function handleSave() {
  const result = await homeStore.saveBlock(props.blockId, content.value)
  if (result.success) {
    emit('close')
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
      <div class="padGlass modal-sm" style="width: 90vw; max-width: 900px; min-width: auto;">
        <div class="modal-glassTitle">Редактирование блока {{ blockId }}</div>
        <RichTextEditor v-model="content" placeholder="Введите содержимое блока..." />
        <div class="ButtonFooter PosRight">
          <button class="btnGlass iconText" @click="emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" class="btn-icon" />
            <span>Отмена</span>
          </button>
          <button class="btnGlass iconText" @click="handleSave">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" class="btn-icon" />
            <span>Сохранить</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
```

---

### Task 10: Обновить Home.vue — 4 блока с кнопками редактирования

**Files:**
- Modify: `maribulka-vue/src/components/home/Home.vue`

**Step 1: Обновить script setup**

```typescript
// @ts-nocheck
import { ref, onMounted, toRef } from 'vue'
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

// Редактирование блоков
const editingBlock = ref<number | null>(null)

onMounted(() => {
  homeStore.fetchBlock(1)
  homeStore.fetchBlock(2)
  homeStore.fetchBlock(3)
  homeStore.fetchBlock(4)
})
```

**Step 2: Обновить template**

```vue
<template>
  <div ref="scrollAreaRef" class="home-scroll-area">
    <div class="home-stack">

      <!-- Панель 1 -->
      <div ref="panel1" class="home-panel">
        <div class="home-panel-glass"></div>
        <div class="home-panel-content">
          <button
            v-if="authStore.isAdmin"
            class="glass-btn icon-only home-panel-edit-btn"
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
            class="glass-btn icon-only home-panel-edit-btn"
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
            class="glass-btn icon-only home-panel-edit-btn"
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
            class="glass-btn icon-only home-panel-edit-btn"
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
```

**Step 3: Добавить стили кнопки редактирования в home.css**

В `maribulka-vue/src/assets/home.css` добавить:

```css
/* Кнопка редактирования блока — правый верхний угол панели */
.home-panel-edit-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 2;
}

/* Контентная область блока */
.home-block-content {
  width: 100%;
  height: 100%;
  padding: 20px;
  overflow-y: auto;
  box-sizing: border-box;
}
```

---

### Task 11: Удалить TipTap зависимости

**Step 1: Удалить пакеты TipTap**

```bash
cd maribulka-vue
npm uninstall @tiptap/vue-3 @tiptap/starter-kit @tiptap/extension-underline @tiptap/extension-text-align @tiptap/extension-text-style @tiptap/extension-color @tiptap/extension-image @tiptap/extension-link
```

**Step 2: Убедиться что build проходит**

```bash
npm run build
```
Ожидается: успешная сборка без ошибок.

---

### Task 12: Проверка в браузере

**Step 1: Запустить dev-сервер**

```bash
cd maribulka-vue
npm run dev
```

**Step 2: Проверить сценарии**

1. Открыть `http://localhost:5173` — домашняя страница загружается, 4 панели видны
2. Войти как admin (login: `admin`, password: `123`)
3. В каждой панели появилась кнопка редактирования (правый верхний угол)
4. Нажать кнопку — открывается модалка с CKEditor 5
5. Ввести текст, добавить картинку через тулбар — картинка загружается на сервер (не base64!)
6. Выбрать картинку, в тулбаре появляются кнопки: inline / align-left / align-right / block
7. Выбрать align-left — текст обтекает картинку справа
8. Нажать Сохранить — модалка закрывается, контент отображается в панели
9. Обновить страницу — контент сохранился
