# 📝 План: Рефакторинг календаря и сайдбара

**Дата:** 2026-04-02  
**Автор:** project-architect  
**Статус:** Готов к реализации

---

## 🎯 Задача

Изменить внешний вид календаря в секции "Учёт":

1. **Исправить выход панелей за границы TopBar** — CalendarPanel и CalendarSidebar не должны выходить за пределы рабочей области
2. **Добавить перетаскиваемый разделитель** — иконка `mdiDotsVertical` между панелями
3. **Изменение размера перетаскиванием** — drag влево/вправо меняет ширину панелей
4. **Скрытие панели** — при ширине < 300px панель исчезает
5. **Возврат панели** — при обратном перетаскивании > 300px панель появляется
6. **Возврат точек** — если не дотянул до 300px, точки возвращаются обратно

---

## 📋 Анализ текущей реализации

### Структура компонентов:
```
App.vue (root)
├── TopBar.vue (110px высота, fixed)
└── .worck-table (рабочий стол, padding-top: 110px)
    └── [calendar page]
        ├── CalendarPanel.vue (календарь FullCalendar)
        └── CalendarSidebar.vue (сайдбар с записями дня)
```

**Текущая архитектура:**
- `CalendarPanel.vue` — обёртка FullCalendar, класс `.calendar-panel`
- `CalendarSidebar.vue` — отдельный компонент с классом `.calendar-sidebar`
- В `App.vue` компоненты рендерятся рядом в `.worck-table` без общего контейнера
- Сайдбар отображается только когда `!sidebarIsDayView`

### Текущие стили (calendar.css):

**CalendarPanel:**
```css
.calendar-panel {
  display: flex;
  flex-direction: column;
  height: calc(100% - 20px);
  gap: 0;
  overflow: hidden;
  max-width: 100%;
  align-items: stretch;
}
```

**CalendarSidebar:**
```css
.calendar-sidebar {
  width: 250px;           /* ← ФИКСИРОВАННАЯ ширина */
  height: calc(100% - 20px);
  padding: 30px 10px;
  flex-shrink: 0;         /* ← Не сжимается */
  border-left: 1px solid var(--glass-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
```

### Найденные проблемы:

1. **Панели выходят за границы TopBar:**
   - `.worck-table` имеет `padding-top: 110px` (отступ под TopBar)
   - `.calendar-panel` имеет `height: calc(100% - 20px)` — это 100% от высоты родителя, но не учитывает flex-контекст
   - Нет общего контейнера-обёртки для `CalendarPanel + CalendarSidebar`
   - Компоненты рендерятся напрямую в `.worck-table` без ограничений по ширине

2. **Нет перетаскиваемого разделителя:**
   - Сайдбар имеет фиксированную ширину `250px`
   - Нет компонента-ресайзера между панелями
   - Нет CSS-переменных для управления шириной

3. **Нет логики скрытия/возврата:**
   - Сайдбар скрывается только через `v-if="!sidebarIsDayView"` (режим дня)
   - Нет условия по ширине < 300px

---

## 🎯 План изменений

### Архитектурное решение

**Структура после изменений:**
```
App.vue
└── .worck-table
    └── CalendarContainer.vue  🆕
        ├── CalendarPanel.vue       # 🆕 Левая панель (календарь)
        ├── CalendarResizer.vue     # 🆕 Разделитель посередине
        └── CalendarSidebar.vue     # 🆕 Правая панель (сайдбар)
```

**Принцип работы:**
- Разделитель **посередине** между двумя панелями
- **Drag влево** → Calendar уменьшается, Sidebar увеличивается
  - **< 300px** → Calendar исчезает, разделитель прилипает к **левой** границе
- **Drag вправо** → Sidebar уменьшается, Calendar увеличивается
  - **< 300px** → Sidebar исчезает, разделитель прилипает к **правой** границе
- **Drag от границы** → панель появляется при ≥ 300px

---

### Этап 1: Исправление границ панелей

**Цель:** Создать обёрточный компонент `CalendarContainer.vue`

- [ ] **Создать компонент `CalendarContainer.vue`**
  - Файл: `maribulka-vue/src/components/calendar/CalendarContainer.vue`
  
  ```vue
  <template>
    <div class="calendar-container">
      <slot></slot>
    </div>
  </template>
  
  <script setup lang="ts">
  import { provide, ref } from 'vue'
  
  // Состояние ширины панелей
  const calendarWidth = ref(650)  // начальная ширина Calendar (слева)
  const sidebarWidth = ref(350)   // начальная ширина Sidebar (справа) - остаток
  const isCalendarHidden = ref(false)
  const isSidebarHidden = ref(false)
  
  // Предоставляем контекст для дочерних компонентов
  provide('calendar-context', {
    calendarWidth,
    sidebarWidth,
    isCalendarHidden,
    isSidebarHidden,
    updateCalendarWidth: (width: number) => {
      calendarWidth.value = width
    },
    updateSidebarWidth: (width: number) => {
      sidebarWidth.value = width
    }
  })
  </script>
  
  <style scoped>
  .calendar-container {
    display: flex;
    height: 100%;
    width: 100%;
    overflow: hidden;
  }
  </style>
  ```

- [ ] **Создать CSS файл `calendar-container.css`**
  - Файл: `maribulka-vue/src/components/calendar/calendar-container.css`
  
  ```css
  .calendar-container {
    display: flex;
    height: 100%;
    width: 100%;
    overflow: hidden;
    max-width: 100%;
  }
  ```

- [ ] **Обновить App.vue**
  - Обернуть `CalendarPanel` + `CalendarSidebar` в `CalendarContainer`, добавить разделитель посередине
  
  ```vue
  <template>
    <div v-if="navStore.currentPage === 'calendar'" class="calendar-page">
      <CalendarContainer>
        <CalendarPanel />
        <CalendarResizer />
        <CalendarSidebar />
      </CalendarContainer>
    </div>
  </template>
  ```

- [ ] **Импортировать стили в App.vue**
  ```typescript
  import './components/calendar/calendar-container.css'
  ```

### Этап 2: Добавление разделителя с drag'n'drop

**Цель:** Создать компонент `CalendarResizer.vue` для изменения ширины панелей

- [ ] **Создать компонент `CalendarResizer.vue`**
  - Файл: `maribulka-vue/src/components/calendar/CalendarResizer.vue`
  
  ```vue
  <template>
    <div 
      class="calendar-resizer"
      :class="{ 'calendar-resizer--dragging': isDragging }"
      @mousedown="startDrag"
    >
      <svg-icon 
        :path="mdiDotsVertical" 
        class="calendar-resizer__icon"
      />
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, inject, computed } from 'vue'
  import { mdiDotsVertical } from '@mdi/light-js'
  
  const context = inject('calendar-context')
  if (!context) throw new Error('CalendarResizer must be inside CalendarContainer')
  
  const { calendarWidth, sidebarWidth, isCalendarHidden, isSidebarHidden, updateCalendarWidth, updateSidebarWidth } = context
  
  const isDragging = ref(false)
  const containerRef = computed(() => {
    // Находим родительский контейнер
    return document.querySelector('.calendar-container') as HTMLElement | null
  })
  
  function startDrag(e: MouseEvent) {
    isDragging.value = true
    document.addEventListener('mousemove', onDrag)
    document.addEventListener('mouseup', stopDrag)
    document.body.style.cursor = 'ew-resize'
    document.body.style.userSelect = 'none'
  }
  
  function onDrag(e: MouseEvent) {
    if (!isDragging.value || !containerRef.value) return
    
    const containerRect = containerRef.value.getBoundingClientRect()
    const newCalendarWidth = e.clientX - containerRect.left  // ширина Calendar от левого края
    const newSidebarWidth = containerRect.right - e.clientX  // ширина Sidebar от правого края
    
    // Проверка скрытия Calendar (drag влево)
    if (isCalendarHidden.value) {
      // Разделитель следует за курсором от левой границы
      if (newCalendarWidth >= 300) {
        isCalendarHidden.value = false
        updateCalendarWidth(newCalendarWidth)
      }
      // Иначе разделитель следует за курсором у левой границы (ширина 0)
    } else if (newCalendarWidth < 300) {
      isCalendarHidden.value = true
      updateCalendarWidth(0)
      // Разделитель прилипает к левой границе
    } else {
      updateCalendarWidth(newCalendarWidth)
    }
    
    // Проверка скрытия Sidebar (drag вправо)
    if (isSidebarHidden.value) {
      // Разделитель следует за курсором от правой границы
      if (newSidebarWidth >= 300) {
        isSidebarHidden.value = false
        updateSidebarWidth(newSidebarWidth)
      }
      // Иначе разделитель следует за курсором у правой границы (ширина 0)
    } else if (newSidebarWidth < 300) {
      isSidebarHidden.value = true
      updateSidebarWidth(0)
      // Разделитель прилипает к правой границе
    } else {
      updateSidebarWidth(newSidebarWidth)
    }
  }
  
  function stopDrag() {
    isDragging.value = false
    document.removeEventListener('mousemove', onDrag)
    document.removeEventListener('mouseup', stopDrag)
    document.body.style.cursor = ''
    document.body.style.userSelect = ''
    
    // Если отпустили < 300px — возвращаем панель в скрытое состояние (разделитель в край)
    if (calendarWidth.value > 0 && calendarWidth.value < 300) {
      isCalendarHidden.value = true
      updateCalendarWidth(0)
    }
    if (sidebarWidth.value > 0 && sidebarWidth.value < 300) {
      isSidebarHidden.value = true
      updateSidebarWidth(0)
    }
    // Если ≥ 300px — панель остаётся с текущей шириной
  }
  </script>
  
  <style scoped>
  .calendar-resizer {
    width: 24px;
    flex-shrink: 0;
    cursor: ew-resize;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--glass-border);
    transition: background 0.2s;
    user-select: none;
  }
  
  .calendar-resizer:hover,
  .calendar-resizer--dragging {
    background: var(--accent-primary);
  }
  
  .calendar-resizer__icon {
    color: var(--text-secondary);
    opacity: 0.6;
    width: 20px;
    height: 20px;
  }
  </style>
  ```

- [ ] **Добавить CSS-переменные**
  - В `calendar-container.css` или глобальные переменные:
  
  ```css
  :root {
    --calendar-initial-width: 350px;
    --sidebar-initial-width: 350px;
    --calendar-min-width: 300px;
    --sidebar-min-width: 300px;
    --calendar-resizer-width: 24px;
  }
  ```

### Этап 3: Логика скрытия/возврата панели

**Цель:** Скрывать левую панель (Calendar) при ширине < 300px, и правую панель (Sidebar) при ширине < 300px

- [ ] **Обновить CalendarPanel.vue**
  - Добавить вычисляемый класс для скрытия, динамическую ширину
  
  ```vue
  <template>
    <div 
      class="calendar-panel"
      :class="{ 'calendar-panel--hidden': isCalendarHidden }"
      :style="panelStyle"
    >
      <!-- Контент (FullCalendar) -->
    </div>
  </template>
  
  <script setup lang="ts">
  import { inject, computed } from 'vue'
  
  const context = inject('calendar-context')
  if (!context) throw new Error('CalendarPanel must be inside CalendarContainer')
  
  const { calendarWidth, isCalendarHidden } = context
  
  const panelStyle = computed(() => ({
    width: isCalendarHidden.value ? '0' : `${calendarWidth.value}px`
  }))
  </script>
  ```

- [ ] **Обновить CalendarSidebar.vue**
  - Добавить вычисляемый класс для скрытия, динамическую ширину
  
  ```vue
  <template>
    <div 
      class="calendar-sidebar"
      :class="{ 'calendar-sidebar--hidden': isSidebarHidden }"
      :style="sidebarStyle"
    >
      <!-- Контент -->
    </div>
  </template>
  
  <script setup lang="ts">
  import { inject, computed } from 'vue'
  
  const context = inject('calendar-context')
  if (!context) throw new Error('CalendarSidebar must be inside CalendarContainer')
  
  const { sidebarWidth, isSidebarHidden } = context
  
  const sidebarStyle = computed(() => ({
    width: isSidebarHidden.value ? '0' : `${sidebarWidth.value}px`
  }))
  </script>
  ```

- [ ] **Добавить CSS для скрытия**
  - В `calendar-panel.css` и `calendar-sidebar.css`:
  
  ```css
  .calendar-panel,
  .calendar-sidebar {
    transition: width 0.2s ease, opacity 0.2s ease, padding 0.2s ease;
    overflow: hidden;
    flex-shrink: 0;
  }
  
  .calendar-panel--hidden,
  .calendar-sidebar--hidden {
    opacity: 0;
    padding: 0;
    border: none;
    pointer-events: none;
  }
  ```

- [ ] **Добавить визуальный маркер скрытой панели**
  - Когда Calendar скрыт, разделитель с иконкой `mdiDotsVertical` остаётся на **левой** границе контейнера
  - Когда Sidebar скрыт, разделитель с иконкой `mdiDotsVertical` остаётся на **правой** границе контейнера
  - Разделитель следует за курсором при перетаскивании
  - **Возврат панели:**
    - Потянули обратно от границы → разделитель следует за курсором
    - **Отпустили ≥ 300px** → панель появляется, разделитель остаётся где отпустили
    - **Отпустили < 300px** → разделитель **возвращается в край** (к границе), панель остаётся скрытой

### Этап 4: Интеграция и тестирование

**Цель:** Проверить работу drag, скрытия, возврата

- [ ] **Тест drag'n'drop:**
  - Перетаскивание разделителя влево/вправо
  - Calendar уменьшается/увеличивается, Sidebar увеличивается/уменьшается
  - Курсор меняется на `ew-resize` при наведении

- [ ] **Тест скрытия Calendar (drag влево):**
  - Drag влево до ширины Calendar < 300px
  - Calendar исчезает с анимацией
  - Разделитель (иконка) прилипает к **левой** границе контейнера

- [ ] **Тест возврата Calendar:**
  - Потянули обратно от левой границы → разделитель следует за курсором
  - **Отпустили ≥ 300px** → Calendar появляется, разделитель остаётся где отпустили
  - **Отпустили < 300px** → разделитель возвращается к левой границе, Calendar остаётся скрытым

- [ ] **Тест скрытия Sidebar (drag вправо):**
  - Drag вправо до ширины Sidebar < 300px
  - Sidebar исчезает с анимацией
  - Разделитель (иконка) прилипает к **правой** границе контейнера

- [ ] **Тест возврата Sidebar:**
  - Потянули обратно от правой границы → разделитель следует за курсором
  - **Отпустили ≥ 300px** → Sidebar появляется, разделитель остаётся где отпустили
  - **Отпустили < 300px** → разделитель возвращается к правой границе, Sidebar остаётся скрытым

- [ ] **Тест границ:**
  - Минимальная ширина: 300px (до скрытия)
  - Максимальной ширины нет — панель всегда прижата к разделителю
  - Панель не выходит за пределы `.worck-table`

---

## ⚠️ Риски и меры предосторожности

| Риск | Мера предосторожности |
|------|----------------------|
| **Конфликт drag с выделением текста** | `user-select: none` на `.calendar-resizer` во время drag |
| **Мерцание при drag** | Использовать `requestAnimationFrame` для плавности |
| **События мыши уходят за окно** | Вешать `mousemove`/`mouseup` на `document` |
| **Мобильные устройства** | Добавить поддержку touch-собыствий (опционально) |
| **Provide/Inject проблемы** | Проверить что все компоненты внутри CalendarContainer |

---

## ✅ Контрольные точки качества

- [ ] **Код-ревью после Этапа 1** — проверка структуры CalendarContainer
- [ ] **Код-ревью после Этапа 2** — проверка логики drag'n'drop
- [ ] **Код-ревью после Этапа 3** — проверка логики скрытия/возврата
- [ ] **Тесты производительности** — drag без лагов (60fps)
- [ ] **Кроссбраузерность** — Chrome, Firefox, Safari

---

## 📄 Файлы для создания/изменения

### Новые файлы (3):
```
maribulka-vue/src/components/calendar/
├── CalendarContainer.vue      # 🆕 Обёрточный контейнер + provide
├── CalendarResizer.vue        # 🆕 Разделитель с drag логикой
└── calendar-container.css     # 🆕 Стили контейнера
```

### Изменяемые файлы (4):
```
maribulka-vue/src/
├── App.vue                    # ✏️ Обернуть в CalendarContainer
└── components/calendar/
    ├── CalendarPanel.vue      # ✏️ Добавить класс скрытия, computed style
    ├── CalendarSidebar.vue    # ✏️ Добавить класс скрытия, computed style
    ├── calendar-panel.css     # ✏️ Добавить transition, .calendar-panel--hidden
    └── calendar-sidebar.css   # ✏️ Добавить transition, .calendar-sidebar--hidden
```

---

## 📈 Рекомендации

1. **Сохранение состояния** — запоминать ширину сайдбара в `localStorage`
2. **Touch-поддержка** — для планшетов (`touchstart`, `touchmove`, `touchend`)

---

*План создан: 2026-04-02*
