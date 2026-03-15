# DatePicker.vue — Спецификация дизайна

**Дата:** 2026-03-15
**Статус:** Согласовано с пользователем

---

## Цель

Создать переиспользуемый Vue 3 компонент выбора даты/диапазона без зависимости от flatpickr. Компонент реализует собственный попап-календарь со стеклянными стилями проекта — никаких `!important`, никакого переопределения чужих стилей.

После внедрения — `flatpickr` и `flatpickr.css` удаляются из проекта. `BookingFormModal.vue` и `BookingsTable.vue` мигрируют на новый компонент.

---

## Расположение файлов

```text
src/components/ui/DatePicker.vue    — компонент
src/components/ui/datePicker.css    — стили (рядом с компонентом)
```

CSS импортируется в `main.ts`.

---

## Props

```ts
interface Props {
  mode?: 'single' | 'range'    // default: 'single'
  modelValue?: string | DateRange  // v-model
  minDate?: string             // формат YYYY-MM-DD
  maxDate?: string             // формат YYYY-MM-DD
  placeholder?: string         // текст пустого инпута
  showToday?: boolean          // кнопка Сегодня/Сброс, default: true
}

interface DateRange {
  from: string   // YYYY-MM-DD
  to: string     // YYYY-MM-DD
}
```

## Emits

```ts
emit('update:modelValue', value: string | DateRange)
// string — формат YYYY-MM-DD (single)
// DateRange — { from: YYYY-MM-DD, to: YYYY-MM-DD } (range)
```

---

## Формат отображения vs v-model

- **Отображение в инпуте:** `ДД.ММ.ГГ` (двузначный год) — чтобы не раздувать инпут
  - single: `15.03.26`
  - range: `01.12.25 — 31.01.26`
- **v-model наружу:** `YYYY-MM-DD` — формат который используют API и все потребители
- Конвертация YYYY-MM-DD ↔ ДД.ММ.ГГ — только внутри компонента

---

## Внешний вид инпута

Визуально идентичен `.combo-box-wrap`, но стили копируются в `datePicker.css` с префиксом `.dp-` — **без импорта и зависимости от `selectBox.css`**.

```text
[ 15.03.26        ▼ ]  [ Сегодня ]      ← single
[ 01.12.25 — 31.01.26 ▼ ]  [ Сброс ]   ← range
```

- Стрелка вниз (chevron) справа — SVG иконка `mdiChevronDown` из `@mdi/js`
- Placeholder цвет: `--text-secondary`
- Кнопка **Сегодня** (single) / **Сброс** (range) — рядом с инпутом, стиль `btnGlass iconText`
- `showToday: false` — кнопка скрыта

### Кнопка "Сегодня" (single mode)

- Нажатие → устанавливает сегодняшнюю дату → закрывает попап → emit

### Кнопка "Сброс" (range mode)

- Нажатие → очищает `from` и `to` → emit с пустым значением

---

## Попап-календарь

Собственный HTML/CSS — **без flatpickr**. Открывается через `position: fixed`.

### z-index

`.dp-popup` → `z-index: 1000`

Позиция в системе слоёв проекта:

- `.modal-overlay-main` (2-й порядок): z-index 999
- **`.dp-popup`: z-index 1000** ← между порядками
- `.modal-overlay` (1-й порядок): z-index 9999 — AlertModal открывается поверх календаря ✅

### Позиционирование

- Попап открывается под инпутом через `getBoundingClientRect()`
- Если не помещается снизу (до края viewport < высота попапа) → открывается **над** инпутом
- Если выходит за правый край → прижимается к правому краю viewport с отступом 8px
- Масштабирование под ширину инпута через `transform: scale()` + `transformOrigin: top left`
  - Попап рендерится скрытым (`visibility: hidden`), затем через `requestAnimationFrame` считается ширина, применяется scale, затем `visibility: visible`
  - Чтобы scaled-попап не занимал unscaled layout-пространство: `position: fixed` + явные `top/left` через JS

### Структура попапа

```text
┌─────────────────────────────────┐  ← .dp-popup (border: 2px groove --glass-border)
│  ←   [Март ▼]   2026   →       │  ← .dp-header
├─────────────────────────────────┤
│  Пн   Вт   Ср   Чт   Пт  Сб  Вс│  ← .dp-weekdays (--text-secondary)
├─────────────────────────────────┤  ← разделитель (border-top)
│  23   24   25   26   27  28   1 │  ← дни предыдущего месяца (.dp-day--other)
│   2    3    4    5    6   7   8 │
│   9   10   11   12   13  14  15 │
│  16   17   18   19   20  21  22 │
│  23   24   25   26   27  28  29 │
│  30   31    1    2    3   4   5 │  ← дни следующего месяца (.dp-day--other)
└─────────────────────────────────┘
```

В range-режиме над сеткой — подпись:

- Нет выбора → **"Начало периода"**
- `from` выбран → **"Конец периода"**

### Правила дней

| Класс | Тип дня | Цвет | Можно выбрать |
|-------|---------|------|---------------|
| `.dp-day` | Текущий месяц | `--text-primary` | ✅ |
| `.dp-day--other` | Соседние месяцы | `--text-secondary`, opacity 0.5 | ❌ |
| `.dp-day--today` | Сегодня | `--text-primary` + border `--text-secondary` | ✅ |
| `.dp-day--selected` | Выбранный | фон `--glass-border`, font-weight 600 | — |
| `.dp-day--range` | Внутри диапазона | фон `--glass-border` opacity 0.5 | ✅ |
| `.dp-day--range-hover` | Hover preview диапазона | фон `--glass-border` opacity 0.3 | — |
| `.dp-day--disabled` | Недоступный (min/maxDate) | `--text-secondary`, opacity 0.4, cursor: not-allowed | ❌ |

### Выбранный день — цвет фона

Для выбранного дня используется отдельная переменная `--dp-selected-bg` чтобы обеспечить контраст в обеих темах:
- Dark: `rgba(255, 255, 255, 0.25)`
- Light: `rgba(68, 87, 197, 0.25)` (акцентный синий проекта)

Определяется в `datePicker.css` через `[data-theme]` и `[data-theme="light"]`.

---

## Range — hover preview

Когда `from` уже выбран и пользователь наводит курсор на другой день:
- Все дни между `from` и hovered-днём получают класс `.dp-day--range-hover`
- Визуально показывает предполагаемый диапазон до финального клика

---

## Навигация по месяцам

- Кнопки `←` / `→` — иконки `mdiChevronLeft` / `mdiChevronRight` через `svg-icon`
- Год — текст, без редактирования
Кастомный дропдаун месяца:

- Триггер `.dp-month-trigger`
- Дропдаун `.dp-dropdown` + `.dp-dropdown-option` — стили скопированы из `selectBox.css` в `datePicker.css`, **без зависимости от `selectBox.css`**
- z-index дропдауна: `100000` (должен выбираться выше самого попапа)

---

## Клавиатура

| Клавиша | Действие |
|---------|----------|
| `Enter` / `Space` | Открыть попап (на инпуте) / выбрать день (в попапе) |
| `Escape` | Закрыть попап |
| `←` `→` | Предыдущий / следующий день |
| `↑` `↓` | Неделя назад / вперёд |
| `Tab` | Закрыть попап, перейти к следующему элементу |

Фокус при открытии попапа — на текущем выбранном дне или на сегодня.

---

## Закрытие попапа

- Клик вне компонента → закрыть (слушатель на `document`, `capture: true`)
- `Escape` → закрыть
- Клик на инпут когда попап открыт → закрыть (toggle)
- В `single`: выбор даты → закрыть
- В `range`: выбор второй даты → закрыть
- `onUnmounted` → удалить слушатели

---

## CSS архитектура

Файл `src/components/ui/datePicker.css`:

- Только CSS переменные из `style.css`
- Никаких `!important`
- Никаких хардкодов цветов/размеров
- Классы с префиксом `.dp-`

```text
.dp-wrap             — flex-обёртка (инпут + кнопка)
.dp-input            — инпут (визуально как combo-box-wrap, стили скопированы)
.dp-input-arrow      — стрелка chevron внутри инпута
.dp-btn-today        — кнопка Сегодня/Сброс
.dp-popup            — попап (position: fixed, z-index: 1000)
.dp-header           — шапка навигации
.dp-month-trigger    — кастомный триггер дропдауна месяца
.dp-dropdown         — дропдаун месяца (визуально как combo-box-dropdown, стили скопированы)
.dp-dropdown-option  — опция дропдауна
.dp-dropdown-option--selected — выбранная опция
.dp-weekdays         — строка дней недели
.dp-grid             — сетка дней (display: grid, 7 колонок)
.dp-day              — ячейка дня
.dp-day--other       — день соседнего месяца
.dp-day--today       — сегодня
.dp-day--selected    — выбранный день
.dp-day--range       — день внутри диапазона
.dp-day--range-hover — hover preview диапазона
.dp-day--disabled    — недоступный день
.dp-range-label      — подпись "Начало/Конец периода" (range mode)
```

---

## Миграция

### BookingFormModal.vue

- Удалить: `import flatpickr`, `fpInstance`, весь watch на `isVisible` связанный с flatpickr, логику кастомного дропдауна месяца
- Заменить: `<input ref="dateInputRef">` → `<DatePicker v-model="shootingDate" :minDate="todayStr" />`
- `shootingDate` остаётся `string` формата `YYYY-MM-DD` — ничего не меняется

### BookingsTable.vue

- Удалить: два отдельных инпута дат + flatpickr инициализацию
- Заменить: `<DatePicker mode="range" v-model="dateRange" />` где `dateRange = { from: periodStart, to: periodEnd }` (оба `YYYY-MM-DD`)
- Адаптировать: `formatForApi()` работает с `dateRange.from` / `dateRange.to`

### Финальная очистка

1. Удалить `src/assets/flatpickr.css`
2. Удалить все `import 'flatpickr/dist/flatpickr.min.css'` из компонентов
3. Удалить все `import flatpickr from 'flatpickr'`
4. Удалить `import { Russian } from 'flatpickr/dist/l10n/ru.js'`
5. Удалить пакет: `npm uninstall flatpickr`

---

## Что НЕ входит в scope

- Выбор года через дропдаун (только текст)
- TimePicker — отдельный компонент если понадобится
- Множественный выбор дат
- Анимация открытия/закрытия попапа
- Поддержка touch-жестов (swipe по месяцам)
