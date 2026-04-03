# План: Кеш записей по месяцам + мгновенные шары

**Дата:** 3 апреля 2026
**Приоритет:** P2
**Статус:** На утверждение

---

## Проблема

1. При каждой смене месяца — новый запрос к API (медленно)
2. При переключении вид месяц → неделя → месяц — шары пропадают и появляются через 2-3 сек (новый запрос)
3. Барбер работает каждый день, за год 3000-4000 записей — грузить всё сразу нельзя

---

## Решение: кеш по месяцам в bookingsStore

### Этап 1 — bookingsStore: добавить кеш

```typescript
const cache = ref<Record<string, any[]>>({}) // ключ: '2026-02'

async function fetchBookings(start?: string, end?: string) {
  // Определяем ключ месяца
  const monthKey = start ? start.slice(0, 7) : currentMonth.value

  // Если есть в кеше — используем сразу, не запрашиваем
  if (cache.value[monthKey]) {
    bookings.value = cache.value[monthKey]
    return
  }

  // Иначе — запрашиваем и кешируем
  loading.value = true
  try {
    // ... fetch ...
    const data = await response.json()
    cache.value[monthKey] = data
    bookings.value = data
  } finally {
    loading.value = false
  }
}

// Инвалидация кеша при создании/обновлении/удалении записи
function invalidateCache(monthKey?: string) {
  if (monthKey) {
    delete cache.value[monthKey]
  } else {
    cache.value = {} // сбросить всё
  }
}
```

После `createBooking`, `updateBooking`, `deleteBooking`, всех `action=*` — вызывать `invalidateCache(monthKey)` чтобы следующий запрос этого месяца пошёл на сервер.

### Этап 2 — CalendarPanel: renderBalls()

Вынести логику вставки шаров в отдельную функцию `renderBalls()`:

```typescript
function renderBalls() {
  // Убрать старые
  document.querySelectorAll('.fc-daygrid-day-frame .status-ball').forEach(el => el.remove())
  // Вставить новые
  for (const [dateKey, ballType] of Object.entries(ballsByDate.value)) {
    const cell = document.querySelector(`[data-date="${dateKey}"] .fc-daygrid-day-frame`)
    if (!cell) continue
    const span = document.createElement('span')
    span.className = 'status-ball' + (ballType === 'red' ? ' status-ball--red' : '')
    cell.appendChild(span)
  }
}
```

Вызывать `renderBalls()`:
- В `watch(ballsByDate)` — когда данные загрузились
- В `datesSet` (после смены вида/месяца) — `await nextTick()` → `renderBalls()`

Так при возврате на месяц шары появятся мгновенно из кеша.

---

## Затронутые файлы

| Файл | Изменение |
|------|-----------|
| `src/stores/bookings.ts` | Добавить `cache`, `invalidateCache()`, проверку кеша в `fetchBookings` |
| `src/components/calendar/CalendarPanel.vue` | Вынести `renderBalls()`, вызывать в `datesSet` |

---

*План создан: 3 апреля 2026*
