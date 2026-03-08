import { onMounted, onUnmounted, type Ref } from 'vue'

export function useStackFade(
  scrollContainer: Ref<HTMLElement | null>,
  panels: Ref<HTMLElement[]>
) {
  // Кэш высот панелей и позиций наезда — вычисляем один раз
  let panelHeights: number[] = []
  let overlapPositions: number[] = []
  let TOP_OFFSET = 110 // высота TopBar — обновляется в calcGeometry по реальному CSS

  function calcGeometry() {
    const container = scrollContainer.value
    const viewportHeight = container ? container.clientHeight : window.innerHeight

    // Читаем реальный TOP_OFFSET из padding-top стека (= высоте TopBar, учитывает мобилку)
    const stackEl = container?.querySelector('.home-stack') as HTMLElement | null
    if (stackEl) {
      const pt = parseInt(getComputedStyle(stackEl).paddingTop, 10)
      if (!isNaN(pt) && pt > 0) TOP_OFFSET = pt
    }

    panelHeights = panels.value.map(p => p.offsetHeight)
    // overlapPositions[i] — scrollTop при котором панель i полностью перекрывает i-1
    overlapPositions = panelHeights.map((_, i) => {
      return panelHeights.slice(0, i).reduce((sum, h) => sum + h, 0)
    })

    // Динамически обновляем top каждой панели и min-height стека
    const availableHeight = viewportHeight - TOP_OFFSET
    panels.value.forEach((panel, i) => {
      const h = panelHeights[i] ?? 0
      if (h > availableHeight) {
        // Панель выше viewport: top отрицательный → сначала виден верх, потом низ
        panel.style.top = `${viewportHeight - h}px`
      } else {
        panel.style.top = `${TOP_OFFSET}px`
      }
    })

    // Обновляем min-height стека чтобы хватало места для скролла последней панели
    if (stackEl) {
      const totalPanelHeight = panelHeights.reduce((sum, h) => sum + h, 0)
      stackEl.style.minHeight = `${TOP_OFFSET + totalPanelHeight + viewportHeight}px`
    }
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

  const SNAP_DURATION = 1000 // мс время авто скрола
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
    const viewportHeight = container.clientHeight
    const availableHeight = viewportHeight - TOP_OFFSET

    if (scrollingDown) {
      // Найти текущую активную панель — последняя с overlapPosition <= scrollTop
      let currentIndex = 0
      for (let i = overlapPositions.length - 1; i >= 0; i--) {
        if ((overlapPositions[i] ?? Infinity) <= scrollTop + 1) {
          currentIndex = i
          break
        }
      }

      const panelHeight = panelHeights[currentIndex] ?? 0

      if (panelHeight > availableHeight) {
        // Панель выше viewport — нужно сначала показать её низ
        // overlapPositions[currentIndex] = scrollTop при котором панель начинает sticky
        // Чтобы увидеть низ: scrollTop = overlapPositions[currentIndex] + (panelHeight - availableHeight)
        const targetToShowBottom = (overlapPositions[currentIndex] ?? 0) + (panelHeight - availableHeight)
        if (targetToShowBottom > scrollTop + 1) {
          smoothTarget = targetToShowBottom
          snapStartTime = 0
          if (smoothRafId) cancelAnimationFrame(smoothRafId)
          smoothRafId = requestAnimationFrame(smoothStep)
          return
        }
      }

      // Низ панели виден — snap к следующей
      const next = overlapPositions.find(pos => pos > scrollTop + 1)
      if (next !== undefined) {
        smoothTarget = next
        snapStartTime = 0
        if (smoothRafId) cancelAnimationFrame(smoothRafId)
        smoothRafId = requestAnimationFrame(smoothStep)
      }
    } else {
      // Скролл вверх: snap к предыдущей позиции
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

  // Touch-поддержка
  let touchStartY = 0

  function onTouchStart(e: TouchEvent) {
    if (e.touches[0]) touchStartY = e.touches[0].clientY
  }

  function onTouchEnd(e: TouchEvent) {
    if (!e.changedTouches[0]) return
    const dy = touchStartY - e.changedTouches[0].clientY
    if (Math.abs(dy) < 10) return // игнорируем случайные касания
    const scrollingDown = dy > 0
    clearTimeout(snapTimer)
    snapByDirection(scrollingDown)
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
      container.addEventListener('touchstart', onTouchStart, { passive: true })
      container.addEventListener('touchend', onTouchEnd, { passive: true })
      resizeObserver.observe(container)
      panels.value.forEach(p => resizeObserver.observe(p))
    }
  })

  onUnmounted(() => {
    const container = scrollContainer.value
    if (container) {
      container.removeEventListener('scroll', onScroll)
      container.removeEventListener('wheel', onWheel)
      container.removeEventListener('touchstart', onTouchStart)
      container.removeEventListener('touchend', onTouchEnd)
    }
    if (rafId) cancelAnimationFrame(rafId)
    if (smoothRafId) cancelAnimationFrame(smoothRafId)
    clearTimeout(snapTimer)
    resizeObserver.disconnect()
  })
}
