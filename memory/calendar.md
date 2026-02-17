# Calendar - Календарь записей

## Компонент

**Файл:** `BookingsFullCalendar.vue`

**Режимы:**
- **Месяц** (`dayGridMonth`) - сетка с днями, точки для записей
- **День** (`timeGridDay`) - таймлайн с записями и временем

---

## Статусы записей

### Enum в БД
```sql
status ENUM('new', 'completed', 'delivered', 'cancelled',
            'cancelled_client', 'cancelled_photographer', 'failed')
```

### Цвета в календаре (BookingsFullCalendar.vue)

| Статус | Цвет | Текст |
|--------|------|-------|
| new | #4682B4 (steel blue) | белый |
| completed | #FFA500 (orange) | чёрный |
| delivered | #2E8B57 (sea green) | белый |
| cancelled* | скрыты | - |
| failed | скрыты | - |

\* cancelled, cancelled_client, cancelled_photographer

### Цвета в таблице (BookingsCalendar.vue)

| Статус | Иконка | Название |
|--------|--------|----------|
| new | 🔵 | Новая |
| completed | 🟠 | Состоялась |
| delivered | 🟢 | Выдано |
| failed | 🔴 | Не состоялась |
| cancelled | 🔴 | Отменена |
| cancelled_client | ⚪ | Отмена-К |
| cancelled_photographer | ⚪ | Отмена-Ф |

---

## Телефон в календаре (инверсные цвета)

**Цвета:**
- На голубом/зелёном фоне → **циан (#00FFFF)**
- На оранжевом фоне → **темно-синий (#1e40af)**

**Стили:**
```css
font-size: calc(1em + 2px);
font-weight: 600;
```

**В таблице/модалках:**
- Цвет: **темно-синий (#1e40af)**
- Кликабельная ссылка: `<a href="tel:...">`

---

## Финансовая информация (режим дня)

**Файл:** `BookingsFullCalendar.vue` (строки 198-211)

**Вторая строка записи:**

| Статус оплаты | Отображение |
|---------------|-------------|
| unpaid | `, долг {сумма} ₽` |
| partially_paid | `, оплачено {сумма} ₽, долг {остаток} ₽` |
| fully_paid | ничего не добавляем |

---

## Алгоритм свободных слотов

### Логика

1. **Занятый блок:** каждая запись занимает `[startMin, startMin + duration_minutes + 30]`
   - `+30` = обязательный перерыв между записями

2. **Свободный слот:** кандидатный блок `[slot, slot + newDuration + 30]` **НЕ** пересекается ни с одним занятым блоком

3. **Шаг поиска:** 30 минут (09:00, 09:30, 10:00, ...)

4. **Рабочие часы:** 09:00 - 22:00

### Особенности

#### AddBookingModal
- Время выбирается **ТОЛЬКО** после выбора типа съёмки
- `<select>` disabled до выбора типа
- На сегодня: слоты раньше текущего времени отфильтрованы
- Нельзя создать запись в прошлом (min на дате)

#### EditBookingModal
- **Текущая запись исключена** из занятых блоков
- Можно выбрать то же время, что и было

#### Защита от записи в прошлом
- Поле даты: `min` = сегодня
- Кнопка "+" скрыта для прошедших дат

---

## Мобильный режим (месяц)

### Цвета точек

**Файл:** `BookingsFullCalendar.vue` (строки 153-159)

**Логика:**
- Если хотя бы одна запись в день красная (failed/cancelled) → **все точки красные**
- Иначе → точки окрашены по статусу каждой записи

**Приоритет красного применяется ТОЛЬКО к режиму месяца на мобилке:**
```typescript
if (windowWidth.value <= 768 && !isDayView.value) {
  // Приоритет красного
}
```

**В режиме дня (и на десктопе, и на мобилке):**
- Каждая запись окрашена по своему статусу

---

## Тулбар календаря

### Десктоп (>768px)
```
[Назад] [Дата] [Вперёд]    [Месяц] [День]
```

### Мобилка (≤768px)
```
[Дата]
[Назад] [Вперёд] [Месяц] [День]
```

**Реализация:**
```css
.fc-toolbar {
  flex-wrap: wrap;
}

.fc-toolbar-chunk:nth-child(2) {
  width: 100%;
  order: -1; /* Дата на первую строку */
}
```

---

## FullCalendar конфигурация

### Плагины
```typescript
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
```

### Основные параметры
```typescript
{
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: 'ru',
  firstDay: 1,  // Понедельник первый
  slotMinTime: '09:00:00',
  slotMaxTime: '23:00:00',  // Exclusive boundary! Для 22:00 ставим 23:00:00
  allDaySlot: false,
  height: 'auto',
  eventClick: handleEventClick
}
```

---

## Известные ловушки

### 1. slotMaxTime - exclusive boundary
Для слота 22:00 ставить `23:00:00`, иначе слот не отобразится.

### 2. eventContent callback
Вернуть `true` для стандартного рендера, `{ html }` для кастомного.
**НЕ** возвращать `undefined`!

### 3. getDate() не реактивен
`calendarRef.getApi().getDate()` НЕ реактивен.
Хранить дату в отдельном `ref` и обновлять вручную.

### 4. toISOString() сдвигает дату
`toISOString()` конвертирует в UTC → сдвигает дату на день.
Использовать локальное форматирование:
```typescript
const formatted = `${year}-${month}-${day}`
```

---

## Логика проведения заказа

### Две кнопки (ViewBookingModal)

#### "Состоялась" (Completed)
- **Проверка:** `DATE(shooting_date) <= today`
- **Действие:** status = 'completed'
- **API:** `POST /api/bookings.php?action=complete`

#### "Провести" (Deliver)
- **Проверка:** `status = 'completed'`
- **Действие:** status = 'delivered', processed_at = NOW()
- **API:** `POST /api/bookings.php?action=deliver`

### Баг (исправлен 09.02.2026)

**Проблема:** Сравнение `DATETIME > DATE` всегда true из-за времени.

**Решение:** Разделили логику на два action, используем `DATE()` функцию в SQL.

**Файл:** `api/bookings.php` (lines 375-410)
