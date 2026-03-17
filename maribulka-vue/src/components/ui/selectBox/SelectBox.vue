<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiChevronDown } from '@mdi/js'

interface Option {
  value: string | number
  label: string
}

const props = defineProps<{
  modelValue: string | number | null
  options: Option[]
  placeholder?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | number | null]
}>()

const isOpen = ref(false)
const wrapRef = ref<HTMLElement | null>(null)
const dropdownStyle = ref({ top: '0px', left: '0px', width: '0px' })

const selectedLabel = computed(() => {
  const found = props.options.find(o => o.value == props.modelValue)
  return found ? found.label : (props.placeholder ?? 'Выберите...')
})

const hasValue = computed(() =>
  props.modelValue !== null && props.modelValue !== '' && props.modelValue !== undefined
)

async function toggle() {
  if (props.disabled) return
  if (!isOpen.value) {
    await nextTick()
    const rect = wrapRef.value?.getBoundingClientRect()
    if (rect) {
      dropdownStyle.value = {
        top: `${rect.bottom + 4}px`,
        left: `${rect.left}px`,
        width: `${rect.width}px`,
      }
    }
  }
  isOpen.value = !isOpen.value
}

function select(option: Option) {
  emit('update:modelValue', option.value)
  isOpen.value = false
}

function onDocumentClick(e: MouseEvent) {
  if (!wrapRef.value?.contains(e.target as Node)) {
    isOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', onDocumentClick, true))
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick, true))
</script>

<template>
  <div
    ref="wrapRef"
    class="combo-box-wrap"
    :class="{ 'combo-box-wrap--disabled': disabled, 'combo-box-wrap--open': isOpen }"
    tabindex="0"
    @click="toggle"
  >
    <span class="combo-box-value" :class="{ 'combo-box-placeholder': !hasValue }">
      {{ selectedLabel }}
    </span>
    <svg-icon type="mdi" :path="mdiChevronDown" class="combo-box-arrow" />
  </div>

  <Teleport to="body">
    <template v-if="isOpen">
      <div class="combo-box-dropdown" :style="dropdownStyle">
        <div
          v-for="option in options"
          :key="option.value"
          class="combo-box-option"
          :class="{ 'combo-box-option--selected': option.value == modelValue }"
          @click="select(option)"
        >
          {{ option.label }}
        </div>
      </div>
    </template>
  </Teleport>
</template>
