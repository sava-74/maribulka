<script setup lang="ts">
import { ref, computed } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {
  mdiWeatherSunny,
  mdiWeatherNight,
  mdiPlay,
  mdiCheck,
  mdiPlus,
  mdiMagnify,
  mdiShareVariant,
  mdiClose,
  mdiAccountOutline,
  mdiMenu,
  mdiImageMultiple,
} from '@mdi/js'

const theme = ref<'dark' | 'light'>('dark')
const themeIcon = computed(() => theme.value === 'dark' ? mdiWeatherNight : mdiWeatherSunny)

function toggleTheme() {
  theme.value = theme.value === 'dark' ? 'light' : 'dark'
}

function onRipple(event: MouseEvent) {
  const button = event.currentTarget as HTMLElement
  const rect = button.getBoundingClientRect()
  const size = Math.max(rect.width, rect.height)
  const x = event.clientX - rect.left - size / 2
  const y = event.clientY - rect.top - size / 2
  const ripple = document.createElement('span')
  ripple.className = 'sb-ripple'
  ripple.style.width = `${size}px`
  ripple.style.height = `${size}px`
  ripple.style.left = `${x}px`
  ripple.style.top = `${y}px`
  button.appendChild(ripple)
  ripple.addEventListener('animationend', () => ripple.remove())
}
</script>

<template>
  <div class="sandbox-root" :data-sandbox="theme">
    <!-- верхняя панель управления -->
    <div class="top-pad-bar">   
      <div class="top-pad-icon">            
            <div class="sectionBigIcon">
              <button class="glass-btn bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiAccountOutline" class="sb-bigIcon" />
              </button>
            </div>
            <div class="sectionBigIcon">
              <button class="glass-btn bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiMenu" class="sb-bigIcon" />
              </button>
            </div>
            <div class="sectionBigIcon">
              <button class="glass-btn bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiImageMultiple" class="sb-bigIcon" />
              </button>
            </div>
            <!-- Переключатель темы class="sb-theme-toggle" -->
            <div class="sectionThemeIcon">
              <button class="sb-theme-btn" @click="toggleTheme" aria-label="Переключить тему">
                <span class="sb-theme-indicator">
                <svg-icon type="mdi" :path="themeIcon" />
                </span>
              </button>
            </div>
          </div>
    </div>

    <!-- Анимированный фон -->
    <div class="sb-bg-layer">
      <div class="sb-bg-orb"></div>
      <div class="sb-bg-orb"></div>
      <div class="sb-bg-orb"></div>
    </div>
    <div class="sb-noise-overlay"></div>

    

    <!-- Контент -->
    <div class="sb-content">
      <!-- Hero -->
      <div class="sectionPad">
        <span class="section-title">Название рабочей панели например "Запись на стрижку"</span>
          <div class="sectionPadIcon">            
            <div class="sectionBigIcon">
              <button class="glass-btn accent bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiPlay" class="sb-bigIcon" />
              </button>
              <p class="textBigIcon">Начать</p>
            </div>
            <div class="sectionBigIcon">
              <button class="glass-btn bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiPlay" class="sb-bigIcon" />
              </button>
              <p class="textBigIcon">Выполнить</p>
            </div>
            <div class="sectionBigIcon">
              <button class="glass-btn accent bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiCheck" class="sb-bigIcon" />
              </button>
              <p class="textBigIcon">Запись на съёмку</p>
            </div>
            <div class="sectionBigIcon">
              <button class="glass-btn accent bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiCheck" class="sb-bigIcon" />
              </button>
              <p class="textBigIcon">Расходы</p>
            </div>
            <div class="sectionBigIcon">
              <button class="glass-btn accent bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiCheck" class="sb-bigIcon" />
              </button>
              <p class="textBigIcon">Приход</p>
            </div>
            <div class="sectionBigIcon">
              <button class="glass-btn bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiPlay" class="sb-bigIcon" />
              </button>
              <p class="textBigIcon">Касса</p>
            </div>
            <div class="sectionBigIcon">
              <button class="glass-btn bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiPlay" class="sb-bigIcon" />
              </button>
              <p class="textBigIcon">Отчеты</p>
            </div>
            
          </div>
          <span class="section-title">Название параграфа например "Справочники"</span>
          <div class="sectionPadIcon">
            <div class="sectionBigIcon">
              <button class="glass-btn bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiPlay" class="sb-bigIcon" />
              </button>
              <p class="textBigIcon">Клиенты</p>
            </div>
            <div class="sectionBigIcon">
              <button class="glass-btn bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiPlay" class="sb-bigIcon" />
              </button>
              <p class="textBigIcon">Акции</p>
            </div>
            <div class="sectionBigIcon">
              <button class="glass-btn bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiPlay" class="sb-bigIcon" />
              </button>
              <p class="textBigIcon">Таблица</p>
            </div>
            <div class="sectionBigIcon">
              <button class="glass-btn bigIcon" @click="onRipple($event)">
                <span class="inner-glow"></span>
                <span class="top-shine"></span>
                <svg-icon type="mdi" :path="mdiPlay" class="sb-bigIcon" />
              </button>
              <p class="textBigIcon">Категории расходов</p>
            </div>
          </div>
      </div>

      <!-- Standard -->
      <div class="sb-section">
        <span class="sb-section-title">Standard</span>
        <div class="sb-buttons-row">
          <button class="glass-btn accent" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <span>Подтвердить</span>
          </button>
          <button class="glass-btn" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <span>Отмена</span>
          </button>
        </div>
      </div>

      <!-- Tinted -->
      <div class="sb-section">
        <span class="sb-section-title">Tinted Variants</span>
        <div class="sb-buttons-row">
          <button class="glass-btn tint-green" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <span>Готово</span>
          </button>
          <button class="glass-btn tint-orange" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <span>Важно</span>
          </button>
          <button class="glass-btn tint-pink" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <span>Удалить</span>
          </button>
        </div>
      </div>

      <!-- icon + text -->
      <div class="sb-section">
        <span class="sb-section-title">ICON + TEXT</span>
        <div class="sb-buttons-row">
          <button class="glass-btn iconText tint-green" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheck" class="sb-icon" />
            <span>Готово</span>
          </button>
          <button class="glass-btn iconText tint-orange" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheck" class="sb-icon" />
            <span>Важно</span>
          </button>
          <button class="glass-btn iconText tint-pink" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiCheck" class="sb-icon" />
            <span>Удалить</span>
          </button>
        </div>
      </div>

      <!-- Text Only -->
      <div class="sb-section">
        <span class="sb-section-title">Text Only</span>
        <div class="sb-buttons-row">
          <button class="glass-btn accent" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <span>Принять все</span>
          </button>
          <button class="glass-btn" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <span>Стекло дефолт</span>
          </button>
          <button class="glass-btn accent" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <span>Сквозь стекло</span>
          </button>
        </div>
      </div>

      <!-- Icon Only -->
      <div class="sb-section">
        <span class="sb-section-title">Icon Only</span>
        <div class="sb-buttons-row">
          <button class="glass-btn icon-only" @click="onRipple($event)" aria-label="Поделиться">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiShareVariant" class="sb-icon" />
          </button>
          <button class="glass-btn icon-only accent" @click="onRipple($event)" aria-label="Добавить">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiPlus" class="sb-icon" />
          </button>
          <button class="glass-btn icon-only" @click="onRipple($event)" aria-label="Поиск">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiMagnify" class="sb-icon" />
          </button>
        </div>
      </div>

      <!-- Refraction -->
      <div class="sb-section">
        <span class="sb-section-title">Refraction Effect</span>
        <div class="sb-refraction-demo">
          <button class="glass-btn accent" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <span>Сквозь стекло</span>
          </button>
        </div>
      </div>

      <!-- rect -->
      <div class="sb-section">
        <span class="sb-section-title">Rect</span>
        <div class="sb-buttons-row">
          <button class="glass-btn rect  accent" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <span>OK</span>
          </button>
          <button class="glass-btn rect iconText" @click="onRipple($event)">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiClose" class="sb-icon" />
            <span>Закрыть</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>
