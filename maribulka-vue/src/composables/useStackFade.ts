import { onMounted, onUnmounted, type Ref } from 'vue'

export function useStackFade(
  scrollContainer: Ref<HTMLElement | null>,
  panels: Ref<HTMLElement[]>
) {
  // Кэш высот панелей и позиций наезда — вычисляем один раз
  let panelHeights: number[] = []
  let overlapPositions: number[] = []
  let fadePositions: number[] = [] // scrollTop момента реального визуального наезда панели i
  let fadeRanges: number[] = []    // диапазон затухания для каждой панели (в px scrollTop)
  let panelStickyTops: number[] = [] // отрицательный top для высоких панелей (кэш)
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

    // Кэшируем top для высоких панелей, изначально всем ставим TOP_OFFSET
    const fitsHeight = viewportHeight - TOP_OFFSET
    panelStickyTops = panelHeights.map(h => {
      if (h > fitsHeight) {
        return viewportHeight - h - BOTTOM_GAP // отрицательный top для высокой панели (активной)
      }
      return TOP_OFFSET
    })
    // Изначально все панели top: TOP_OFFSET — переключаем динамически при скролле
    panels.value.forEach(panel => {
      panel.style.top = `${TOP_OFFSET}px`
    })

    // overlapPositions[i] — scrollTop при котором панель i начинает наезжать на i-1
    overlapPositions = panelHeights.map((_, i) => {
      return panelHeights.slice(0, i).reduce((sum, h) => sum + h, 0)
    })

    // fadePositions[i] = scrollTop начала анимации наезда панели i
    fadePositions = panelHeights.map((_, i) => {
      if (i === 0) return 0
      const prevH = panelHeights[i - 1] ?? 0
      const prevStart = overlapPositions[i - 1] ?? 0
      if (prevH > fitsHeight) {
        return prevStart + (prevH - fitsHeight) + BOTTOM_GAP
      }
      return (overlapPositions[i] ?? 0) - Math.min(prevH, fitsHeight)
    })

    // fadeRanges[i] = overlapPositions[i] - fadePositions[i]
    // всегда точно совпадает, анимация заканчивается ровно на overlapPositions[i]
    fadeRanges = panelHeights.map((_, i) => {
      if (i === 0) return 0
      return Math.max((overlapPositions[i] ?? 0) - (fadePositions[i] ?? 0), 50)
    })

    // Обновляем min-height стека чтобы хватало места для скролла последней панели
    if (stackEl) {
      const totalPanelHeight = panelHeights.reduce((sum, h) => sum + h, 0)
      stackEl.style.minHeight = `${TOP_OFFSET + totalPanelHeight + viewportHeight}px`
    }
  }

  function updateOpacity() {
    const container = scrollContainer.value
    if (!container || !panels.value.length || !fadePositions.length) return

    const scrollTop = container.scrollTop

    panels.value.forEach((panel, index) => {
      const isLast = index === panels.value.length - 1
      // Переключаем top высокой панели: отрицательный только когда она активна
      const stickyTop = panelStickyTops[index] ?? TOP_OFFSET
      if (stickyTop < TOP_OFFSET) {
        // Панель высокая — активна когда scrollTop >= overlapPositions[index]
        const isActive = scrollTop >= (overlapPositions[index] ?? Infinity)
        panel.style.top = isActive ? `${stickyTop}px` : `${TOP_OFFSET}px`
      }
      let opacity = 1

      // Fade-out: когда следующая панель наезжает на эту
      if (!isLast) {
        const fadeOutStart = fadePositions[index + 1] ?? Infinity
        const range = fadeRanges[index + 1] ?? 50
        const progress = scrollTop - fadeOutStart
        if (progress >= range) {
          opacity = 0
        } else if (progress > 0) {
          opacity = 1 - progress / range
        }
      }

      // Fade-in: когда эта панель наезжает на предыдущую
      if (index > 0) {
        const fadeInStart = fadePositions[index] ?? 0
        const range = fadeRanges[index] ?? 50
        const progress = scrollTop - fadeInStart
        const fadeIn = progress <= 0 ? 0 : progress >= range ? 1 : progress / range
        opacity = Math.min(opacity, fadeIn)
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
  let bottomReached = false // флаг: низ высокой панели достигнут, следующий щелчок — снап

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
    const container = scrollContainer.value
    if (!container) return

    // Пока идёт snap-анимация — игнорируем колёсико
    if (smoothRafId) return

    const scrollingDown = e.deltaY > 0

    // Найти текущую активную панель
    const scrollTop = container.scrollTop
    const viewportHeight = container.clientHeight
    const fitsHeight = viewportHeight - TOP_OFFSET

    let currentIndex = 0
    for (let i = overlapPositions.length - 1; i >= 0; i--) {
      if ((overlapPositions[i] ?? Infinity) <= scrollTop + 1) {
        currentIndex = i
        break
      }
    }

    const panelHeight = panelHeights[currentIndex] ?? 0

    if (panelHeight > fitsHeight) {
      // Панель высокая — считаем границы ручного скролла
      const panelScrollStart = overlapPositions[currentIndex] ?? 0
      const panelScrollEnd = panelScrollStart + (panelHeight - fitsHeight) + BOTTOM_GAP

      if (scrollingDown) {
        if (!bottomReached) {
          // Ещё не достигли низа — скроллим вручную
          const newScrollTop = Math.min(scrollTop + Math.abs(e.deltaY), panelScrollEnd)
          container.scrollTop = newScrollTop
          // Если достигли границы — выставляем флаг (следующий щелчок = снап)
          if (newScrollTop >= panelScrollEnd - 1) bottomReached = true
          return
        }
        // bottomReached = true → падаем вниз к снапу
      }
      if (!scrollingDown) {
        // Скролл вверх — сбрасываем флаг, скроллим вручную
        bottomReached = false
        if (scrollTop > panelScrollStart + 1) {
          container.scrollTop = Math.max(scrollTop - Math.abs(e.deltaY), panelScrollStart)
          return
        }
      }
    } else {
      bottomReached = false
    }

    // Снап к следующей/предыдущей панели
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
