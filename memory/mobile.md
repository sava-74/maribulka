# Mobile - Мобильная адаптация

## 🚨 ЕДИНЫЙ БРЕЙКПОИНТ!

**Всё что ≤768px - это мобилка.**

**НЕТ брейкпоинта 480px!**

---

## Календарь

### Тулбар в две строки

**Десктоп:**
```
[Назад] [Дата] [Вперёд]    [Месяц] [День]
```

**Мобилка:**
```
[Дата]
[Назад] [Вперёд] [Месяц] [День]
```

**CSS:**
```css
@media (max-width: 768px) {
  .fc-toolbar {
    flex-wrap: wrap;
  }

  .fc-toolbar-chunk:nth-child(2) {
    width: 100%;
    order: -1; /* Дата на первую строку */
  }
}
```

### Цвета точек (режим месяца)

**Файл:** `BookingsFullCalendar.vue` (строки 153-159)

**Логика:**
- Если хотя бы одна запись красная (failed/cancelled) → **все точки красные**
- Иначе → точки окрашены по статусу

**Условие применения:**
```typescript
if (windowWidth.value <= 768 && !isDayView.value) {
  // Приоритет красного ТОЛЬКО в режиме месяца на мобилке
}
```

---

## Sidebar (боковое меню)

**Десктоп:**
- Всегда видно
- Ширина: fixed

**Мобилка:**
- Collapsed по умолчанию
- Открывается overlay по гамбургеру
- Z-index: выше контента

---

## TopBar (верхняя панель)

**Десктоп:**
- Высота: 50px

**Мобилка:**
- Высота: 46px (компактнее)
- Margin reduced

---

## Content (основной контент)

**Десктоп:**
- `margin-left`: от ширины sidebar

**Мобилка:**
- `margin-left: 5px`
- `margin-top`: под TopBar (с учётом его высоты)

---

## Модальные окна

### Большие модалки (Add/Edit/ViewBookingModal)

**Файл:** `modal.css` (строки 550-588)

**Проблема (до 14.02.2026):**
- Обрезались сверху/снизу на мобильных устройствах

**Решение:**
```css
@media (max-width: 768px) {
  .modal-overlay {
    display: block;  /* НЕ flex! */
    overflow-y: auto;
  }

  .modal-glass {
    margin: 20px 15px;
    max-width: calc(100vw - 30px);
  }
}
```

**Результат:**
- Возможность прокрутки всего содержимого
- Модалка не обрезается

### Маленькие модалки (Alert/Confirm/LoginModal)

**Проблема (до 14.02.2026):**
- Были прижаты к верху экрана

**Решение:**
```css
@media (max-width: 768px) {
  .modal-small {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
  }
}
```

**Результат:**
- Всегда по центру экрана независимо от прокрутки

### Редактор описания (modal-large)

**Файл:** `modal.css` (строки 633-660)

**Проблема:**
- На узких экранах (<600px) нужен горизонтальный скролл

**Решение:**
```css
@media (max-width: 768px) {
  /* Overlay - включаем скролл */
  .modal-overlay:has(.modal-glass.modal-large) {
    display: block;  /* НЕ flex! */
    overflow-x: auto;
    overflow-y: auto;
    padding: 5px;
  }

  /* Модалка - фиксированная ширина */
  .modal-glass.modal-large {
    margin: 0;
    width: 600px;
    min-width: 600px;
    height: calc(100vh - 10px);
    overflow-y: auto;
    padding: 15px;
  }
}
```

**Ключевые моменты:**
- Overlay: `display: block` + `overflow-x: auto`
- Модалка: фиксированная ширина 600px (НЕ auto, НЕ 100vw!)
- Горизонтальный скролл автоматически когда экран <600px

---

## Таблицы

**Файл:** `tables.css`

### Размер шрифта
```css
.table-containerTab {
  font-size: 12px;  /* Было 14px */
}
```

### Обрезка длинного текста

**Мобилка (≤768px):**
```css
@media (max-width: 768px) {
  /* ClientsTable - 4-я колонка (Примечание) */
  .clients-table td:nth-child(4) {
    max-width: 100px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* ShootingTypesTable - 5-я колонка (Описание) */
  .shooting-types-table td:nth-child(5) {
    max-width: 100px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}
```

---

## Hover эффекты

**Отключены на touch устройствах:**
```css
@media (max-width: 768px) {
  .glass-button:hover {
    transform: none;  /* Отключаем hover эффект */
  }
}
```

---

## Sticky элементы

### Баннер акции (home.css)

**Десктоп:**
```css
.promotion-banner {
  position: sticky;
  top: 87px;
  margin-top: -8px;
}
```

**Мобилка:**
```css
@media (max-width: 768px) {
  .promotion-banner {
    top: 69px;  /* Меньше из-за компактного TopBar */
  }
}
```

### Вкладки Accounting (layout.css)

**Десктоп:**
```css
.accounting-nav {
  position: sticky;
  top: 86px;
  margin-top: 7px;
}
```

**Мобилка:** (подбирается экспериментально для каждого случая)

---

## Текст auto-shrink

**Баннер акции:**
```css
.promotion-banner {
  font-size: clamp(16px, 4vw, 32px);  /* Автоматическое уменьшение */
}
```

---

## Проверка ширины экрана в JS

```typescript
import { ref, onMounted, onUnmounted } from 'vue'

const windowWidth = ref(window.innerWidth)

function handleResize() {
  windowWidth.value = window.innerWidth
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
```

**Использование:**
```typescript
if (windowWidth.value <= 768) {
  // Мобильная логика
}
```
