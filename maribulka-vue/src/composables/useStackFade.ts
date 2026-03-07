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
        const fadeInEnd = overlapPositions[index] ?? 0
        const fadeInStart = overlapPositions[index - 1] ?? 0
        const nextOverlap = overlapPositions[index + 1]
        const isLast = index === panels.value.length - 1

        if (scrollTop <= fadeInStart) {
          opacity = 0
        } else if (scrollTop < fadeInEnd) {
          const range = fadeInEnd - fadeInStart
          opacity = range > 0 ? (scrollTop - fadeInStart) / range : 1
        } else if (!isLast && nextOverlap !== undefined) {
          const range = nextOverlap - fadeInEnd
          opacity = scrollTop >= nextOverlap ? 0 : 1 - (scrollTop - fadeInEnd) / range
        } else {
          opacity = 1
        }
      }

      panel.style.setProperty('--panel-opacity', String(Math.max(0, Math.min(1, opacity))))
    })
  }

  let rafId = 0

  function onScroll() {
    if (rafId) return
    rafId = requestAnimationFrame(() => {
      updateOpacity()
      rafId = 0
    })
  }

  // Плавный скролл колёсиком
  let smoothTarget = 0
  let smoothRafId = 0

  const SNAP_DURATION = 2500 // мс
  let snapStartTime = 0
  let snapStartPos = 0
  let snapEndPos = 0

  function easeOutQuad(t: number): number {
    return t * (2 - t)
  }

  function smoothStep(timestamp: number) {
    const container = scrollContainer.value
    if (!container) return

    if (!snapStartTime) {
      snapStartTime = timestamp
      snapStartPos = container.scrollTop
      snapEndPos = smoothTarget
    }

    // Если цель изменилась во время анимации — перезапустить от текущей позиции
    if (snapEndPos !== smoothTarget) {
      snapStartTime = timestamp
      snapStartPos = container.scrollTop
      snapEndPos = smoothTarget
    }

    const elapsed = timestamp - snapStartTime
    const t = Math.min(elapsed / SNAP_DURATION, 1)
    const eased = easeOutQuad(t)

    container.scrollTop = snapStartPos + (snapEndPos - snapStartPos) * eased

    if (t < 1) {
      smoothRafId = requestAnimationFrame(smoothStep)
    } else {
      container.scrollTop = snapEndPos
      snapStartTime = 0
      smoothRafId = 0
    }
  }

  let snapTimer = 0

  function snapByDirection(scrollingDown: boolean) {
    const container = scrollContainer.value
    if (!container || overlapPositions.length < 2) return

    const scrollTop = container.scrollTop

    if (scrollingDown) {
      const next = overlapPositions.find(pos => pos > scrollTop + 1)
      if (next !== undefined) {
        smoothTarget = next
        snapStartTime = 0
        if (smoothRafId) cancelAnimationFrame(smoothRafId)
        smoothRafId = requestAnimationFrame(smoothStep)
      }
    } else {
      const prev = [...overlapPositions].reverse().find(pos => pos < scrollTop - 1)
      if (prev !== undefined) {
        smoothTarget = prev
        snapStartTime = 0
        if (smoothRafId) cancelAnimationFrame(smoothRafId)
        smoothRafId = requestAnimationFrame(smoothStep)
      }
    }
  }

  function onWheel(e: WheelEvent) {
    e.preventDefault()
    const scrollingDown = e.deltaY > 0
    clearTimeout(snapTimer)
    snapTimer = window.setTimeout(() => snapByDirection(scrollingDown), 180)
  }

  const resizeObserver = new ResizeObserver(() => {
    calcGeometry()
    updateOpacity()
  })

  onMounted(() => {
    const container = scrollContainer.value
    if (container) {
      smoothTarget = container.scrollTop
      calcGeometry()
      updateOpacity()
      container.addEventListener('scroll', onScroll, { passive: true })
      container.addEventListener('wheel', onWheel, { passive: false })
      resizeObserver.observe(container)
    }
  })

  onUnmounted(() => {
    const container = scrollContainer.value
    if (container) {
      container.removeEventListener('scroll', onScroll)
      container.removeEventListener('wheel', onWheel)
    }
    if (rafId) cancelAnimationFrame(rafId)
    if (smoothRafId) cancelAnimationFrame(smoothRafId)
    clearTimeout(snapTimer)
    resizeObserver.disconnect()
  })
}
