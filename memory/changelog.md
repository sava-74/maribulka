# Changelog - История изменений

## 06.03.2026 (продолжение) - Мобильная адаптация LaunchPad + TopBar

### ✅ Что сделано

**1. Мобильная адаптация LaunchPad:**
- `modal-overlay-launch` на мобилке: `display: block; overflow-y: auto; padding: 95px 5px 0 5px`
- Панель `.padGlass-work`: `box-sizing: border-box; width: 100%` — правильные отступы без `calc`
- Файлы: `modal.css`, `padGlass.css`

**2. Мобильная адаптация TopBar:**
- Убран `width: calc(100% - 20px)` — заменён на `left: 5px; right: 5px; width: auto`
- `box-sizing: border-box` чтобы border не выходил за отступы
- Базовый `.padGlass-top` переработан: `box-sizing: border-box`, `width: calc(100% - 40px)` сохранён для десктопа
- Файл: `padGlass.css`

**3. Брейкпоинт заменён на `pointer: coarse`:**
- Все `@media (max-width: 480px)` в новых CSS → `@media (pointer: coarse)`
- Файлы: `padGlass.css`, `modal.css`
- Результат: стили работают по типу устройства, не зависят от ширины/ориентации

**4. Скролл TopBar по X:**
- `.padGlass-top-row`: добавлен `overflow-x: auto` — если кнопок много, появляется горизонтальный скролл
- `body min-width`: 280px → 355px

**5. Повторное нажатие кнопки закрывает LaunchPad с анимацией:**
- `TopBar.vue`: проп `isLaunchpadOpen`, emit `close-launchpad`
- `LaunchPad.vue`: `defineExpose({ close })` — выставляет метод наружу
- `App.vue`: `ref` на LaunchPad, `@close-launchpad="launchpadRef?.close()"` — анимация работает

### ⚠️ Урок
- `defineExpose({ close })` — паттерн для вызова метода компонента с анимацией из родителя
- Всегда закрывать через `close()` из `useGenie`, никогда через прямое `showX = false`

---

## 06.03.2026 - Фикс анимации джина для Huawei + починка deploy.ps1

### ✅ Что сделано

**1. Фикс анимации геnie для Android (Huawei Mate 50 Pro):**
- Убран `border-radius` из keyframes `genieIn`/`genieOut` — устранён repaint дочерних элементов
- Добавлена GPU-изоляция в `.genie-enter` и `.genie-leave`:
  - `transform: translateZ(0)` — выносит на отдельный compositor layer
  - `backface-visibility: hidden` — GPU хинт
  - `contain: layout style paint` — изолирует `backdrop-filter` от пересчёта при scale
- Файл: `maribulka-vue/src/assets/animations.css`

**2. Починка deploy.ps1:**
- Исправлены пути: `$REMOTE_DIST = public_html`, `$REMOTE_API = api`
- Исправлен сломанный heredoc SSH команды симлинка (был незакрытый `\` + undefined `$sshCommand`)
- Бэкап снова работает с `maribulka-vue/dist`

### ⚠️ Урок
- `border-radius` в CSS keyframes → repaint на КАЖДОМ кадре → тормоза на слабых Android
- `backdrop-filter` пересчитывается при scale — изолировать через `contain: layout style paint`
- Ручной деплой: `npm run build` → `scp dist/* → public_html/` + `scp api/ → api/`

---

## 05.03.2026 (вечер) - Фикс структуры App + орбы

### ✅ Что сделано

**1. Фикс отступов TopBar на Android:**
- Причина: `min-width: 355px` в `.padGlass` конфликтовал с `width: calc(100% - 20px)` на мобилке
- Решение: добавлен `min-width: auto` в `.padGlass-top` в медиазапросе `@media (max-width: 480px)`
- Файл: `padGlass.css`

**2. Структура App.vue приведена к эталону:**
- Добавлен `<div class="app-root">` как корневой контейнер (аналог `sandbox-root`)
- Орбы обёрнуты в `<div class="app-bg-layer">` (аналог `sb-bg-layer`)
- `#app` закомментирован в `style.css`, добавлен `.app-root` с теми же стилями
- Файлы: `App.vue`, `style.css`

**3. Орбы — восстановлены и улучшены:**
- `position: fixed`, `opacity: 0.45` — восстановлено как было в коммите `14e0dfa`
- Размеры восстановлены: orb-1=600px, orb-2=400px, orb-3=350px
- Траектории улучшены: диагональные движения через весь экран (4 точки)
- Цвет orb-1 изменён: был тёмно-синий (почти невидим) → зелёно-бирюзовый `#00ff88 → #00b4d8`
- Цвет orb-1 светлая тема: `#00c96e → #00a8c8`

### ⚠️ Урок
- При переносе кода из эталона нужно переносить **структуру HTML** (App.vue), а не только CSS классы
- Перед любыми изменениями СНАЧАЛА читать App.vue и другие Vue файлы, потом CSS

---

## 05.03.2026 - ПОЛНЫЙ ДЕНЬ: Новый дизайн TopBar + Авторизация + Модалки

### ✅ Что сделано

**1. Новая CSS архитектура (3 глобальных файла):**
- `style.css` — темы `[data-theme]`/`[data-theme="light"]`, анимированные орбы, `body`, `#app`, scrollbar
- `buttonGlass.css` — стекло-кнопки `.btnGlass` + модификаторы `.bigIcon`, `.iconText`, `.btn-theme`
- `padGlass.css` — стекло-панели `.padGlass`, `.padGlass-top`, `.padGlass-top-row`, `.padGlass-work`
- Все старые CSS перенесены в `src/assets/oldCss/`
- `data-theme` устанавливается на `<html>` — НЕ на `#app`!

**2. Анимированные орбы:**
- 3 div-а `.orb .orb-1/2/3` в `App.vue` перед `<TopBar />`
- `position: fixed; filter: blur(80px)` — медленная анимация 35-45с через `@keyframes orbFloat1/2/3`
- Цвета `--orb-1/2/3` меняются с темой через CSS переменные

**3. TopBar — 4 кнопки:**
- Структура: `.padGlass.padGlass-top` → `.padGlass-top-row` → `.pad-icon-cell` → `.btnGlass.bigIcon`
- Кнопка 1: вход/лаунчпад (`mdiAccountOutline` / `mdiMenu`)
- Кнопка 2: домашняя страница (`mdiHome`)
- Кнопка 3: портфолио (`mdiImageMultiple`)
- Кнопка 4: переключатель темы (`.btn-theme` + `.btn-theme-indicator`)
- Ripple эффект: `onRipple($event)` — создаёт `.btn-ripple` span с CSS анимацией

**4. Авторизация в TopBar:**
- Убрана заглушка `const outin = false`
- Добавлен `useAuthStore()` → `auth.isAdmin`
- `outinIcon = computed(() => auth.isAdmin ? mdiMenu : mdiAccountOutline)` — кнопка меняет иконку
- **Критичный баг:** `computed` использовал `auth` до объявления (temporal dead zone) → исправлено: `const auth = useAuthStore()` теперь объявлен ДО всех computed
- `handleAction()`: `auth.isAdmin` → `showConfirm = true` (выход), иначе → `emit('open-login')` (вход)
- Удалён неиспользуемый `navStore`

**2. LoginModal подключён в App.vue:**
- `showLogin = ref(false)`
- `<TopBar @open-login="showLogin = true" />`
- `<LoginModal :isVisible="showLogin" @close="showLogin = false" />`
- **Баг:** Модалка не открывалась — TopBar делал emit, но App.vue не слушал. Исправлено добавлением `@open-login` и `<LoginModal>`.

**3. Создан `src/assets/modal.css` (НОВЫЙ ФАЙЛ):**
Старый `modal.css` лежал в `oldCss/` и не импортировался. Создан новый с нуля.
Классы:
- `.modal-overlay` — `position: fixed; inset: 0; z-index: 2000; backdrop-filter: blur(8px)` (без background — не нужен)
- `.modal-glassTitle` — заголовок модалки
- `.modal-message` — текст сообщения
- `.input-group` — группа инпутов
- `.modal-input` — стеклянный инпут с CSS переменными
- `.remember-label` — чекбокс "запомнить"
- `.padGlass.modal-sm` — субстиль для маленьких модалок: `min-width: auto; gap: 12px; padding: 20px`
- `.ButtonFooter` + `.PosCenter/.PosRight/.PosLeft/.PosSpace` — футер кнопок
- Подключён в `main.ts`: `import './assets/modal.css'`

**4. AlertModal, ConfirmModal, LoginModal — обновлены под новую систему:**
- Контейнер: `padGlass modal-sm` (вместо старого `modal-glass`)
- Кнопки: `btnGlass iconText` (вместо `buttonGL buttonGL-textFix`)
- В каждую кнопку добавлены две строки из эталона:
  ```html
  <span class="inner-glow"></span>
  <span class="top-shine"></span>
  ```
- Структура взята строго из эталона `SandboxView.vue` — один в один!

**5. `padGlass.css` — добавлен фон (строка 19):**
- `background: var(--glass-bg)` в `.padGlass` — пользователь добавил сам, т.к. фона не было

**6. `buttonGlass.css` — добавлен min-width (строка ~181):**
- `min-width: 100px` в `.btnGlass.iconText` — пользователь добавил сам

### ⚠️ Ошибки и уроки

1. **Temporal dead zone:** `computed(() => auth.xxx)` нельзя объявлять ДО `const auth = useAuthStore()`.
2. **Инлайн-стили запрещены:** Пытался написать `style="gap: 12px"` в шаблоне → НАРУШЕНИЕ. Правило: стили ТОЛЬКО в CSS файлах.
3. **Придумывать новые классы запрещено:** Вместо нового `.modal-panel` нужно использовать готовый `.padGlass`.
4. **Эталон = копировать AS IS:** При добавлении `inner-glow` и `top-shine` в кнопки — брать строго из `SandboxView.vue` без изменений.
5. **Проверять пользовательские изменения:** Пользователь внёс изменения в файлы вне диалога — нужно читать файлы перед работой.

### 📁 Изменённые файлы

| Файл | Что изменилось |
|------|---------------|
| `TopBar.vue` | `auth.isAdmin` вместо заглушки, порядок объявлений, `handleAction()` |
| `App.vue` | `LoginModal` импорт, `showLogin ref`, `@open-login` обработчик |
| `main.ts` | `import './assets/modal.css'` |
| `modal.css` | СОЗДАН НОВЫЙ (старый в oldCss/) |
| `padGlass.css` | `background: var(--glass-bg)` в `.padGlass` |
| `buttonGlass.css` | `min-width: 100px` в `.btnGlass.iconText` |
| `AlertModal.vue` | `padGlass modal-sm`, `btnGlass iconText`, `inner-glow`, `top-shine` |
| `ConfirmModal.vue` | Аналогично AlertModal |
| `LoginModal.vue` | Аналогично AlertModal |

---

## 26.02.2026 - Миграция с Quill на TipTap

### 🔐 Устранение уязвимостей npm

**Проблема:**
- 2 moderate XSS уязвимости в quill
- Устаревший пакет @vueup/vue-quill

**Решение:**
Полная замена Quill на TipTap v3.20.0

**Удалено:**
```json
"@vueup/vue-quill": "^1.3.1",
"quill": "^2.0.3"
```

**Добавлено:**
```json
"@tiptap/vue-3": "^3.20.0",
"@tiptap/starter-kit": "^3.20.0",
"@tiptap/extension-underline": "^3.20.0",
"@tiptap/extension-text-align": "^3.20.0",
"@tiptap/extension-text-style": "^3.20.0",
"@tiptap/extension-color": "^3.20.0",
"@tiptap/extension-image": "^3.20.0",
"@tiptap/extension-link": "^3.20.0"
```

**Результат:** 0 vulnerabilities (было 2 moderate)

### 📝 Новый компонент RichTextEditor.vue

**Файл:** `src/components/RichTextEditor.vue`

**Функционал:**
- Заголовки (H1, H2, H3)
- Форматирование (жирный, курсив, подчёркнутый, зачёркнутый)
- Списки (маркированный, нумерованный)
- Выравнивание (по левому краю, центру, правому краю, ширине)
- Изображения с resize (ручки по углам и сторонам)
- Ссылки с кастомной модалкой ввода URL
- Очистка форматирования

**Конфигурация Image:**
```typescript
TiptapImage.configure({
  inline: true,
  allowBase64: true,
  resize: {
    enabled: true,
    minWidth: 50,
    minHeight: 50,
    alwaysPreserveAspectRatio: true
  }
})
```

**Загрузка изображений:**
- File picker через `<input type="file">`
- Конвертация в base64
- Вставка через `editor.chain().focus().setImage({ src: base64 })`

**Вставка ссылок:**
- Кастомная модалка вместо `window.prompt()`
- Класс `.modal-footerUrl` для кнопок (Отмена слева, Сохранить справа)
- Enter/Esc для управления

### 🎨 Стили TipTap

**Файл:** `src/assets/tiptap.css`

**Философия:** Минимальные стили, БЕЗ переопределения HTML тегов

**Только служебные стили:**
- `.tiptap-wrapper`, `.tiptap-toolbar`, `.tiptap-content`, `.tiptap-editor`
- Placeholder для пустого редактора
- Resize handles для изображений (`[data-resize-handle]`)
- 100% CSS переменные из theme.css

**НЕ переопределяется:**
- h1, h2, h3, p, ul, ol, a, img, blockquote, code (используют браузерные стили)

### 🎯 Модалка ввода URL

**Новый класс:** `.modal-footerUrl` (modal.css, строки 637-643)

**Отличия от `.modal-footer`:**
- `.modal-footer`: `justify-content: flex-end` (кнопки справа)
- `.modal-footerUrl`: `justify-content: space-between` (кнопки по краям)

**Использование:**
```vue
<div class="modal-footerUrl">
  <button @click="cancel">Отмена</button>
  <button @click="save">Сохранить</button>
</div>
```

### ✅ Результат

- ✅ 0 уязвимостей npm
- ✅ Современный редактор TipTap
- ✅ Resize изображений с сохранением пропорций
- ✅ Кастомная модалка вместо browser prompt
- ✅ Минимальные стили (БЕЗ переопределений)

---

## 26.02.2026 - Оптимизация сборки (code splitting)

### ⚡ Разбиение на чанки

**Файл:** `vite.config.ts` (строки 15-26)

**Конфигурация manual chunks:**

```typescript
manualChunks: {
  'vue-vendor': ['vue', 'pinia'],
  'fullcalendar': ['@fullcalendar/core', '@fullcalendar/vue3', '@fullcalendar/daygrid', '@fullcalendar/timegrid', '@fullcalendar/interaction'],
  'charts': ['chart.js', '@tanstack/vue-table']
}
```

**Преимущества:**

1. **vue-vendor** (Vue + Pinia):
   - Основные фреймворки в отдельном чанке
   - Кэшируется долго (редко меняется)
   - Загружается первым

2. **fullcalendar** (FullCalendar):
   - Большая библиотека календаря в отдельном чанке
   - Ленивая загрузка при переходе на вкладку "Записи"
   - Не влияет на начальную загрузку

3. **charts** (Chart.js + TanStack Table):
   - Графики и таблицы
   - Используется на нескольких вкладках

**Лимит:** `chunkSizeWarningLimit: 600` (увеличен с 500kb)

**Результат:**
- ⚡ Быстрая начальная загрузка
- 📦 Лучшее кэширование
- 🔄 Изменения в коде не инвалидируют vendor chunks

---

## 26.02.2026 - Удаление всех `<style>` блоков из Vue компонентов

### 🧹 Очистка от инлайн-стилей

**Проблема:**
В 4 компонентах обнаружены `<style scoped>` блоки, нарушающие **ЗАКОН № 2** проекта.

**Удалено из файлов:**

1. **CancelBookingModal.vue** (строки 178-191):
   - `.payment-warning` (background, border, padding)
   - `.payment-warning .hint` (font-size, color)

2. **ConfirmSessionModal.vue** (строки 131-167):
   - `.confirm-info`, `.prepayment-ok`, `.prepayment-warning`
   - `.info-box`, `.info-box ul`, `.info-box li`
   - `.divider`

3. **DeliverBookingModal.vue** (строки 165-229):
   - `.delivery-info`, `.price-value`, `.remaining-warning`, `.remaining-amount`
   - `.paid-full`, `.divider`, `.order-result-section`
   - `.radio-group`, `.radio-label`, `.radio-label:hover`
   - `.radio-label input[type="radio"]`, `.radio-label span`

4. **App.vue** (строки 66-68):
   - Пустой `<style scoped>` блок

**Статус:** ✅ Во ВСЕХ .vue файлах проекта теперь **НЕТ ни одного `<style>` блока**

**Следующий шаг:** Если эти стили нужны - добавить их в соответствующие CSS файлы (modal.css, tables.css) **с явного разрешения пользователя**.

---

## 26.02.2026 - Замена иконок в модалках на Material Design Icons

### 🎨 Унификация иконок

**Выполнено пользователем:**
- ✅ Заменены все иконки в модалках на `@mdi/js` (Material Design Icons)
- ✅ Прямые импорты из `@mdi/js` в каждом компоненте
- ✅ Использование стандартных иконок:
  - `mdiCheckCircleOutline` - "Ок"
  - `mdiCloseCircleOutline` - "Закрыть"
  - `mdiDeleteCircleOutline` - "Удалить"
  - `mdiCameraPlusOutline` - "Добавить фото"
- ✅ Размер и цвет через CSS переменные (`--svg-sizeButton`, `--svg-colorButton`)
- ✅ Текст в кнопках с классом `.textButton`

**Паттерн записан в память:** [patterns.md](patterns.md#иконки)

---

## 26.02.2026 - Исправление вкладок в Справочниках

### Проблема
После рефакторинга glass-panel.css кнопки вкладок в Справочниках разъехались по всей ширине панели из-за глобального `justify-content: space-between`.

### Решение

- Убрал `justify-content: space-between` из базового `.glass-panel-tabs-nav`
- Создал суб-стиль `.glass-panel-tabs-nav.accounting-tabs` для Бухгалтерии
- Добавил класс `accounting-tabs` в [Accounting.vue:59](../maribulka-vue/src/components/accounting/Accounting.vue#L59)
- Обернул кнопки в [References.vue:23](../maribulka-vue/src/components/accounting/References.vue#L23) в `.tabs-group-left`

### Результат

- ✅ Справочники: кнопки слева с отступом (базовый стиль)
- ✅ Бухгалтерия: вкладки слева + фильтр периода справа (суб-стиль)

---

## 25.02.2026 - Полный рефакторинг стилей модалок

### 🎨 ГЛОБАЛЬНЫЙ РЕФАКТОРИНГ: Унификация стилей модалок

**Цель:** Привести все стили модалок к единым переменным проекта, убрать хардкод, обеспечить 100% использование CSS переменных.

---

### ✅ 1. Новые CSS переменные в theme.css

**Добавлено 3 новые переменные:**

```css
--glass-bgModal: rgb(228 228 228 / 85%);     /* Фон модальных окон */
--glass-bgFilterOverlay: blur(10px);         /* Размытие оверлея */
--glass-bgFilterModal: blur(3px);            /* Размытие модалки */
```

**Обновлено:**
```css
--glass-bgFilter: blur(5px);                 /* Было blur(12px) → стало blur(5px) */
```

---

### ✅ 2. Полный рефакторинг modal.css (808 → 740 строк)

**Удалено:**
- ❌ Весь блок `@media (max-width: 480px)` (-68 строк)
- ❌ Мобильная адаптация модалок (будет делаться отдельно по roadmap.md)

**Заменено на переменные:**

| Было (хардкод) | Стало (переменная) |
|----------------|-------------------|
| `font-size: 13px/14px/1.2rem` | `var(--generalTextSizeSmall)` (8px) |
| `font-size: 20px` (заголовки) | `var(--generalTextSize)` (10px) |
| `padding: 30px/20px/12px/10px` | `padding: 5px` (единообразно!) |
| `gap: 10px/12px/20px` | `gap: 5px` (единообразно!) |
| `border-radius: 8px/15px` | `var(--panelRadius)` (12px) |
| `border-radius: 4px` (инпуты) | `var(--glass-borderRadiusButton)` (6px) |
| `width: 320px/600px/800px` | `width: 150px` (базовая, расширяется по содержимому) |
| `background: rgba(255,255,255,0.8)` | `var(--glass-bgModal)` |
| `backdrop-filter: blur(5px/60px)` | `var(--glass-bgFilterModal)` / `var(--glass-bgFilterOverlay)` |
| `box-shadow: 0 0 30px rgba(...)` | `var(--glass-shadowPanel)` |

**Заменено цвета на семантические:**

| Хардкод | Переменная |
|---------|------------|
| `#dc2626`, `#f87171` | `var(--expenseColor)` |
| `#059669`, `#4ade80` | `var(--incomeColor)` |
| `#d97706` | `var(--warningColor)` |
| `#2196f3` | `var(--infoColor)` |
| `#1e40af` | `var(--linkColor)` |

**Удалены фоны из информационных блоков:**
- `.payment-info` - БЕЗ `background: rgba(255,255,255,0.2)`
- `.delete-info` - БЕЗ `background: rgba(248,113,113,0.1)`
- `.price-info` - БЕЗ `background: rgba(74,222,128,0.1)`
- **Теперь:** Единый фон `var(--glass-bgModal)` для всех модалок, различие только в **цветах текста**!

---

### ✅ 3. Создан новый класс `.modal-glassTitle`

**Проблема:** Использовался тег `<h2>` для заголовков модалок.

**Решение:**
```css
/* ❌ БЫЛО */
.modal-glass h2 {
  font-size: 1.2rem;
}

/* ✅ СТАЛО */
.modal-glassTitle {
  margin: 0;
  text-align: center;
  font-size: var(--generalTextSize); /* 10px */
  color: var(--generalTextColor);
}
```

---

### ✅ 4. Обновлено 31 компонент Vue

**Заменено во ВСЕХ модалках:**
```vue
<!-- ❌ БЫЛО -->
<h2>Заголовок модалки</h2>

<!-- ✅ СТАЛО -->
<div class="modal-glassTitle">Заголовок модалки</div>
```

**Список обновлённых компонентов:**
- **Базовые модалки (3):** AlertModal, ConfirmModal, LoginModal
- **Home модалки (2):** EditStudioDescriptionModal, UploadPhotoModal
- **Accounting модалки (26):** Add/Edit/View для всех сущностей (Bookings, Clients, Expenses, Promotions, ShootingTypes, ExpenseCategories) + специальные модалки (Cancel, Deliver, Refund, Actions, ConfirmSession, DeleteConfirm, AddPayment, PeriodSelector)

**Всего:** 32 замены тега `<h2>` → `<div class="modal-glassTitle">` в 31 файле.

---

### ✅ 5. Очищен responsive.css

**Проблемы:**
- Дубликаты стилей из modal.css (`.input-field-*`, `.modal-glass.modal-compact .modal-input`)
- Хардкод `font-size: 13px` вместо переменных
- Весь файл = `@media (max-width: 480px)` блок

**Решение:**
- ✅ Файл очищен полностью
- ✅ Оставлен комментарий: *"Будет заполнен при реализации мобильной адаптации (roadmap.md)"*
- ✅ 16 импортов `import '../../assets/responsive.css'` в компонентах - НЕ тронуты (безопасно)

---

### 📊 Статистика изменений

| Файл | Было | Стало | Изменения |
|------|------|-------|-----------|
| **theme.css** | 75 строк | 78 строк | +3 переменные |
| **modal.css** | 808 строк | 740 строк | -68 строк, ~80 замен хардкода на переменные |
| **responsive.css** | 128 строк | 6 строк | Очищен полностью |
| **Vue компоненты** | 31 файл | 31 файл | 32 замены h2 → div.modal-glassTitle |

**Использование переменных в modal.css:** 100% ✅

---

### 🎯 Единая система стилей модалок

**Базовые правила (ЖЕЛЕЗНЫЙ ЗАКОН!):**

| Параметр | Значение | Переменная |
|----------|----------|------------|
| Текст (основной) | 8px | `var(--generalTextSizeSmall)` |
| Заголовки | 10px | `var(--generalTextSize)` |
| Padding | 5px | `5px` (везде единообразно!) |
| Gap | 5px | `5px` (везде единообразно!) |
| Border-radius (панель) | 12px | `var(--panelRadius)` |
| Border-radius (инпуты) | 6px | `var(--glass-borderRadiusButton)` |
| Width | auto | По содержимому! |
| Фон модалки | rgb(228 228 228 / 85%) | `var(--glass-bgModal)` |
| Blur overlay | blur(10px) | `var(--glass-bgFilterOverlay)` |
| Blur glass | blur(3px) | `var(--glass-bgFilterModal)` |
| Тень | 4px 4px 6px | `var(--glass-shadowPanel)` |

**Цвета (семантика):**
- Расход/опасность: `var(--expenseColor)` (#f87171)
- Доход/успех: `var(--incomeColor)` (#4ade80)
- Предупреждение: `var(--warningColor)` (#fbbf24)
- Информация: `var(--infoColor)` (#3b82f6)
- Ссылки: `var(--linkColor)` (#1e40af)

---

### 🚀 Итоги рефакторинга

✅ **Достигнуто:**
1. 100% использование CSS переменных в modal.css
2. Полная унификация padding и gap (5px везде)
3. Удаление всех фонов из информационных блоков (единый фон модалки)
4. Создание семантического класса `.modal-glassTitle`
5. Обновление 31 компонента (замена h2 на div)
6. Очистка responsive.css от дубликатов и хардкода
7. Сокращение кода на 68 строк в modal.css

✅ **Следующий этап:** Мобильная адаптация модалок (см. roadmap.md, Этап 1)

---

## 24.02.2026 - Единый фильтр периода для всех вкладок Учёт

### ✨ Новое: Фильтр периода с flatpickr

**Проблема:**
- Каждая вкладка (Запись, Приход, Расход, Отчёты) имела свой фильтр месяца
- Требовалось сделать единый фильтр периода (с/по) для всех вкладок
- Период должен сохраняться при переключении вкладок

**Решение:**

1. **Установлена библиотека flatpickr:**
   ```bash
   npm install flatpickr
   ```

2. **Созданы компоненты:**
   - `PeriodSelector.vue` - компонент с flatpickr (range mode) + пресеты
   - `PeriodSelectorModal.vue` - модальное окно для выбора периода
   - **ВАЖНО:** БЕЗ `<style>` блоков! Используют ТОЛЬКО базовые классы

3. **Обновлён `Accounting.vue`:**
   - Добавлено состояние: `periodStart`, `periodEnd`, `showPeriodModal`
   - Кнопка фильтра периода в правой группе вкладок
   - Формат: "с 01.02.26 по 28.02.26"
   - Модалка вынесена за пределы `.glass-panel-tabs` для правильного z-index

4. **Props drilling:** Все дочерние компоненты получили props:
   - `BookingsCalendar.vue`
   - `BookingsFullCalendar.vue`
   - `IncomeTable.vue`
   - `ExpensesTable.vue`
   - `Reports.vue`

5. **Watch с immediate: true:**
   ```typescript
   watch([() => props.periodStart, () => props.periodEnd], async () => {
     const start = formatDateForAPI(props.periodStart)
     const end = formatDateForAPI(props.periodEnd)
     await store.fetchData(start, end)
   }, { immediate: true })
   ```

6. **Обновлены PHP API:**
   - `api/bookings.php` - поддержка `?start=...&end=...` с `BETWEEN`
   - `api/income.php` - аналогично
   - `api/expenses.php` - аналогично
   - Сохранена обратная совместимость с `?month=...`

7. **Pinia stores обновлены:**
   - `stores/bookings.ts` - `fetchBookings(start?, end?)`
   - `stores/finance.ts` - `fetchIncome(start?, end?)`, `fetchExpenses(start?, end?)`

8. **Пресеты периодов:**
   - Текущий месяц
   - Прошлый месяц
   - Последние 3 месяца
   - Текущий год

9. **flatpickr настройки:**
   - `mode: 'range'` - выбор диапазона
   - `locale: Russian` - русская локализация
   - `dateFormat: 'd.m.y'` - формат ДД.ММ.ГГ
   - `onChange` - emit события при выборе

**Критические уроки (НАРУШЕНИЯ!):**

⚠️ **Многократно нарушено правило:** НИКАКИХ `<style>` блоков в .vue файлах!
- Изначально добавлены стили в `PeriodSelector.vue` и `PeriodSelectorModal.vue`
- Придуманы несуществующие переменные и классы
- **Исправлено:** Все `<style>` блоки удалены
- Используются ТОЛЬКО базовые классы: `.modal-overlay`, `.modal-glass`, `.modal-actions`, `.glass-button-text`

**Правило:** Новые стили ТОЛЬКО с явного разрешения пользователя!

**Файлы:**
- ✅ `PeriodSelector.vue` - БЕЗ стилей
- ✅ `PeriodSelectorModal.vue` - БЕЗ стилей, только базовые классы
- ✅ `Accounting.vue` - состояние периода, кнопка фильтра
- ✅ `api/bookings.php`, `income.php`, `expenses.php` - поддержка start/end
- ✅ `stores/bookings.ts`, `finance.ts` - методы с опциональными параметрами

**Статус:** ✅ Работает, но требуется убрать debug логирование (🔵🟡🟣🟢)

---

## 23.02.2026 (вечер) - Создана система универсальных панелей

### ✨ Новое: Универсальная система панелей

**Файлы:**
- ✅ Создан `glass-panel.css` - базовые стили панелей
- ✅ Добавлена переменная `--glass-tab-inactive-border` в theme.css
- ✅ Подключён glass-panel.css в main.ts
- ✅ Создан [glass-panel-guide.md](glass-panel-guide.md) - полное руководство

**Типы панелей:**

1. **`.glass-panel`** - базовая панель с glass-эффектом
2. **`.glass-panel-tabs`** - панель с вкладками (folder-style)
   - Вкладки как кнопки, активная становится частью панели
   - Активная вкладка: border var(--generalColor)
   - Неактивная: border var(--glass-tab-inactive-border)
   - Динамическое скругление углов панели
3. **`.glass-panel-tabs-sticky`** - sticky навигация (top: 86px)

**Характеристики:**
- backdrop-filter: blur(60px)
- padding: 6px (desktop), 4px (mobile)
- tab height: 30px (desktop), 26px (mobile)
- font-size: var(--generalTextSizeSmall) = 8px
- border-radius: 8px (фиксированный, только верхние углы)
- gap между вкладками: 0px

**Цель:** Убрать дублирующиеся стили, унифицировать панели (Этап 2: Roadmap)

**Следующий шаг:** Миграция `.accounting-nav` и `.references-nav`

---

## 23.02.2026 (утро) - Исправлены критические баги + оптимизация баланса

### 🐛 Баг #1: Кнопки "Редактировать" и "Удалить" всегда неактивны

**Проблема:**

- Кнопки edit/delete были неактивны даже для заказов со статусом `new` и датой съёмки в будущем
- Причина: `shooting_date` содержит DATETIME (`2026-02-23 09:00:00`), а код пытался склеить с `shooting_time` (undefined)
- Результат: `new Date("2026-02-23 09:00:00 undefined")` = Invalid Date

**Решение:** `BookingsCalendar.vue`

```typescript
const shootingDateTime = selectedBooking.value.shooting_time
  ? new Date(`${selectedBooking.value.shooting_date} ${selectedBooking.value.shooting_time}`)
  : new Date(selectedBooking.value.shooting_date)
```

---

### 🐛 Баг #2: Модалка возврата показывает "недостаточно средств", хотя деньги есть

**Проблема:**

- При открытии `RefundModal` баланс кассы = 0 (данные не загружены)
- `financeStore.totalIncome/totalExpenses` считаются по данным `income.value/expenses.value` (только текущий месяц или пустые)
- Приходилось сначала зайти на вкладку "Отчёты", потом возвращаться

**Решение (Backend считает!):**

1. **API:** `GET /api/expenses.php?balance=true`
   - Backend делает `SELECT SUM(amount) FROM income` и `FROM expenses`
   - Возвращает только 3 числа: `{ totalIncome, totalExpenses, balance }`
   - Быстро даже на больших объёмах (MySQL оптимизирован для SUM)

2. **Store:** `finance.ts` → метод `fetchCashBalance()`
   - Вызывает API, получает баланс

3. **Component:** `RefundModal.vue`
   - При открытии модалки загружается свежий баланс
   - Добавлено состояние загрузки `loadingBalance`
   - Показывается "⏳ Проверка баланса кассы..." → устранено мигание "недостаточно средств"

**Принцип (ЗАКОН!):** Backend считает, Frontend получает результат

- ✅ Backend: агрегация (SUM, COUNT, AVG) на уровне БД
- ✅ Frontend: получает только итоговые данные (числа, объекты)
- ❌ НЕ гонять массивы записей по сети для подсчёта на клиенте!

---

### 🎨 Организация стилей (исправлен смертный грех!)

**Проблема:** Добавил `<style scoped>` блок в `RefundModal.vue` → нарушение правила проекта!

**Решение:**

- Удалён `<style scoped>` блок из `RefundModal.vue`
- Все стили перенесены в `assets/modal.css` (секция `REFUND MODAL`)
- Добавлены: `.refund-info`, `.refund-amount`, `.form-group`, `.form-textarea`, `.info-message`, `.warning-message`, `.success-message`

**Правило проекта:**

- **НИКАКИХ** `<style>` блоков в .vue файлах!
- Один тип стилей = один CSS файл

---

## 17.02.2026 - Синхронизация авторизации Frontend ↔ Backend

### Проблема
- Frontend (localStorage) и Backend (PHP session) не синхронизированы
- После выхода/таймаута сессии бэкенд возвращает 403, но фронтенд думает что пользователь авторизован
- Кнопки редактирования видны, но API запросы отклоняются

### Решение

**1. Новый API endpoint:** `api/auth.php`
- `GET /api/auth.php?action=check` - проверка сессии
- `POST /api/auth.php?action=login` - вход
- `POST /api/auth.php?action=logout` - выход

**2. Обновлён `stores/auth.ts`**
- Метод `checkSession()` - проверяет PHP сессию через API
- Метод `logout()` - теперь async, вызывает API
- Синхронизация `localStorage` с бэкенд сессией

**3. Автопроверка сессии:** `App.vue`
- При загрузке приложения вызывается `authStore.checkSession()`
- Если сессия невалидна - очищается `localStorage` и `isAdmin = false`

**4. Обновлены компоненты:**
- `LoginModal.vue` - использует `/api/auth.php?action=login`
- `TopBar.vue` - async logout через API

**5. Устаревший файл:**
- ❌ `api/login.php` - НЕ УДАЛЯТЬ! (может использоваться в старых запросах)

### Как это работает
1. Пользователь открывает сайт → `App.vue` вызывает `checkSession()`
2. API проверяет PHP сессию
3. Если сессия валидна → `isAdmin = true`
4. Если сессия истекла → `isAdmin = false`, `localStorage` очищен
5. При выходе → вызов API `/logout` + очистка локального состояния

---

## 17.02.2026 - Очистка SQL файлов

### Удалены дубликаты и мусор
- ❌ Удалена папка `backup_api_with_copy/` (дубликаты SQL и PHP)
- ❌ Удалена папка `sql/` с недописанной миграцией

### Дописаны таблицы в init-database.sql
- ✅ Добавлена таблица `studio_photos` в основной файл инициализации
- ✅ Добавлена таблица `studio_description` (правильный формат!)
  - Поле `created_at` TIMESTAMP
  - Поле `updated_at` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  - ENGINE=InnoDB, utf8mb4_unicode_ci
  - Дефолтное описание студии
- ✅ Добавлены DROP TABLE для обеих таблиц

### Итоговая структура SQL файлов
```
api/
├── init-database.sql          # Полная схема БД + тестовые данные
├── add_failed_status.sql      # Миграция: статус 'failed'
├── add_notes_field.sql        # Миграция: поле 'notes' в bookings
└── migrations/
    └── create_studio_photos.sql  # Миграция: таблица studio_photos
```

---

## 16.02.2026 - Редактор описания студии

### Rich Text Editor (Quill)

**Установка библиотеки:**
```bash
npm install quill @vueup/vue-quill
```

**БД - таблица studio_description:**
```sql
CREATE TABLE studio_description (
  id INT PRIMARY KEY AUTO_INCREMENT,
  content TEXT NOT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Новые файлы:**
- `EditStudioDescriptionModal.vue` - модалка с Quill редактором
- `api/studio_description.php` - API endpoint (GET/POST, защита admin)
- Обновлён `stores/home.ts` - методы `fetchDescription()`, `updateDescription()`

**Функционал редактора:**
- Заголовки (H1, H2, H3)
- Форматирование текста (жирный, курсив, подчёркнутый, зачёркнутый)
- Списки (нумерованные, маркированные)
- Выравнивание текста
- Цвет текста и фона
- Загрузка изображений с автоматическим ресайзом (200x300px, base64)
- Ссылки
- Очистка форматирования

**Мобильная адаптация:**
- Модалка фиксированной ширины 600px
- Горизонтальный скролл на экранах <600px
- `display: block` на overlay для вертикального скролла

**Исправление ссылок:**
- Автоматическое добавление `https://` для относительных ссылок
- Обработка кликов - открытие в новой вкладке
- `target="_blank"` и `rel="noopener noreferrer"` для всех ссылок

**Безопасность:**
- ⚠️ XSS защита ОТСУТСТВУЕТ! (`v-html` без санитизации)
- Текущая защита: только админ может редактировать (проверка на бэкенде)
- TODO: добавить DOMPurify

---

## 14.02.2026

### 1. Мобильная адаптация модальных окон

**Проблемы:**
- Большие модалки обрезались сверху/снизу
- Маленькие модалки были прижаты к верху

**Решения:**
- **Большие модалки:** `display: block` + `overflow-y: auto` на overlay
- **Маленькие модалки:** новый класс `.modal-small` с центрированием
- **Файл:** `modal.css` (строки 550-588)

**Изменённые компоненты:**
- `AlertModal.vue` - добавлен класс `modal-small`
- `ConfirmModal.vue` - добавлен класс `modal-small`
- `LoginModal.vue` - добавлен класс `modal-small`

### 2. Календарь - финансовая информация в режиме дня

**Вторая строка записи:**
- `unpaid` → `, долг {сумма} ₽`
- `partially_paid` → `, оплачено {сумма} ₽, долг {остаток} ₽`
- `fully_paid` → ничего не добавляем

**Файл:** `BookingsFullCalendar.vue` (строки 198-211)

### 3. AddBookingModal - поле оплаты

**Проблемы:**
1. Поле оплаты по умолчанию равнялось итоговой сумме
2. Использовало `position: absolute` → ломало вёрстку

**Решения:**
1. Удалён `watch`, теперь `paymentAmount = 0` по умолчанию
2. Создан класс `.payment-input-inline` (inline, фиксированная ширина 60px)

**Файлы:**
- `AddBookingModal.vue` (строка 25, удалены строки 115-118, line 417)
- `modal.css` (строки 284-293)

### 4. Проект переименован "FotoMari" → "Марибулька"

**index.html:**
- `<html lang="ru">`
- `<title>Марибулька</title>`
- `<link rel="icon" href="/camera.svg">`

**camera.svg:**
- Новый SVG файл с иконкой камеры
- `fill="currentColor"` для динамической окраски

**theme.css:**
- `link[rel="icon"] { color: var(--generalColor); }`
- Favicon окрашен в генеральный цвет (#39FF14)

### 5. Убрана лишняя прокрутка в content

**Проблема:** Даже когда контент влезал, была прокрутка вверх на ~половину экрана.

**Решение:**
- Убран `min-height: 100vh` из `.accounting`
- Добавлен `min-height: 400px` для предотвращения схлопывания

**Файл:** `layout.css`

### 6. Мобильный календарь - цвета точек

**Проблема:** В режиме дня все записи становились красными если хоть одна красная.

**Решение:**
- Приоритет красного ТОЛЬКО в режиме месяца на мобилке
- Условие: `if (windowWidth.value <= 768 && !isDayView.value)`

**Файл:** `BookingsFullCalendar.vue` (строки 153-159)

---

## 13.02.2026 - Таблица "Акции"

### 1. Timeline визуализация акций

**Новый компонент:** `PromotionsTimeline.vue`

**Функционал:**
- График акций на год (365/366 дней)
- Блоки акций позиционируются по датам начала/конца
- Черточки для каждого дня, длинные для 1-го числа месяца
- Подписи месяцев внутри шкалы
- Цвета акций: 10 уникальных пастельных цветов (ротация по ID)
- Формат даты в тултипе: ДД.ММ.ГГ

### 2. Форматирование дат и процентов

**Файлы:** `PromotionsTable.vue`, `ViewPromotionModal.vue`

**Изменения:**
- Дата: ДД.ММ.ГГ (вместо ДД.ММ.ГГГГ)
- Проценты: целое число (`Math.round()`)
- Заголовки таблицы сокращены:
  - "ID" → "№"
  - "Скидка %" → "%"
  - "Дата начала" → "Начало"
  - "Дата окончания" → "Конец"

### 3. Стили таблицы "Акции"

**Файл:** `tables.css`

**Изменения:**
- Центрирование колонок: ID, Дата начала, Дата окончания (строки 378-382)
- Мобильная адаптация (≤768px):
  - Примечание (ClientsTable, 4-я колонка): `max-width: 100px`
  - Описание (ShootingTypesTable, 5-я колонка): `max-width: 100px`
  - Текст обрезается многоточием
- Размер шрифта таблиц: 12px (было 14px)
- `max-height` для `.table-containerTab`: `calc(100vh - 300px)`

### 4. API проверка пересечений периодов акций

**Файл:** `api/promotions.php`

**Функционал:**
- При создании/редактировании акции проверяется пересечение дат
- HTTP 409 Conflict при пересечении с сообщением о конфликтующей акции
- Игнорируются бессрочные акции (start_date/end_date = NULL)

---

## 13.02.2026 - Домашняя страница

### 1. Структура домашней страницы

**Файлы:** `Home.vue`, `home.css`

**Компоненты:**
- Баннер акции (sticky, желтый фон, красный текст)
- 4 фото студии (горизонтальная линия без зазоров)
- Описание студии (текстовый блок)

### 2. Баннер действующей акции

**Файл:** `home.css` (строки 8-23)

**Особенности:**
- `position: sticky` - прилипает к топу при прокрутке
- Десктоп: `top: 87px; margin-top: -8px;`
- Мобилка: `top: 69px;`
- Жёлтый фон (#FFD700), красный текст (#DC143C)
- Текст auto-shrink: `clamp(16px, 4vw, 32px)`
- Показывается только если есть действующая акция

### 3. Загрузка фото студии

**Файлы:** `UploadPhotoModal.vue`, `api/studio_photos.php`, `stores/home.ts`

**БД:** Таблица `studio_photos` (id, photo_url, position, created_at)

**Хранение:**
- Медиа: `/home/s/sava7424/maribulka.rf/media/home/` (вне dist!)
- Симлинк: `dist/media -> ../../media`
- URL: `/media/home/studio_123.jpg`

**Обработка:**
- Автоматическое сжатие (макс 1920x1080, качество 85%)
- Форматы: JPG, PNG, WEBP
- Размер: макс 5 МБ
- Замена старого фото при загрузке на ту же позицию

### 4. Модалка загрузки фото

**Файл:** `UploadPhotoModal.vue`

**Функционал:**
- Preview загруженного фото (max-height: 200px)
- Статичная надпись "Выбрать файл"
- Кнопки: Загрузить, Удалить, Закрыть
- Доступна только для админа

### 5. GitHub Actions и деплой

**Файлы:** `.github/workflows/deploy.yml`, `deploy.ps1`

**Исправления:**
- Проверка сайта: HTTPS вместо HTTP (редирект 301 → 200)
- Проверка API: HTTPS вместо HTTP
- Добавлен симлинк media в deploy.ps1 (строка 84)
- PowerShell скрипт проверяет HTTPS с `-MaximumRedirection 5`

---

## 09.02.2026 - Исправления багов

### 1. Баг проведения заказа

**Проблема:** Сравнение `DATETIME > DATE` всегда true из-за времени.

**Решение:** Разделили логику на два action:
- `complete`: проверяет `DATE(shooting_date) <= today`, ставит status='completed'
- `deliver`: проверяет `status='completed'`, ставит status='delivered' + processed_at=NOW()

**Файл:** `api/bookings.php` (lines 375-410)

### 2. Телефон в ViewBookingModal

**Изменения:**
- Сделан кликабельной ссылкой `<a href="tel:...">`
- Цвет: темно-синий (#1e40af)

---

## Текущие задачи

- ✅ Надписи в режиме день
- ✅ Модалки 'Новая' и 'Редактировать'
- ✅ Алгоритм свободных слотов
- ✅ Защита от записи в прошлом
- ✅ Мобильная адаптация календаря
- ✅ Цвета статусов
- ✅ Телефон в календаре контрастный
- ✅ Таблица "Акции" (PromotionsTable)
- ✅ Домашняя страница (баннер акции, 4 фото)
- ✅ Блок описания студии (rich text editor)
- ⏳ Кнопка скрыть/показать таблицу
- ⏳ Доработка мобильной адаптации
