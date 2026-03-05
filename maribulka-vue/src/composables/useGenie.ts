import { ref, type Ref, watch } from 'vue'

export function useGenie(
  panelRef: Ref<HTMLElement | null>,
  isVisible: () => boolean,
  emitClose: () => void
) {
  const closing = ref(false)

  function close(onDone?: () => void) {
    if (closing.value) return

    closing.value = true
    const el = panelRef.value

    const done = () => {
      closing.value = false
      if (onDone) onDone()
      else emitClose()
    }

    if (!el) {
      done()
      return
    }

    let fired = false
    const safeDone = () => {
      if (fired) return
      fired = true
      clearTimeout(timer)
      done()
    }

    el.addEventListener('animationend', safeDone, { once: true })

    // Страховка: если animationend не сработает за 600мс
    const timer = setTimeout(safeDone, 600)
  }

  watch(isVisible, (val) => {
    if (val) closing.value = false
  })

  return { closing, close }
}
