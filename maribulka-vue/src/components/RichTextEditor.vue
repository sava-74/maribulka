<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import TextAlign from '@tiptap/extension-text-align'
import { TextStyle } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import TiptapImage from '@tiptap/extension-image'
import TiptapLink from '@tiptap/extension-link'
import SvgIcon from '@jamescoyle/vue-icon'
import {
  mdiFormatBold,
  mdiFormatItalic,
  mdiFormatUnderline,
  mdiFormatStrikethrough,
  mdiFormatHeader1,
  mdiFormatHeader2,
  mdiFormatHeader3,
  mdiFormatListBulleted,
  mdiFormatListNumbered,
  mdiFormatAlignLeft,
  mdiFormatAlignCenter,
  mdiFormatAlignRight,
  mdiFormatAlignJustify,
  mdiLink,
  mdiImagePlus,
  mdiFormatClear,
  mdiCheckCircleOutline,
  mdiCloseCircleOutline
} from '@mdi/js'

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

// Состояние модалки ввода URL
const showLinkModal = ref(false)
const linkUrl = ref('')

// Инициализация редактора
const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({
      heading: {
        levels: [1, 2, 3]
      }
    }),
    Underline,
    TextAlign.configure({
      types: ['heading', 'paragraph']
    }),
    TextStyle,
    Color,
    TiptapImage.configure({
      inline: true,
      allowBase64: true,
      resize: {
        enabled: true,
        minWidth: 50,
        minHeight: 50,
        alwaysPreserveAspectRatio: true
      }
    }),
    TiptapLink.configure({
      openOnClick: false,
      HTMLAttributes: {
        target: '_blank',
        rel: 'noopener noreferrer'
      }
    })
  ],
  editorProps: {
    attributes: {
      class: 'tiptap-editor'
    }
  },
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  }
})

// Синхронизация входного значения
watch(() => props.modelValue, (value) => {
  if (editor.value && editor.value.getHTML() !== value) {
    editor.value.commands.setContent(value, { emitUpdate: false })
  }
})

// Обработчик загрузки изображений
function handleImageUpload() {
  if (!editor.value) return

  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'

  input.onchange = () => {
    const file = input.files?.[0]
    if (!file) return

    const reader = new FileReader()

    reader.onload = (e) => {
      const base64 = e.target?.result as string
      if (editor.value) {
        editor.value.chain().focus().setImage({ src: base64 }).run()
      }
    }

    reader.readAsDataURL(file)
  }

  input.click()
}

// Обработчик вставки ссылки
function handleAddLink() {
  if (!editor.value) return

  const previousUrl = editor.value.getAttributes('link').href
  linkUrl.value = previousUrl || ''
  showLinkModal.value = true
}

// Сохранить ссылку
function saveLinkUrl() {
  if (!editor.value) return

  // Пустая строка - удалить ссылку
  if (linkUrl.value.trim() === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
  } else {
    // Добавить/обновить ссылку
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: linkUrl.value }).run()
  }

  showLinkModal.value = false
  linkUrl.value = ''
}

// Отменить ввод ссылки
function cancelLinkUrl() {
  showLinkModal.value = false
  linkUrl.value = ''
}

// Очистка при размонтировании
onBeforeUnmount(() => {
  editor.value?.destroy()
})
</script>

<template>
  <div v-if="editor" class="tiptap-wrapper">
    <!-- Панель инструментов -->
    <div class="tiptap-toolbar">
      <!-- Заголовки -->
      <button
        @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
        :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
        class="buttonGL"
        type="button"
        title="Заголовок 1"
      >
        <svg-icon type="mdi" :path="mdiFormatHeader1" />
      </button>
      <button
        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
        :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
        class="buttonGL"
        type="button"
        title="Заголовок 2"
      >
        <svg-icon type="mdi" :path="mdiFormatHeader2" />
      </button>
      <button
        @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
        :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
        class="buttonGL"
        type="button"
        title="Заголовок 3"
      >
        <svg-icon type="mdi" :path="mdiFormatHeader3" />
      </button>

      <div class="toolbar-separator"></div>

      <!-- Форматирование текста -->
      <button
        @click="editor.chain().focus().toggleBold().run()"
        :class="{ 'is-active': editor.isActive('bold') }"
        class="buttonGL"
        type="button"
        title="Жирный"
      >
        <svg-icon type="mdi" :path="mdiFormatBold" />
      </button>
      <button
        @click="editor.chain().focus().toggleItalic().run()"
        :class="{ 'is-active': editor.isActive('italic') }"
        class="buttonGL"
        type="button"
        title="Курсив"
      >
        <svg-icon type="mdi" :path="mdiFormatItalic" />
      </button>
      <button
        @click="editor.chain().focus().toggleUnderline().run()"
        :class="{ 'is-active': editor.isActive('underline') }"
        class="buttonGL"
        type="button"
        title="Подчёркнутый"
      >
        <svg-icon type="mdi" :path="mdiFormatUnderline" />
      </button>
      <button
        @click="editor.chain().focus().toggleStrike().run()"
        :class="{ 'is-active': editor.isActive('strike') }"
        class="buttonGL"
        type="button"
        title="Зачёркнутый"
      >
        <svg-icon type="mdi" :path="mdiFormatStrikethrough" />
      </button>

      <div class="toolbar-separator"></div>

      <!-- Списки -->
      <button
        @click="editor.chain().focus().toggleBulletList().run()"
        :class="{ 'is-active': editor.isActive('bulletList') }"
        class="buttonGL"
        type="button"
        title="Маркированный список"
      >
        <svg-icon type="mdi" :path="mdiFormatListBulleted" />
      </button>
      <button
        @click="editor.chain().focus().toggleOrderedList().run()"
        :class="{ 'is-active': editor.isActive('orderedList') }"
        class="buttonGL"
        type="button"
        title="Нумерованный список"
      >
        <svg-icon type="mdi" :path="mdiFormatListNumbered" />
      </button>

      <div class="toolbar-separator"></div>

      <!-- Выравнивание -->
      <button
        @click="editor.chain().focus().setTextAlign('left').run()"
        :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }"
        class="buttonGL"
        type="button"
        title="По левому краю"
      >
        <svg-icon type="mdi" :path="mdiFormatAlignLeft" />
      </button>
      <button
        @click="editor.chain().focus().setTextAlign('center').run()"
        :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }"
        class="buttonGL"
        type="button"
        title="По центру"
      >
        <svg-icon type="mdi" :path="mdiFormatAlignCenter" />
      </button>
      <button
        @click="editor.chain().focus().setTextAlign('right').run()"
        :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }"
        class="buttonGL"
        type="button"
        title="По правому краю"
      >
        <svg-icon type="mdi" :path="mdiFormatAlignRight" />
      </button>
      <button
        @click="editor.chain().focus().setTextAlign('justify').run()"
        :class="{ 'is-active': editor.isActive({ textAlign: 'justify' }) }"
        class="buttonGL"
        type="button"
        title="По ширине"
      >
        <svg-icon type="mdi" :path="mdiFormatAlignJustify" />
      </button>

      <div class="toolbar-separator"></div>

      <!-- Медиа -->
      <button
        @click="handleAddLink"
        :class="{ 'is-active': editor.isActive('link') }"
        class="buttonGL"
        type="button"
        title="Вставить ссылку"
      >
        <svg-icon type="mdi" :path="mdiLink" />
      </button>
      <button
        @click="handleImageUpload"
        class="buttonGL"
        type="button"
        title="Вставить изображение"
      >
        <svg-icon type="mdi" :path="mdiImagePlus" />
      </button>

      <div class="toolbar-separator"></div>

      <!-- Очистить форматирование -->
      <button
        @click="editor.chain().focus().unsetAllMarks().clearNodes().run()"
        class="buttonGL"
        type="button"
        title="Очистить форматирование"
      >
        <svg-icon type="mdi" :path="mdiFormatClear" />
      </button>
    </div>

    <!-- Область редактирования -->
    <EditorContent :editor="editor" class="tiptap-content" />

    <!-- Модалка ввода URL -->
    <div v-if="showLinkModal" class="modal-overlay" @click.self="cancelLinkUrl">
      <div class="modal-glass">
        <div class="modal-header">
          <div class="modal-glassTitle">Вставить ссылку</div>
        </div>

        <div class="modal-body">
          <input
            v-model="linkUrl"
            type="text"
            placeholder="Введите URL..."
            class="glass-input"
            @keyup.enter="saveLinkUrl"
            @keyup.esc="cancelLinkUrl"
            autofocus
          />
        </div>

        <div class="modal-footerUrl">
          <button class="buttonGL-text" @click="cancelLinkUrl">
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" />
            <span>Отмена</span>
          </button>
          <button class="buttonGL-text" @click="saveLinkUrl">
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" />
            <span>Сохранить</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
