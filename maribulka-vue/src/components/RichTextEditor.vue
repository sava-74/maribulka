<script setup lang="ts">
// @ts-nocheck
import { ref, watch } from 'vue'
import { Ckeditor } from '@ckeditor/ckeditor5-vue'
import {
  ClassicEditor,
  Bold, Italic, Underline, Strikethrough,
  Heading,
  List, ListUI,
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
    List, ListUI,
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
    <Ckeditor
      :editor="ClassicEditor"
      v-model="editorData"
      :config="editorConfig"
      @input="onInput"
    />
  </div>
</template>
