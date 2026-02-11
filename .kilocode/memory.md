# Проект Maribulka - Система учёта фотосъёмок

## Правила работы
1. **Изменения:** все изменения в файлах делать только по одобрению пользователя!
2. **Адаптивность:** только один брейкпоинт ≤768px. Режим ≤480px НЕ существует!
   - Всё что ≤768px - это мобилка
   - Стили от старого ≤480px должны быть перенесены в ≤768px (как сделано с календарём)

## Порядок работы
**Всегда ПЕРЕД вопросами:**
1. Изучить `DEPLOY_GUIDE.md` и `QUICK_START.md` в проекте
2. Проверить `.kilocode/memory.md` - там есть ответы
3. Выполнить анализ через SSH если нужно
4. Только потом спрашивать пользователя

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
- cancelled/cancelled_client/cancelled_photographer/failed - красный (#DC2626)

### Мини-календарь (BookingsCalendar.vue)
- new - синий кружок
- completed - оранжевый кружок "готово"
- delivered - зеленый кружок "выдано"
- failed - красный кружок "ошибка"
- cancelled - красный кружок "отменено"
- cancelled_client - красный кружок "отменено клиентом"
- cancelled_photographer - красный кружок "отменено фотографом"

### Заголовки и подписи
- Фон заголовков: #00FFFF (циан)
- Фон подписей: #1e40af (тёмно-синий)
- Шрифт: calc(1em + 2px), font-weight: 600
- В мини-календаре и таблицах: тёмно-синий (#1e40af)

## Адаптивность (≤768px)
- **Единый брейкпоинт:** всё что ≤768px - это мобилка
- **Удалить:** @media (max-width: 480px) полностью, его стили перенести в @media (max-width: 768px)
- **Календарь:** CSS: `.fc-timegrid { max-height: calc(100vh - 120px); overflow-y: auto }`
- **Модальные окна:**
  - Проблема: `max-height: 95vh` и `height: 100vh` обрезают контент
  - Решение: удалить оба, оставить `max-height: 90vh` с `overflow-y: auto`
- **Sidebar:** сворачивается, overlay для мобильных
- **TopBar:** уменьшенная высота (50px), уменьшенные отступы
- **Content:** margin-left: 5px
- **Hover эффекты:** отключены для touch устройств

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

---

## Подключение к серверу BeGet

### SSH подключение
```bash
ssh -i C:\Users\sava\.ssh\beget_maribulka sava7424@sava7424.beget.tech
```
Или команды из любой директории:
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech "команда"
```

### Документация для анализа
Всегда смотреть **ПЕРЕД** вопросами пользователя:
- `DEPLOY_GUIDE.md` - полный гайд по деплою
- `QUICK_START.md` - шпаргалка по командам

### Быстрые команды для анализа
```bash
# Структура проекта на сервере
ls -la /home/s/sava7424/maribulka.rf/

# База данных
mysql -u sava7424_mari -p'Zxc456Siti' -e "SELECT COUNT(*) FROM bookings" sava7424_mari

# Логи ошибок
tail -5 /home/s/sava7424/maribulka.rf/xn--80aac1alfd7a3a5g.xn--p1ai.error.log

# Проверка API файлов
ls -la /home/s/sava7424/maribulka.rf/maribulka-vue/dist/api/

# Мониторинг сайта
curl -s -o /dev/null -w "%{http_code}" http://марибулька.рф/
```

### Данные для подключения
| Параметр | Значение |
|----------|----------|
| **SSH Host** | sava7424.beget.tech |
| **SSH User** | sava7424 |
| **SSH Key** | ~/.ssh/beget_maribulka |
| **DB Host** | localhost |
| **DB Name** | sava7424_mari |
| **DB User** | sava7424_mari |
| **DB Pass** | Zxc456Siti |
| **Сайт** | http://марибулька.рф |

### Типовые задачи на сервере
1. Проверить ошибки → `tail -f *.error.log`
2. Проверить БД → `mysql -u sava7424_mari -p'...'`
3. Проверить файлы API → `ls -la /home/.../dist/api/`
4. Сделать бэкап → `cp -r dist dist_backup_$(date +%Y%m%d_%H%M%S)`
5. Откатить → `cp -r dist_backup_YYYYMM... dist`

---

## MCP Memory Server

### Установка (11.02.2026)
MCP Memory Server установлен для сохранения контекста между сессиями.

**Команды для установки:**
```bash
# Создание директории
mkdir "C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server"

# Инициализация проекта
cd "C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server"
npm init -y

# Установка зависимостей
npm install @modelcontextprotocol/sdk zod
npm install --save-dev @types/node typescript

# Создание файлов:
# - package.json
# - tsconfig.json
# - src/index.ts (см. ниже)

# Сборка
npm run build
```

**Конфигурация MCP Settings:**
Файл: `C:\Users\sava\AppData\Roaming\Code\User\globalStorage\kilocode.kilo-code\settings\mcp_settings.json`
```json
{
  "mcpServers": {
    "memory": {
      "command": "node",
      "args": ["C:\\Users\\sava\\AppData\\Roaming\\Kilo-Code\\MCP\\memory-server\\build\\index.js"],
      "env": {},
      "disabled": false,
      "alwaysAllow": [],
      "disabledTools": []
    }
  }
}
```

### Доступные инструменты

#### add_memory_note
Добавить заметку в память.

**Параметры:**
- `title` (string) - Заголовок заметки (обязательный)
- `content` (string) - Содержание заметки (обязательный)
- `category` (string) - Категория заметки (опциональный)

**Пример:**
```typescript
mcp--memory--add_memory_note({
  title: "Новая задача",
  content: "Сделать что-то важное",
  category: "tasks"
})
```

#### get_memory_notes
Получить все заметки из памяти.

**Параметры:**
- `category` (string) - Фильтр по категории (опциональный)

**Пример:**
```typescript
mcp--memory--get_memory_notes({ category: "tasks" })
```

#### delete_memory_note
Удалить заметку из памяти.

**Параметры:**
- `id` (number) - ID заметки для удаления

**Пример:**
```typescript
mcp--memory--delete_memory_note({ id: 1234567890 })
```

#### update_memory_note
Обновить заметку в памяти.

**Параметры:**
- `id` (number) - ID заметки для обновления (обязательный)
- `title` (string) - Новый заголовок (опциональный)
- `content` (string) - Новое содержание (опциональный)
- `category` (string) - Новая категория (опциональный)

**Пример:**
```typescript
mcp--memory--update_memory_note({
  id: 1234567890,
  title: "Обновленная задача",
  content: "Новое содержание"
})
```

#### add_context
Добавить контекстную информацию.

**Параметры:**
- `key` (string) - Ключ контекста (обязательный)
- `value` (string) - Значение контекста (обязательный)

**Пример:**
```typescript
mcp--memory--add_context({
  key: "current_task",
  value: "Работа над календарем"
})
```

#### get_context
Получить контекстную информацию.

**Параметры:**
- `key` (string) - Ключ контекста (опциональный)

**Пример:**
```typescript
mcp--memory--get_context({ key: "current_task" })
```

### Файлы сервера
- [`package.json`](C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server\package.json) - Конфигурация проекта
- [`tsconfig.json`](C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server\tsconfig.json) - Настройки TypeScript
- [`src/index.ts`](C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server\src\index.ts) - Основной код сервера
- [`build/index.js`](C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server\build\index.js) - Скомпилированный код
- [`memory.json`](C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server\src\index.ts:15) - Файл хранения данных (автоматически создается)

### Обновление сервера
```bash
cd "C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server"
npm run build
```