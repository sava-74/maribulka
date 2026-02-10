# Проект Maribulka - Система учёта фотосъёмок

## Технологический стек
- **Frontend:** Vue 3 + TypeScript + Vite (maribulka-vue/)
- **Backend:** PHP API (api/)
- **База данных:** MySQL
- **Календарь:** @fullcalendar/vue3 с плагинами dayGrid, timeGrid, interaction
- **Иконки:** @mdi/light-js (Material Design Icons Light)
- **Стили:** Кастомные CSS компоненты (buttons.css, calendar.css, modal.css, responsive.css, layout.css)

## Основные компоненты
- `BookingsFullCalendar.vue` - Интерактивный календарь с отображением/редактированием бронирований
- `AddBookingModal.vue` - Модальное окно для создания нового бронирования
- `EditBookingModal.vue` - Модальное окно для редактирования бронирования
- `BookingActionsModal.vue` - Модальное окно с действиями над бронированием (завершить, доставить, отменить)
- `Accounting.vue` - Учётная панель с вкладками "Бухгалтерия"
- `stores/bookings.ts` - Pinia store для управления бронированиями
- `stores/references.ts` - Pinia store для справочников (типы съёмок, промоакции, клиенты)

## Бизнес-логика бронирований
- Продолжительность съёмки рассчитывается как: `duration_minutes + 30 минут`
- Временные слоты для выбора: `[startMin, startMin + duration + 30]`
- При изменении продолжительности: `[slot, slot + newDuration + 30]` с проверкой доступности
- В селектах отключены недоступные опции (disabled)
- В EditBookingModal отображаются только доступные временные слоты
- Валидация: типы съёмок должны соответствовать выбранным промоакциям
- Статусы бронирований обновляются через модальные окна с подтверждением

## Структура таблицы bookings
```
id, booking_date, shooting_date, processing_days, delivery_date, client_id, phone,
shooting_type_id, quantity, promotion_id, base_price, discount, final_price, total_amount,
paid_amount, payment_status (enum: unpaid/partially_paid/fully_paid),
status (enum: new/completed/delivered/cancelled), cancel_reason, created_at, updated_at
```
**Дополнительное поле:** `processed_at` DATETIME - дата/время обработки заказа

## UI/UX Конвенции
- Замена browser alert/confirm на кастомные модальные окна (AlertModal, ConfirmModal)
- Использование Material Design Icons Light (см. icons.md)
- Формат ID бронирований: `фото-{id}.{year}` где year из created_at
- Стили кнопок: glass-button (40x40 пикселей), glass-button-text (150x40 с текстом)
- Модальные окна: стили из modal.css (modal-overlay, modal-glass), кнопки из buttons.css

## Рекомендации по стилям
**Важно: не использовать `<style>` в .vue файлах! Только template + script!**

### CSS компоненты (по функциональности):
- `buttons.css` - кнопки (glass-button, glass-button-text, специальные кнопки календаря)
- `calendar.css` - стили FullCalendar
- `tables.css` - таблицы
- `modal.css` - модальные окна
- `layout.css` - layout, фильтры, бухгалтерия
- `sidebar.css` - сайдбар
- `topbar.css` - топбар
- `content.css` - контентная область
- `variables.css` - CSS переменные (цвета, отступы)

### Приоритеты стилей:
1. **Глобальные стили** - базовые настройки в style.css
2. **Inline стили** в template (только для динамических значений)
3. **Компонентные CSS** - специфичные стили в соответствующих .css файлах
4. Избегать дублирования стилей - использовать существующие CSS компоненты

## Технические нюансы
- `toISOString()` конвертирует в UTC, что может вызывать проблемы. Использовать только для API запросов
- FullCalendar `slotMaxTime` - исключающая граница. Для отображения до 22:00 установить 23:00:00
- `eventContent` callback: вернуть `true` для стандартного отображения, `{ html }` для кастомного, undefined для пропуска
- FullCalendar `calendarRef.getApi().getDate()` возвращает дату - использовать только после инициализации ref
- **Важно:** PHP DATE() возвращает только дату, для DATETIME использовать другие функции
- **Важно:** Логика статусов: "готово к выдаче" когда shooting_date <= today, "выдано" когда status='delivered'

## Цветовая схема статусов
### FullCalendar (BookingsFullCalendar.vue)
- new - стальной синий (#4682B4) + иконка камеры
- completed - оранжевый (#FFA500) + иконка галочки
- delivered - морской зеленый (#2E8B57) + иконка доставки
- cancelled/cancelled_client/cancelled_photographer/failed - серый

### Мини-календарь (BookingsCalendar.vue)
- new - синий кружок
- completed - оранжевый кружок "готово"
- delivered - зеленый кружок "выдано"
- failed - серый кружок "ошибка"
- cancelled - серый кружок "отменено"
- cancelled_client - серый кружок "отменено клиентом"
- cancelled_photographer - серый кружок "отменено фотографом"

### Заголовки и подписи
- Фон заголовков: #00FFFF (циан)
- Фон подписей: #1e40af (тёмно-синий)
- Шрифт: calc(1em + 2px), font-weight: 600
- В мини-календаре и таблицах: тёмно-синий (#1e40af)

## Адаптивность (<=480px)
- **Календарь:** кнопки переносятся на новую строку
  - `.fc-toolbar { flex-wrap: wrap }`
  - `.fc-toolbar-chunk:nth-child(2) { width: 100%; order: -1 }` (дата первой)
- **Sidebar:** сворачивается, overlay для мобильных
- **TopBar:** уменьшенная высота (50px вместо 46px), уменьшенные отступы
- **Content:** margin-left: 5px, margin-top зависит от TopBar
- **Hover эффекты:** отключены для touch устройств (transform: none)

## Последние изменения (09.02.2026)
### Логика статусов бронирований (api/bookings.php)
- **Исправление:** DATETIME > DATE конвертация для корректной работы
- **Новые действия:** 
  - `complete`: shooting_date <= today, статус='completed'
  - `deliver`: статус='completed' → статус='delivered' + processed_at=NOW()
- **Реализация:** api/bookings.php lines 375-410

### ViewBookingModal
- Телефонные номера как ссылки `<a href="tel:...">`
- Заголовки: тёмно-синий (#1e40af)

## Выполненные задачи
- Удалена проверка на конфликт бронирований (не требуется)
- Заменена надпись 'забронировано' на 'подтверждено бронирование'
- Реализована логика продолжительности съёмок
- Добавлена валидация при создании бронирований
- Реализована адаптивность календаря
- Настроены цветовые статусы
- Стилизованы заголовки и подписи
- Осталось: настроить hover эффекты для таблиц
- Осталось: доработать адаптивность для мобильных устройств