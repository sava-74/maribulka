# Styles - Организация стилей

## 🔥 СТРОГОЕ ПРАВИЛО (ЗАКОН ПРОЕКТА!)

### НИКАКИХ `<style>` блоков в .vue файлах!

**Только:** `<template>` + `<script>`

---

## Структура CSS файлов

### Один тип стилей = один файл

```
assets/
├── theme.css          # CSS переменные
├── buttons.css        # ВСЕ кнопки
├── calendar.css       # Календарь FullCalendar
├── tables.css         # Таблицы
├── modal.css          # Модальные окна
├── layout.css         # Layout, вкладки, filters, accounting
├── sidebar.css        # Боковое меню
├── topbar.css         # Верхняя панель
├── content.css        # Основной контент
└── home.css           # Домашняя страница
```

---

## Правила организации

1. **НЕТ дубликатов** - один стиль в одном месте
2. **НЕТ inline стилей** в template (только классы)
3. **Импорт CSS** только в главных компонентах
4. При изменении стилей - **ВСЕГДА** в соответствующий .css файл

---

## CSS переменные (theme.css)

### Цвета и общие настройки

```css
--generalColor: #39FF14;              /* Ярко-зелёный неон (основной акцент) */
--neon-blue: #00F3FF;                 /* Дополнительный неоновый цвет */
--generalTextColor: #333;             /* Основной цвет текста */
--glass-bg: rgba(255, 255, 255, 0.4); /* Фон панелей (эффект стекла) */
--glass-bgFilter: blur(12px);         /* Размытие для эффекта стекла */
--panelRadius: 12px;                  /* Радиус скругления панелей */
--glass-shadowPanel: 10px 10px 10px rgba(0, 0, 0, 0.2); /* Тень панелей */
```

### Кнопки

```css
--glass-bgButton: rgba(255, 255, 255, 0.4);       /* Фон кнопок */
--glass-bgButtonActive: rgba(57, 255, 20, 0.3);   /* Фон активной кнопки */
--glass-widthButton: 40px;                        /* Ширина квадратной кнопки */
--glass-heightButton: 40px;                       /* Высота квадратной кнопки */
--glass-widthButtonText: 140px;                   /* Ширина кнопки с текстом */
--glass-borderRadiusButton: 8px;                  /* Скругление кнопок */
--svgColorForButton: #333;                          /* Цвет иконок в кнопках */
```

### Таблицы и статусы

```css
--generalTabBorderColor: #000;                    /* Цвет границ таблицы */
--tableHeaderBg: #f0f0f0;                         /* Фон заголовков */
--tableSelectedBg: rgba(74, 222, 128, 0.2);       /* Фон выбранной строки */
--incomeColor: #4ade80;                           /* Доход (зелёный) */
--expenseColor: #f87171;                          /* Расход (красный) */
--statusCancelledColor: #9ca3af;                  /* Отменённые (серый) */
--warningColor: #fbbf24;                          /* Предупреждения (жёлтый) */
--infoColor: #3b82f6;                             /* Информация (синий) */
--tableTextColor: #000;                           /* Основной текст в таблицах */
--textMutedLightColor: rgba(255, 255, 255, 0.7);  /* Приглушённый светлый текст */
--text-colorAlert: #ff0000;                       /* Текст предупреждений */
```

### Календарь

```css
--calendar-bg: rgba(252, 250, 250, 0.8);          /* Фон сетки календаря */
--calendar-colorDayGrid: rgba(57, 255, 20, 0.1);  /* Подсветка ячеек дней */
--calendar-colorToday: rgba(28, 20, 255, 0.15);   /* Выделение текущего дня */
```

---

## Sticky элементы

### 🚨 КРИТИЧНО: НЕ использовать position: fixed!

**ВСЕГДА использовать `position: sticky`**

### Эталон: `.accounting-nav` в layout.css

```css
.accounting-nav {
  position: sticky;
  top: 86px;  /* Отступ от топ-бара */
  z-index: 100;
  margin-top: 7px;
  padding: 16px;
  background: var(--glass-bg);
  border: 1px solid var(--generalColor);
  border-radius: var(--panelRadius);
  box-shadow: var(--glass-shadowPanel);
  -webkit-backdrop-filter: var(--glass-bgFilter);
  backdrop-filter: var(--glass-bgFilter);
}
```

### Почему sticky, а не fixed?

| `position: sticky` | `position: fixed` |
|-------------------|-------------------|
| ✅ Автоматически адаптируется под размер экрана | ❌ Требует media queries |
| ✅ Работает без жёстких left/right | ❌ Ломается на мобилке |
| ✅ Относительно родительского контейнера | ❌ Относительно viewport |

### Подбор значения `top`

**ВАЖНО:** `position: sticky` работает относительно **родительского контейнера**, НЕ viewport!

- Если контейнер имеет `margin-top` или `padding-top` - они влияют на позиционирование
- Подбирать `top` и `margin-top` нужно **экспериментально** для каждого случая
- Пример: `.promotion-banner` в home.css
  - Десктоп: `top: 87px; margin-top: -8px;`
  - Мобилка: `top: 69px;`

---

## Классы кнопок

### glass-button (40x40, только иконка)

```css
.glass-button {
  width: var(--glass-widthButton);
  height: var(--glass-heightButton);
  background: var(--glass-bgButton);
  border: 1px solid var(--generalColor);
  border-radius: var(--glass-borderRadiusButton);
  cursor: pointer;
  /* ... */
}
```

**Использование:**
```vue
<button class="glass-button">
  <svg><!-- иконка --></svg>
</button>
```

### glass-button-text (150x40, иконка + текст)

```css
.glass-button-text {
  width: var(--glass-widthButtonText);
  height: var(--glass-heightButton);
  /* ... */
}
```

**Использование:**
```vue
<button class="glass-button-text">
  <svg><!-- иконка --></svg>
  <span>Текст</span>
</button>
```

---

## Классы модалок

### modal-overlay (фон)

```css
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
```

### modal-glass (контейнер модалки)

```css
.modal-glass {
  background: var(--glass-bg);
  border: 1px solid var(--generalColor);
  border-radius: var(--panelRadius);
  box-shadow: var(--glass-shadowPanel);
  -webkit-backdrop-filter: var(--glass-bgFilter);
  backdrop-filter: var(--glass-bgFilter);
  padding: 20px;
  /* ... */
}
```

### modal-small (маленькие модалки)

Для AlertModal, ConfirmModal, LoginModal.

```css
.modal-small {
  /* Центрирование по центру экрана (на мобилке) */
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
```

### modal-large (большие модалки)

Для редактора описания студии.

```css
.modal-large {
  width: 800px;
  max-width: 90vw;
}

@media (max-width: 480px) {
  .modal-large {
    width: 600px;
    min-width: 600px;
    height: calc(100vh - 10px);
    /* Горизонтальный скролл когда экран < 600px */
  }
}
```

---

## Импорт CSS в компонентах

**ТОЛЬКО в главных компонентах:**

```vue
<!-- App.vue -->
<script setup lang="ts">
import './assets/theme.css'
import './assets/buttons.css'
import './assets/modal.css'
import './assets/layout.css'
// ...
</script>
```

**НЕ импортировать в каждом компоненте!**
