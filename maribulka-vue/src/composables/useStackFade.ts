import { onMounted, onUnmounted, type Ref } from 'vue'

export function useStackFade(
  scrollContainer: Ref<HTMLElement | null>,
  panels: Ref<HTMLElement[]>
) {
  // Кэш высот панелей и позиций наезда — вычисляем один раз
  let panelHeights: number[] = []
  let overlapPositions: number[] = []
  let TOP_OFFSET = 110 // высота TopBar — обновляется в calcGeometry по реальному CSS
  let BOTTOM_GAP = 20 // отступ снизу — обновляется в calcGeometry (20px десктоп, 10px мобилка)

  function calcGeometry() {
    const container = scrollContainer.value
    const viewportHeight = container ? container.clientHeight : window.innerHeight

    // Читаем реальный TOP_OFFSET из padding-top стека (= высоте TopBar, учитывает мобилку)
    const stackEl = container?.querySelector('.home-stack') as HTMLElement | null
    if (stackEl) {
      const pt = parseInt(getComputedStyle(stackEl).paddingTop, 10)
      if (!isNaN(pt) && pt > 0) TOP_OFFSET = pt
    }

    // Обновляем BOTTOM_GAP по типу устройства
    BOTTOM_GAP = window.matchMedia('(pointer: coarse)').matches ? 10 : 20

    panelHeights = panels.value.map(p => p.offsetHeight)
    // overlapPositions[i] — scrollTop при котором панель i полностью перекрывает i-1
    overlapPositions = panelHeights.map((_, i) => {
      return panelHeights.slice(0, i).reduce((sum, h) => sum + h, 0)
    })

    // Динамически обновляем top каждой панели и min-height стека
    // Панель "высокая" если не помещается в viewport без учёта BOTTOM_GAP
    const fitsHeight = viewportHeight - TOP_OFFSET
    panels.value.forEach((panel, i) => {
      const h = panelHeights[i] ?? 0
      if (h > fitsHeight) {
        // Панель выше viewport: top отрицательный → сначала виден верх, потом низ
        panel.style.top = `${viewportHeight - h - BOTTOM_GAP}px`
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

  const FADE_RANGE = 150 // px — диапазон затухания при наезде следующей панели

  function updateOpacity() {
    const container = scrollContainer.value
    if (!container || !panels.value.length) return

    // Читаем rects всех панелей один раз
    const rects = panels.value.map(p => p.getBoundingClientRect())

    panels.value.forEach((panel, index) => {
      let opacity = 1
      const isLast = index === panels.value.length - 1

      // overlap(i, i+1) = насколько панель i+1 перекрыла панель i снизу
      // = нижний край панели i - верхний край панели i+1
      // когда overlap <= 0 — панели не перекрываются
      // когда overlap >= FADE_RANGE — панель i полностью затухла

      if (index === 0) {
        const nextRect = rects[1]
        if (!nextRect) {
          opacity = 1
        } else {
          const overlap = rects[0]!.bottom - nextRect.top
          opacity = overlap <= 0 ? 1 : overlap >= FADE_RANGE ? 0 : 1 - overlap / FADE_RANGE
        }
      } else {
        // Fade-in: эта панель появляется по мере перекрытия предыдущей снизу
        const prevRect = rects[index - 1]!
        const fadeInOverlap = prevRect.bottom - rects[index]!.top
        const fadeIn = fadeInOverlap <= 0 ? 0 : fadeInOverlap >= FADE_RANGE ? 1 : fadeInOverlap / FADE_RANGE

        // Fade-out: следующая панель наезжает на эту
        let fadeOut = 1
        if (!isLast) {
          const nextRect = rects[index + 1]!
          const overlap = rects[index]!.bottom - nextRect.top
          fadeOut = overlap <= 0 ? 1 : overlap >= FADE_RANGE ? 0 : 1 - overlap / FADE_RANGE
        }

        opacity = Math.min(fadeIn, fadeOut)
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
    const fitsHeight = viewportHeight - TOP_OFFSET

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

      if (panelHeight > fitsHeight) {
        // Панель выше viewport — нужно сначала показать её низ
        // overlapPositions[currentIndex] = scrollTop при котором панель начинает sticky
        // Чтобы увидеть низ: scrollTop = overlapPositions[currentIndex] + (panelHeight - availableHeight)
        const targetToShowBottom = (overlapPositions[currentIndex] ?? 0) + (panelHeight - fitsHeight)
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
