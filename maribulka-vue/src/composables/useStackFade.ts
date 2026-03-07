import { onMounted, onUnmounted, type Ref } from 'vue'

export function useStackFade(
  scrollContainer: Ref<HTMLElement | null>,
  panels: Ref<HTMLElement[]>
) {
  // Кэш высот панелей и позиций наезда — вычисляем один раз
  let panelHeights: number[] = []
  let overlapPositions: number[] = []

  function calcGeometry() {
    panelHeights = panels.value.map(p => p.offsetHeight)
    // overlapPositions[i] — scrollTop при котором панель i полностью перекрывает i-1
    overlapPositions = panelHeights.map((_, i) => {
      return panelHeights.slice(0, i).reduce((sum, h) => sum + h, 0)
    })
  }

  function updateOpacity() {
    const container = scrollContainer.value
    if (!container || !panels.value.length) return

    const scrollTop = container.scrollTop

    panels.value.forEach((panel, index) => {
      let opacity = 1
      const h = panelHeights[index] || panel.offsetHeight

      if (index === 0) {
        // Панель 1: исчезает по мере наезда панели 2 (100% диапазон)
        const fadeEnd = overlapPositions[1] ?? h
        opacity = scrollTop >= fadeEnd ? 0 : 1 - scrollTop / fadeEnd
      } else {
        const fadeInEnd = overlapPositions[index]      // полное перекрытие
        const fadeInStart = overlapPositions[index - 1] // начало наезда

        const nextOverlap = overlapPositions[index + 1]
        const isLast = index === panels.value.length - 1

        if (scrollTop <= fadeInStart) {
          opacity = 0
        } else if (scrollTop < fadeInEnd) {
          // Появление: от 0 до 1 на всём диапазоне наезда
          opacity = (scrollTop - fadeInStart) / (fadeInEnd - fadeInStart)
        } else if (!isLast && nextOverlap !== undefined) {
          // Исчезновение: от 1 до 0 на диапазоне следующего наезда
          opacity = scrollTop >= nextOverlap ? 0 : 1 - (scrollTop - fadeInEnd) / (nextOverlap - fadeInEnd)
        } else {
          opacity = 1
        }
      }

      panel.style.setProperty('--panel-opacity', String(Math.max(0, Math.min(1, opacity))))
    })
  }

  const resizeObserver = new ResizeObserver(() => {
    calcGeometry()
    updateOpacity()
  })

  onMounted(() => {
    const container = scrollContainer.value
    if (container) {
      calcGeometry()
      updateOpacity()
      container.addEventListener('scroll', updateOpacity, { passive: true })
      resizeObserver.observe(container)
    }
  })

  onUnmounted(() => {
    const container = scrollContainer.value
    if (container) {
      container.removeEventListener('scroll', updateOpacity)
    }
    resizeObserver.disconnect()
  })
}
