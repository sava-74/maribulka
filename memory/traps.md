# Traps - Известные ловушки и баги

## JavaScript / TypeScript

### 1. toISOString() сдвигает дату

**Проблема:**
```typescript
const date = new Date('2026-02-15')
date.toISOString() // "2026-02-14T21:00:00.000Z" (сдвиг на день из-за UTC!)
```

**Решение:**
```typescript
function formatDate(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}
```

---

## FullCalendar

### 2. slotMaxTime - exclusive boundary

**Проблема:**
```typescript
slotMaxTime: '22:00:00'  // Последний слот НЕ отобразится!
```

**Решение:**
```typescript
slotMaxTime: '23:00:00'  // Для последнего слота 22:00 ставим 23:00:00
```

### 3. eventContent callback возвращает undefined

**Проблема:**
```typescript
eventContent: (arg) => {
  // Если не вернуть значение - eventContent сломается
}
```

**Решение:**
```typescript
eventContent: (arg) => {
  if (customCondition) {
    return { html: '<div>Custom</div>' }
  }
  return true  // Стандартный рендер
}
```

### 4. getDate() не реактивен

**Проблема:**
```typescript
const currentDate = computed(() => calendarRef.value.getApi().getDate())
// НЕ обновляется при изменении даты в календаре!
```

**Решение:**
```typescript
const currentDate = ref(new Date())

function handleDateChange(date: Date) {
  currentDate.value = date
}
```

---

## PHP / MySQL

### 5. Сравнение DATETIME с DATE

**Проблема:**
```sql
-- shooting_date = '2026-02-15 14:30:00'
-- today = '2026-02-15'

WHERE shooting_date <= '2026-02-15'
-- Вернёт FALSE! (потому что 14:30:00 > 00:00:00)
```

**Решение:**
```sql
WHERE DATE(shooting_date) <= '2026-02-15'
-- Вернёт TRUE (сравниваем только даты)
```

### 6. Логика проведения заказа

**До исправления (баг):**
```php
// Одно действие "provести" проверяло ОБА условия
if (shooting_date > today OR status != 'completed') {
  // Ошибка: сравнение DATETIME > DATE всегда true!
}
```

**После исправления (09.02.2026):**
```php
// Разделили на два action:

// 1. complete - проверяет дату
if (DATE(shooting_date) <= today) {
  status = 'completed'
}

// 2. deliver - проверяет статус
if (status == 'completed') {
  status = 'delivered'
  processed_at = NOW()
}
```

**Файл:** `api/bookings.php` (lines 375-410)

---

## CSS

### 7. position: fixed с жёсткими left/right

**Проблема:**
```css
.sticky-element {
  position: fixed;
  top: 86px;
  left: 250px;  /* Ломается на мобилке! */
  right: 20px;
}
```

**Решение:**
```css
.sticky-element {
  position: sticky;  /* Автоматически адаптируется */
  top: 86px;
  /* НЕТ left/right! */
}
```

### 8. Подбор top для sticky элементов

**Проблема:**
```css
.sticky-element {
  position: sticky;
  top: 86px;  /* Может не подойти из-за margin/padding родителя */
}
```

**Решение:**
- `position: sticky` работает относительно **родительского контейнера**, НЕ viewport!
- Подбирать `top` и `margin-top` экспериментально для каждого случая
- Пример: `.promotion-banner` - `top: 87px; margin-top: -8px;` (десктоп), `top: 69px` (мобилка)

### 9. Hover эффекты на touch устройствах

**Проблема:**
```css
.glass-button:hover {
  transform: scale(1.05);  /* "Прилипает" на мобилке после тапа */
}
```

**Решение:**
```css
@media (max-width: 480px) {
  .glass-button:hover {
    transform: none;  /* Отключаем hover на touch */
  }
}
```

---

## Мобильная адаптация

### 10. Модалки обрезаются на мобилке

**Проблема:**
```css
.modal-overlay {
  display: flex;          /* Контент не прокручивается! */
  align-items: center;
  justify-content: center;
}
```

**Решение:**
```css
@media (max-width: 480px) {
  .modal-overlay {
    display: block;       /* Включаем прокрутку */
    overflow-y: auto;
  }
}
```

**Файл:** `modal.css` (строки 550-588)

### 11. Фиксированная ширина модалки на мобилке

**Проблема:**
```css
@media (max-width: 480px) {
  .modal-large {
    width: 100vw;  /* Нет горизонтального скролла! */
  }
}
```

**Решение:**
```css
@media (max-width: 480px) {
  .modal-large {
    width: 600px;      /* Фиксированная ширина */
    min-width: 600px;  /* Важно! */
  }

  .modal-overlay {
    overflow-x: auto;  /* Горизонтальный скролл когда экран < 600px */
  }
}
```

**Файл:** `modal.css` (строки 633-660)

---

## Vue / Pinia

### 12. v-html без санитизации

**Проблема:**
```vue
<div v-html="userContent"></div>
<!-- XSS уязвимость! -->
```

**Текущее состояние:**
- `Home.vue` использует `v-html` для описания студии
- **НЕТ** санитизации!
- Защита: только админ может редактировать (проверка на бэкенде)

**TODO:**
- Добавить DOMPurify для очистки HTML

### 13. Quill создаёт относительные ссылки

**Проблема:**
```html
<!-- Quill создаёт: -->
<a href="mail.ru">mail.ru</a>

<!-- URL становится: -->
https://марибулька.рф/mail.ru  <!-- Неправильно! -->
```

**Решение (при сохранении):**
```typescript
fixedContent = fixedContent.replace(
  /<a\s+([^>]*?)href="([^"]+)"([^>]*)>/gi,
  (match, before, href, after) => {
    if (!/^(https?:\/\/|mailto:)/i.test(href)) {
      return `<a ${before}href="https://${href}"${after}>`
    }
    return match
  }
)
```

**Файл:** `EditStudioDescriptionModal.vue` (строки 122-142)

---

## Деплой

### 14. Медиа-файлы пересоздаются при деплое

**Проблема:**
```
dist/media/home/photo.jpg  <!-- Удаляется при npm run build! -->
```

**Решение:**
- Медиа хранятся **вне dist/** в `/home/s/sava7424/maribulka.rf/media/`
- В `dist/` создаётся симлинк `media -> ../../media`
- Симлинк автоматически создаётся при деплое (deploy.ps1)

### 15. Windows-путь к SSH ключу не работает в Git Bash

**Проблема:**
```bash
ssh -i C:\Users\sava\.ssh\beget_maribulka  # НЕ работает!
```

**Решение:**
```bash
ssh -i ~/.ssh/beget_maribulka  # Unix-стиль ВСЕГДА!
```

---

## Календарь записей

### 16. Мобильные точки все красные

**Проблема (до 14.02.2026):**
```typescript
// В режиме ДНЯ все записи становились красными
// если хоть одна запись была красной
```

**Решение:**
```typescript
// Приоритет красного ТОЛЬКО в режиме месяца на мобилке
if (windowWidth.value <= 768 && !isDayView.value) {
  // Логика приоритета красного
}
```

**Файл:** `BookingsFullCalendar.vue` (строки 153-159)

### 17. Поле оплаты по умолчанию = итоговая сумма

**Проблема (до 14.02.2026):**
```typescript
watch(() => totalAmount.value, (newTotal) => {
  paymentAmount.value = newTotal  // Автоматически заполняло!
})
```

**Решение:**
```typescript
// Удалили watch, теперь paymentAmount по умолчанию = 0
const paymentAmount = ref(0)
```

**Файл:** `AddBookingModal.vue` (строка 25, удалены строки 115-118)

---

## GitHub Actions

### 18. Проверка сайта редиректит 301 → 200

**Проблема:**
```bash
curl http://марибулька.рф/
# 301 Moved Permanently (редирект на HTTPS)
# CI считает это ошибкой!
```

**Решение:**
```bash
curl -L https://марибулька.рф/  # Следовать редиректам
# 200 OK
```

**Файл:** `.github/workflows/deploy.yml` (исправлено 13.02.2026)
