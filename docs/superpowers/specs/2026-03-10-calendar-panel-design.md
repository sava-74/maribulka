# Calendar Panel — Design Spec
**Дата:** 2026-03-10
**Статус:** Approved

---

## 1. Концепция

Новый модуль Календарь — самостоятельная рабочая панель на рабочем столе (`.worck-table`), запускаемая из LaunchPad. Переписывается с нуля на FullCalendar 6.x, с нашей стеклянной стилистикой.

---

## 2. Запуск из LaunchPad

- Кнопка "Календарь" в LaunchPad → `navStore.navigateTo('calendar')`
- LaunchPad **закрывается** при переходе (вызов `emit('close')` перед навигацией)
- В `App.vue` добавляется: `<CalendarPanel v-if="navStore.currentPage === 'calendar'" />`
- Тип `PageType` в `navigation.ts` расширяется: добавляется `'calendar'`

---

## 3. Структура файлов

```
src/components/calendar/
├── CalendarPanel.vue          # главная панель — рабочий стол
├── CalendarSidebar.vue        # сайдбар с записями выбранного дня
├── AddBookingModal.vue        # перенос из accounting/
├── EditBookingModal.vue       # перенос из accounting/
├── ViewBookingModal.vue       # перенос из accounting/
├── DeleteBookingModal.vue     # перенос из accounting/
├── CancelBookingModal.vue     # перенос из accounting/
├── BookingActionsModal.vue    # перенос из accounting/
├── AddPaymentModal.vue        # перенос из accounting/
├── ConfirmSessionModal.vue    # перенос из accounting/
├── DeliverBookingModal.vue    # перенос из accounting/
└── calendar.css               # стили модуля (импорт в main.ts)

Удаляются:
src/components/accounting/BookingsFullCalendar.vue
src/components/accounting/BookingsCalendar.vue
```

---

## 4. Компонент CalendarPanel.vue

### Структура HTML
```html
<div class="padGlass padGlass-work calendar-panel">
  <!-- FullCalendar -->
  <FullCalendar ref="calendarRef" :options="calendarOptions" />

  <!-- Сайдбар — скрыт в режиме дня -->
  <CalendarSidebar
    v-if="!isDayView"
    :date="selectedDate"
    :bookings="bookingsForSelectedDate"
    @add="openAddModal"
    @select="openActionsModal"
  />
</div>
```

### Логика
- `selectedDate: ref<string>` — выбранная дата (клик по ячейке месяца/недели)
- `isDayView: ref<boolean>` — true когда `view.type === 'timeGridDay'`
- Обновляется через FullCalendar callback `datesSet` и `dateClick`

### FullCalendar опции
```typescript
{
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: 'ru',
  firstDay: 1,
  headerToolbar: {
    left: 'prev,next title',
    right: 'multiMonthYear,dayGridMonth,timeGridWeek,timeGridDay'
  },
  views: {
    multiMonthYear: { type: 'multiMonth', duration: { years: 1 } }
  },
  slotMinTime: '09:00:00',
  slotMaxTime: '23:00:00',
  allDaySlot: false,
  height: 'auto',
  dateClick: handleDateClick,
  eventClick: handleEventClick,
  datesSet: handleDatesSet,
}
```

### Callbacks
- `handleDatesSet(info)` → обновляет `isDayView`, `selectedDate`
- `handleDateClick(info)` → обновляет `selectedDate`; в режиме месяца/недели НЕ переключает вид
- `handleEventClick(info)` → открывает `BookingActionsModal`

### Кнопка "+" (добавить запись)
- Видна только в режиме `timeGridDay`
- Реализуется через `customButtons` в FullCalendar опциях
- Открывает `AddBookingModal` с предзаполненной датой

---

## 5. Компонент CalendarSidebar.vue

### Props
```typescript
date: string        // 'YYYY-MM-DD'
bookings: Booking[] // записи на этот день
```

### Emits
```typescript
add: []             // нажата кнопка "+ добавить"
select: [Booking]   // нажата запись
```

### Структура HTML
```html
<div class="calendar-sidebar">
  <div class="calendar-sidebar__header">{{ formattedDate }}</div>
  <div class="calendar-sidebar__list">
    <div v-for="b in bookings" class="calendar-sidebar__item" @click="$emit('select', b)">
      <span class="calendar-sidebar__time">{{ time(b) }}</span>
      <span class="calendar-sidebar__name">{{ b.client_name }}</span>
      <span class="calendar-sidebar__status">{{ statusLabel(b) }}</span>
    </div>
    <div v-if="!bookings.length" class="calendar-sidebar__empty">Нет записей</div>
  </div>
  <div class="ButtonFooter PosCenter">
    <button class="btnGlass iconText" @click="$emit('add')">
      <span class="inner-glow"></span>
      <span class="top-shine"></span>
      <svg-icon type="mdi" :path="mdiPlus" class="btn-icon" />
      <span>Добавить</span>
    </button>
  </div>
</div>
```

---

## 6. Стили (calendar.css)

Импортируется в `main.ts`. Использует только CSS переменные из `style.css`.

### Ключевые блоки
```css
/* Панель — обёртка для FC + сайдбара */
.calendar-panel {
  display: flex;
  gap: 0;
  overflow: hidden;
}

/* FullCalendar занимает оставшееся пространство */
.calendar-panel .fc {
  flex: 1;
  min-width: 0;
}

/* Сайдбар */
.calendar-sidebar {
  width: 200px;
  flex-shrink: 0;
  border-left: 1px solid var(--glass-border);
  display: flex;
  flex-direction: column;
}

/* Переопределение стилей FullCalendar под наш glass */
.fc .fc-toolbar-title { color: var(--text-primary); }
.fc .fc-button { /* стилизация под btnGlass */ }
.fc .fc-daygrid-day { background: transparent; }
.fc .fc-event { border-radius: 5px; }
/* и т.д. */
```

---

## 7. Цвета событий (без изменений)

| Статус | Цвет |
|--------|------|
| new | #4682B4 (steel blue) |
| completed | #FFA500 (orange) |
| delivered | #2E8B57 (sea green) |
| cancelled* | скрыты или приглушены |

---

## 8. Модалки

Все модалки переносятся из `accounting/` в `calendar/` **без изменения логики**. Только:
- Обновляются импорты
- Приводятся к актуальным стилям (`.btnGlass`, `.padGlass.modal-sm`, `.modal-glassTitle`)

---

## 9. Интеграция в App.vue

```html
<!-- App.vue -->
<div class="worck-table">
  <LaunchPad ... />
  <Home v-if="navStore.currentPage === 'home'" />
  <CalendarPanel v-if="navStore.currentPage === 'calendar'" />
</div>
```

---

## 10. Изменения в navigation.ts

```typescript
export type PageType = 'home' | 'portfolio' | 'accounting' | 'settings' | 'references' | 'sandbox' | 'calendar'
```

---

## 11. Изменения в LaunchPad.vue

Кнопка "Календарь":
```typescript
function openCalendar() {
  navStore.navigateTo('calendar')
  emit('close') // закрываем лаунчпад
}
```

---

## 12. Что НЕ меняем

- Бизнес-логика модалок — без изменений
- `bookings.ts` store — без изменений
- `Accounting.vue` — пока без изменений (там остаётся таблица `BookingsCalendar`)
- Стили модалок уже соответствуют новой системе

---

## 13. Порядок реализации

1. Создать папку `src/components/calendar/`
2. Создать `calendar.css` (пустой, добавить импорт в `main.ts`)
3. Создать `CalendarPanel.vue` с базовым FullCalendar
4. Создать `CalendarSidebar.vue`
5. Расширить `PageType` в `navigation.ts`
6. Обновить `App.vue` — добавить `<CalendarPanel>`
7. Обновить `LaunchPad.vue` — кнопка закрывает лаунч и открывает календарь
8. Перенести модалки из `accounting/` в `calendar/`
9. Подключить модалки к `CalendarPanel.vue`
10. Стилизовать FullCalendar через `calendar.css`
11. Удалить `BookingsFullCalendar.vue` из `accounting/`
