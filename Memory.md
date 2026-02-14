# MCP Memory Server - Руководство пользователя

## Что это?

MCP Memory Server - это инструмент для сохранения контекста между сессиями работы с проектом. Он позволяет хранить заметки и контекстную информацию, которая будет доступна в следующих сессиях.

## Установка

MCP Memory Server уже установлен и настроен для использования в проекте Maribulka.

**Расположение:**
- Директория: `C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server`
- Конфигурация: `C:\Users\sava\AppData\Roaming\Code\User\globalStorage\kilocode.kilo-code\settings\mcp_settings.json`
- Файл данных: `C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server\src\memory.json`

## Доступные инструменты

### 1. add_memory_note

Добавляет новую заметку в память.

**Параметры:**
| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| title | string | Да | Заголовок заметки |
| content | string | Да | Содержание заметки |
| category | string | Нет | Категория заметки (по умолчанию: "general") |

**Пример использования:**
```typescript
mcp--memory--add_memory_note({
  title: "Важная задача",
  content: "Необходимо проверить API endpoints перед релизом",
  category: "tasks"
})
```

**Результат:**
```json
{
  "content": [
    {
      "type": "text",
      "text": "Заметка \"Важная задача\" добавлена в память"
    }
  ]
}
```

---

### 2. get_memory_notes

Получает все заметки из памяти. Можно фильтровать по категории.

**Параметры:**
| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| category | string | Нет | Фильтр по категории |

**Пример использования (все заметки):**
```typescript
mcp--memory--get_memory_notes()
```

**Пример использования (фильтр по категории):**
```typescript
mcp--memory--get_memory_notes({ category: "tasks" })
```

**Результат:**
```json
[
  {
    "id": 1707676800000,
    "title": "Важная задача",
    "content": "Необходимо проверить API endpoints перед релизом",
    "category": "tasks",
    "createdAt": "2024-02-11T17:00:00.000Z"
  }
]
```

---

### 3. delete_memory_note

Удаляет заметку из памяти по ID.

**Параметры:**
| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| id | number | Да | ID заметки для удаления |

**Пример использования:**
```typescript
mcp--memory--delete_memory_note({ id: 1707676800000 })
```

**Результат (успех):**
```json
{
  "content": [
    {
      "type": "text",
      "text": "Заметка с ID 1707676800000 удалена"
    }
  ]
}
```

**Результат (не найдено):**
```json
{
  "content": [
    {
      "type": "text",
      "text": "Заметка с ID 1707676800000 не найдена"
    }
  ],
  "isError": true
}
```

---

### 4. update_memory_note

Обновляет существующую заметку.

**Параметры:**
| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| id | number | Да | ID заметки для обновления |
| title | string | Нет | Новый заголовок |
| content | string | Нет | Новое содержание |
| category | string | Нет | Новая категория |

**Пример использования:**
```typescript
mcp--memory--update_memory_note({
  id: 1707676800000,
  title: "Обновленная задача",
  content: "Новое содержание задачи"
})
```

**Результат (успех):**
```json
{
  "content": [
    {
      "type": "text",
      "text": "Заметка с ID 1707676800000 обновлена"
    }
  ]
}
```

---

### 5. add_context

Добавляет или обновляет контекстную информацию по ключу.

**Параметры:**
| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| key | string | Да | Ключ контекста |
| value | string | Да | Значение контекста |

**Пример использования:**
```typescript
mcp--memory--add_context({
  key: "current_task",
  value: "Работа над календарем бронирований"
})
```

**Результат:**
```json
{
  "content": [
    {
      "type": "text",
      "text": "Контекст \"current_task\" обновлен"
    }
  ]
}
```

---

### 6. get_context

Получает контекстную информацию.

**Параметры:**
| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| key | string | Нет | Ключ контекста (если не указан - все контексты) |

**Пример использования (конкретный ключ):**
```typescript
mcp--memory--get_context({ key: "current_task" })
```

**Пример использования (все контексты):**
```typescript
mcp--memory--get_context()
```

**Результат:**
```json
{
  "current_task": "Работа над календарем бронирований"
}
```

---

## Практические примеры использования

### Сценарий 1: Сохранение задач
```typescript
// Добавляем задачу
mcp--memory--add_memory_note({
  title: "Исправить баг с календарем",
  content: "Проблема с отображением событий в FullCalendar",
  category: "bugs"
})

// Добавляем еще одну задачу
mcp--memory--add_memory_note({
  title: "Добавить фильтрацию",
  content: "Добавить фильтр по датам в календаре",
  category: "features"
})

// Получаем все задачи
mcp--memory--get_memory_notes({ category: "bugs" })
```

### Сценарий 2: Управление контекстом
```typescript
// Устанавливаем текущий контекст
mcp--memory--add_context({
  key: "current_project",
  value: "maribulka"
})

mcp--memory--add_context({
  key: "current_task",
  value: "Работа над календарем"
})

// Получаем контекст
mcp--memory--get_context()
```

### Сценарий 3: История изменений
```typescript
// Добавляем заметку о изменении
mcp--memory--add_memory_note({
  title: "Изменение API",
  content: "Обновлен endpoint /api/bookings - добавлен статус delivered",
  category: "api"
})

// Обновляем заметку
mcp--memory--update_memory_note({
  id: 1707676800000,
  content: "Обновлен endpoint /api/bookings - добавлен статус delivered и processed_at"
})

// Удаляем устаревшую заметку
mcp--memory--delete_memory_note({ id: 1707676800000 })
```

## Структура данных

### Заметка (MemoryNote)
```typescript
interface MemoryNote {
  id: number;           // Уникальный ID (timestamp)
  title: string;        // Заголовок
  content: string;      // Содержание
  category: string;     // Категория
  createdAt: string;    // Дата создания (ISO 8601)
}
```

### Память (Memory)
```typescript
interface Memory {
  context: Record<string, string>;  // Контекстная информация
  notes: MemoryNote[];              // Список заметок
}
```

## Обновление сервера

Если вы вносите изменения в код сервера:

```bash
cd "C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server"
npm run build
```

## Удаление сервера

Если нужно удалить MCP Memory Server:

1. Удалите конфигурацию из `mcp_settings.json`
2. Удалите директорию `C:\Users\sava\AppData\Roaming\Kilo-Code\MCP\memory-server`
3. Удалите файл данных `memory.json` (если нужен полный сброс)

## Часто задаваемые вопросы

**Q: Где хранятся данные?**  
A: Данные хранятся в файле `memory.json` в директории сервера.

**Q: Можно ли использовать категорию "general"?**  
A: Да, если категория не указана, используется "general" по умолчанию.

**Q: Что произойдет при удалении заметки?**  
A: Заметка будет удалена из памяти и файл `memory.json` будет обновлен.

**Q: Можно ли обновить только часть полей заметки?**  
A: Да, при обновлении можно указать только те поля, которые нужно изменить.

---

# Проект Maribulka - Система учёта фотосъёмок

## Правила работы
1. **Изменения:** все изменения в файлах делать только по одобрению пользователя!
2. **Адаптивность:** только один брейкпоинт ≤768px. Режим ≤480px НЕ существует!
   - Всё что ≤768px - это мобилка
   - Стили от старого ≤480px должны быть перенесены в ≤768px (как сделано с календарём)
3. **ДЕПЛОЙ:**
   - ❌ НЕ делать `git commit` автоматически
   - ❌ НЕ делать `git push` автоматически
   - ✅ Пользователь сам делает коммиты и деплой через GitHub после локального тестирования
   - ✅ Только сообщать пользователю какие файлы изменены и готовы для коммита

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
status (enum: new/completed/delivered/cancelled/cancelled_client/cancelled_photographer/failed),
cancel_reason, notes, created_at, updated_at, processed_at
```
**Поля:**
- `processed_at` DATETIME - дата/время обработки заказа
- `notes` TEXT - заметки к заказу
- `status` включает дополнительные статусы: cancelled_client, cancelled_photographer, failed

## UI/UX Конвенции
- Замена browser alert/confirm на кастомные модальные окна (AlertModal, ConfirmModal)
- Использование Material Design Icons Light (см. icons.md)
- Формат ID бронирований: `МБ{id}{magicNumber}{year}` где magicNumber = день * месяц, year = последние 2 цифры года из created_at
  - Пример: МБ1312526 (id=13, день=13, месяц=1, magicNumber=13*1=13, год=26)
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
- ✅ **Справочник "Клиенты" (ClientsTable.vue) реализован (13.02.2026)**
  - Переведён на TanStack Table (БЕЗ checkbox)
  - Добавлены кнопки управления (Добавить, Просмотр, Редактирование, Удалить)
  - Реализована сортировка по всем колонкам
  - Выбор строки по клику на всю строку (enableMultiRowSelection: false)
  - Модалки: AddClientModal, EditClientModal, ViewClientModal, DeleteConfirmModal
  - Валидация удаления: проверка связей с bookings через API endpoint check_relations
  - Логика удаления: если клиент участвует в заказах → AlertModal с запретом; иначе → ConfirmModal
  - Задеплоено на BeGet (api/clients.php с endpoint check_relations)
- Осталось: настроить hover эффекты для таблиц
- Осталось: доработать адаптивность для мобильных устройств

---

## ⭐ КРИТИЧНО: СЕРВЕР И ДЕПЛОЙ ⭐

### НЕТ ЛОКАЛЬНОГО PHP СЕРВЕРА!

**ВАЖНО:** У проекта НЕТ локального PHP сервера! Все API запросы идут на удалённый сервер BeGet через Vite proxy.

### Структура серверов

**Локальная разработка:**
- `npm run dev` → http://localhost:5173
- Vite dev server с proxy на BeGet (см. vite.config.ts)
- Frontend работает локально
- **API запросы проксируются на** http://xn--80aac1alfd7a3a5g.xn--p1ai

**Продакшн:**
- http://марибулька.рф (http://xn--80aac1alfd7a3a5g.xn--p1ai)
- Сервер: BeGet shared hosting
- SSH: sava7424@sava7424.beget.tech
- Путь на сервере: /home/s/sava7424/maribulka.rf/maribulka-vue/dist/

### Vite Proxy (vite.config.ts)
```typescript
server: {
  proxy: {
    '/api': {
      target: 'http://xn--80aac1alfd7a3a5g.xn--p1ai',
      changeOrigin: true,
      secure: false
    }
  }
}
```

### Деплой на BeGet

**Порядок действий:**
1. **ВСЕГДА** коммитить изменения: `git add . && git commit -m "..."`
2. **ВСЕГДА** пушить в GitHub: `git push`
3. **ТОЛЬКО ПОТОМ** запускать деплой: `.\deploy.ps1` (Windows) или `./deploy.sh` (Linux/Mac)

**Скрипт деплоя делает:**
1. Собирает Vue проект (npm run build)
2. Загружает dist/ на сервер через scp
3. Загружает api/ на сервер в dist/api/

**Важно:** После изменений в `api/` файлах (например, api/clients.php), ОБЯЗАТЕЛЬНО задеплоить на сервер, иначе изменения не будут работать локально (т.к. Vite проксирует на старую версию API на BeGet).

### Подключение к серверу BeGet

**SSH подключение:**
```bash
ssh -i C:\Users\sava\.ssh\beget_maribulka sava7424@sava7424.beget.tech
```

**Быстрые команды для анализа:**
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
| **DB Host** | localhost (НА СЕРВЕРЕ!) |
| **DB Name** | sava7424_mari |
| **DB User** | sava7424_mari |
| **DB Pass** | Zxc456Siti |
| **Сайт** | http://марибулька.рф |

### Документация для анализа
Всегда смотреть **ПЕРЕД** вопросами пользователя:
- `DEPLOY_GUIDE.md` - полный гайд по деплою
- `QUICK_START.md` - шпаргалка по командам

---

## CSS файлы проекта (12.02.2026)

### Статус использования CSS переменных

| Файл | Статус | Жёстко заданные цвета |
|------|--------|----------------------|
| theme.css | ✅ Определяет переменные | 0 |
| buttons.css | ✅ Полностью через переменные | 0 |
| calendar.css | ⚠️ Частично | 5 цветов |
| tables.css | ⚠️ Частично | 11 цветов |
| layout.css | ⚠️ Частично | 6 цветов |
| modal.css | ⚠️ Частично | 14 цветов |
| responsive.css | - | 0 |
| sidebar.css | ✅ Полностью через переменные | 0 |
| topbar.css | ✅ Полностью через переменные | 0 |

### Основные CSS переменные из theme.css (Актуально на 13.02.2026)

**Цвета и общие настройки:**
- `--generalColor`: `#39FF14` (ярко-зеленый неон) — основной акцентный цвет.
- `--neon-blue`: `#00F3FF` — дополнительный неоновый цвет.
- `--generalTextColor`: `#333` — основной цвет текста.
- `--glass-bg`: `rgba(255, 255, 255, 0.4)` — фон панелей с эффектом стекла.
- `--glass-bgFilter`: `blur(12px)` — сила размытия для эффекта стекла.
- `--panelRadius`: `12px` — радиус скругления панелей.
- `--glass-shadowPanel`: `10px 10px 10px rgba(0, 0, 0, 0.2)` — тень панелей.

**Кнопки:**
- `--glass-bgButton`: `rgba(255, 255, 255, 0.4)` — фон кнопок.
- `--glass-bgButtonActive`: `rgba(57, 255, 20, 0.3)` — фон активной/нажатой кнопки.
- `--glass-widthButton`: `40px` / `--glass-heightButton`: `40px` — стандартный размер квадратной кнопки.
- `--glass-widthButtonText`: `140px` — ширина кнопки с текстом.
- `--glass-borderRadiusButton`: `8px` — скругление кнопок.
- `--svg-colorButton`: `#333` — цвет иконок в кнопках.

**Таблицы и статусы:**
- `--generalTabBorderColor`: `#000` — цвет границ таблицы и ячеек.
- `--tableHeaderBg`: `#f0f0f0` — фон заголовков таблиц.
- `--tableSelectedBg`: `rgba(74, 222, 128, 0.2)` — фон выбранной строки.
- `--incomeColor`: `#4ade80` — цвет дохода (зеленый).
- `--expenseColor`: `#f87171` — цвет расхода (красный).
- `--statusCancelledColor`: `#9ca3af` — цвет для отмененных статусов (серый).
- `--warningColor`: `#fbbf24` — цвет для предупреждений/авансов (желтый).
- `--infoColor`: `#3b82f6` — цвет для информации/баланса (синий).
- `--tableTextColor`: `#000` — основной цвет текста в таблицах.
- `--textMutedLightColor`: `rgba(255, 255, 255, 0.7)` — светлый приглушенный текст.
- `--text-colorAlert`: `#ff0000` — цвет текста предупреждений.

**Календарь:**
- `--calendar-bg`: `rgba(252, 250, 250, 0.8)` — фон сетки календаря.
- `--calendar-colorDayGrid`: `rgba(57, 255, 20, 0.1)` — подсветка ячеек дней.
- `--calendar-colorToday`: `rgba(28, 20, 255, 0.15)` — выделение текущего дня.

---

## Стили таблиц (12.02.2026) - ЗАВЕРШЕНО

### Сформировавшийся шаблон таблиц

**CSS классы:**
1. `.table-general` - подложка (фон, рамка, скругление, тень)
2. `.table-scroll-container` - контейнер прокрутки (рамка, overflow)
3. `.accounting-table` - сама таблица (без внешней рамки)
4. `.accounting-table thead` - заголовки (серый фон, липкое позиционирование)
5. `.accounting-table th, td` - ячейки (внутренние рамки, padding)

---

## План работы с CSS переменными для таблиц (13.02.2026)

### Пункт 1: Создание переменных цветов для таблиц ✅ ВЫПОЛНЕН
**Дата:** 13.02.2026

**Добавлены новые переменные в theme.css:**
- `--generalTabBorderColor: #000` - рамка контейнера и ячеек
- `--tableHeaderBg: #f0f0f0` - фон заголовка
- `--tableHoverBg: rgba(255, 255, 255, 0.1)` - hover эффект
- `--tableSelectedBorder: #4ade80` - рамка выбранной строки
- `--tableSelectedBg: rgba(74, 222, 128, 0.2)` - фон выбранной строки
- `--incomeColor: #4ade80` - доход (зелёный)
- `--expenseColor: #f87171` - расход (красный)
- `--statusCancelledColor: #9ca3af` - статус отменено
- `--linkColor: #1e40af` - ссылки телефона

### Пункт 2: Приведение таблиц к единым стилям ✅ ВЫПОЛНЕН
**Дата:** 13.02.2026

---

## История изменений (13.02.2026)
### Улучшения таблицы заказов
- **Формат дат:** Изменен на `ДД.ММ.ГГ` (например, `13.02.26`).
- **Интерфейс:** Уменьшена высота строк (padding 6px), запрещен перенос текста (`white-space: nowrap`).
- **Центровка:** Настроено выравнивание по центру для столбцов: Дата выдачи, Кол-во, Стоимость, Сумма.
- **Валюта:** Символ `₽` удален из ячеек и добавлен в заголовок столбца «Сумма ₽».
- **Маска ID:** Новый формат ID заказа: `МБ{id}{день*месяц}{ГГ}`.
- **Стили:** Вернулась разделительная линия между заголовком и телом таблицы.

### Исправление мобильного календаря (13.02.2026)
- **Проблема:** В мобильной версии (режим месяца) точки не сигнализировали о наличии проблемных заказов (алертов).
- **Решение:** Внедрена логика "один день — один тип индикации" для мобильного режима месяца.
- **Логика:**
  - Если в дне есть хотя бы один заказ со статусом `cancelled_client`, `cancelled_photographer`, `cancelled` или `failed`, точка окрашивается в `var(--expenseColor)` (красный).
  - В остальных случаях точка окрашивается в `var(--statusCancelledColor)` (темно-серый).
- **Важно:** 
  - Десктопная версия и мобильный режим дня сохранили полную цветовую схему статусов.
  - Исправлена детекция дат (используется локальное время `YYYY-MM-DD` вместо UTC), что обеспечило точность срабатывания алертов.

**Последнее обновление:** 13.02.2026

### Унификация формата ID заказа (13.02.2026)
- **Проблема:** В модалках ViewBookingModal, DeliverBookingModal, CancelBookingModal использовался старый формат `МБ-{id}.{year}`
- **Решение:** Обновлен формат ID во всех трех модалках на новый: `МБ{id}{magicNumber}{year}`
- **Формула:** magicNumber = день * месяц из created_at
- **Реализация:** Все модалки теперь используют единый алгоритм расчета ID
- **Файлы:** ViewBookingModal.vue, DeliverBookingModal.vue, CancelBookingModal.vue (строки 26-36)

**Последнее обновление:** 13.02.2026

### Реорганизация памяти (13.02.2026)
- Файлы `.kilocode/memory.md` и `MCPMemory.md` объединены в один общий файл `MCPMemory.md`.
- Старый файл `.kilocode/memory.md` удален для избежания дублирования.
- Теперь вся история проекта, правила и руководство по серверу памяти хранятся в `MCPMemory.md`.
- Успехи по улучшению таблицы (даты, ID, стили) зафиксированы в обеих системах памяти (MCP и файл проекта).

### Исправление окрашивания таблиц (13.02.2026)
- Исправлена проблема, когда столбец «Сумма» не окрашивался в красный цвет при статусе «Не состоялась».
- Класс `.amount-income` в `tables.css` теперь использует `color: inherit`, чтобы наследовать цвет текста от родительской строки (например, `.failed-booking`).
- В `theme.css` подтверждены основные переменные: `--generalColor` (#39FF14), `--incomeColor` (#4ade80), `--expenseColor` (#f87171).

---

## Изменения 14.02.2026

### 1. Мобильная адаптация модальных окон (≤768px)
**Файл:** `modal.css` (строки 550-588)

**Проблема:** На мобильных устройствах большие модалки обрезались сверху/снизу, маленькие были прижаты к верху.

**Решение:**
- **Большие модалки** (AddBookingModal, EditBookingModal, ViewBookingModal):
  - `.modal-overlay`: `display: block` + `overflow-y: auto` вместо `flex`
  - `.modal-glass`: отступы `margin: 20px 15px`, `max-width: calc(100vw - 30px)`
  - Возможность прокрутки всего содержимого

- **Маленькие модалки** (AlertModal, ConfirmModal, LoginModal):
  - Новый класс `.modal-small`
  - Центрирование: `position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);`
  - Всегда по центру экрана независимо от прокрутки

**Файлы изменены:**
- `modal.css` - добавлена секция mobile adaptation
- `AlertModal.vue` - добавлен класс `modal-small` (line 16)
- `ConfirmModal.vue` - добавлен класс `modal-small` (line 16)
- `LoginModal.vue` - добавлен класс `modal-small` (line 48)

---

### 2. Календарь - финансовая информация в режиме дня
**Файл:** `BookingsFullCalendar.vue` (строки 198-211)

**Задача:** Показывать информацию об оплате во второй строке записи в режиме дня.

**Логика:**
- **Не оплачено** (`unpaid`): показываем только `, долг {сумма} ₽`
- **Частично оплачено** (`partially_paid`): `, оплачено {сумма} ₽, долг {остаток} ₽`
- **Полностью оплачено** (`fully_paid`): ничего не добавляем

**Реализация:**
```javascript
const totalAmount = Number(booking.total_amount) || 0
const paidAmount = Number(booking.paid_amount) || 0
const remaining = totalAmount - paidAmount
let financeDetails = ''

if (booking.payment_status === 'unpaid') {
  financeDetails = `, долг ${Math.round(remaining)} ₽`
} else if (booking.payment_status === 'partially_paid') {
  financeDetails = `, оплачено ${Math.round(paidAmount)} ₽, долг ${Math.round(remaining)} ₽`
}
```

---

### 3. AddBookingModal - поле оплаты по умолчанию и позиционирование
**Файлы:** `AddBookingModal.vue`, `modal.css`

**Проблемы:**
1. Поле оплаты по умолчанию равнялось итоговой сумме (должно быть 0)
2. Поле использовало `position: absolute`, что ломало вёрстку на разных экранах

**Решения:**
1. **Значение по умолчанию:**
   - Удален `watch` который ставил `paymentAmount = totalAmount` (строки 115-118)
   - Теперь `paymentAmount` по умолчанию = 0 (line 25)

2. **Позиционирование:**
   - Создан новый класс `.payment-input-inline` в `modal.css` (строки 284-293)
   - Убран `position: absolute`, теперь inline элемент с фиксированной шириной 60px
   - `AddBookingModal.vue` line 417: заменён класс на `payment-input-inline`

---

### 4. Проект переименован "FotoMari" → "Марибулька"
**Файлы:** `index.html`, `camera.svg`, `theme.css`

**Изменения:**
1. **index.html:**
   - `<html lang="ru">` (было "en")
   - `<title>Марибулька</title>` (было "Maribulka Vue")
   - `<link rel="icon" href="/camera.svg">` (было "/vite.svg")
   - Description обновлён на "FotoMari - система управления фотостудией"

2. **camera.svg:**
   - Создан новый SVG файл с иконкой камеры
   - Использует `fill="currentColor"` вместо жёсткого цвета
   - Цвет задаётся через CSS переменную

3. **theme.css:**
   - Добавлен стиль: `link[rel="icon"] { color: var(--generalColor); }`
   - Favicon теперь окрашен в генеральный цвет проекта (#39FF14)

---

### 5. Убрана лишняя прокрутка в content
**Файл:** `layout.css`

**Проблема:** Даже когда контент влезал на экран (таблица скрыта, только календарь), была возможность прокрутить вверх на ~половину экрана.

**Решение:**
- Убран `min-height: 100vh` из `.accounting`
- Добавлен `min-height: 400px` для предотвращения полного схлопывания
- Теперь контейнер занимает только нужную высоту

---

### 6. Мобильный календарь - исправлены цвета точек в режиме дня
**Файл:** `BookingsFullCalendar.vue` (строки 153-159)

**Проблема:** В мобильном режиме дня все записи становились красными если хоть одна запись была красной.

**Решение:**
- Приоритет красного цвета применяется ТОЛЬКО к режиму месяца на мобилке
- Условие: `if (windowWidth.value <= 768 && !isDayView.value)`
- В режиме дня (и на десктопе, и на мобилке) каждая запись окрашена по своему статусу
- Добавлен комментарий для ясности логики

**Код:**
```javascript
if (windowWidth.value <= 768 && !isDayView.value) {
  // МОБИЛЬНАЯ ВЕРСИЯ (только режим месяца): серый или красный алерт
  const hasAlert = eventDate && datePriorityMap.has(eventDate)
  backgroundColor = hasAlert ? 'var(--text-colorAlert)' : 'var(--statusCancelledColor)'
  textColor = 'transparent'
}
// В режиме дня (мобилка и десктоп) каждая запись окрашена по своему статусу - НЕ применяем приоритет красного
```

---

### 7. Исправление модалок - убраны inline стили, уменьшены отступы
**Файлы:** `AlertModal.vue`, `ConfirmModal.vue`, `CancelBookingModal.vue`, `modal.css`

**Проблемы:**
1. Inline стили в AlertModal и ConfirmModal (нарушение правил проекта)
2. Большие отступы в CancelBookingModal (20px margin, 15px padding)
3. Использовался жёсткий цвет `#333` вместо переменной

**Решения:**
1. **Убраны inline стили:**
   - В AlertModal и ConfirmModal заменено `style="text-align: center; margin: 20px 0; color: #333"` на класс `.modal-message`
   - Создан класс `.modal-message` в modal.css с использованием `var(--generalTextColor)`

2. **Уменьшены отступы в CancelBookingModal:**
   - `.cancel-info, .delivery-info`: `margin: 20px 0` → `margin: 2px 0`, `padding: 15px` → `padding: 2px`
   - `.cancel-info p, .delivery-info p`: `margin: 8px 0` → `margin: 2px 0`
   - Заменён `color: #333` на `color: var(--generalTextColor)`

3. **Класс modal-small временно отключен:**
   - Стиль `.modal-small` оставлен в modal.css (строки 569-577) для будущего использования
   - Убран класс из всех модалок: AlertModal, ConfirmModal, LoginModal, CancelBookingModal
   - **Причина:** Требуется дополнительное тестирование перед применением

**Файлы изменены:**
- `modal.css` - добавлен класс `.modal-message`, исправлены отступы в `.cancel-info`
- `AlertModal.vue` - убран inline стиль, убран класс `modal-small`
- `ConfirmModal.vue` - убран inline стиль, убран класс `modal-small`
- `LoginModal.vue` - убран класс `modal-small`
- `CancelBookingModal.vue` - убран класс `modal-small`

---

## Эталоны структуры таблиц и вкладок (14.02.2026) ⭐

### СТРУКТУРА ВКЛАДОК (эталон: Accounting.vue)

**HTML структура:**
```vue
<div class="accounting">
  <!-- Навигация (вкладки) -->
  <div class="accounting-nav">
    <nav class="tabs">
      <button
        class="glass-button-text"
        @click="switchTab('tab1')"
        :class="{ active: activeTab === 'tab1' }"
      >
        Название вкладки 1
      </button>
    </nav>
  </div>

  <!-- Контент вкладок -->
  <div class="tab-content">
    <ComponentTab1 v-if="activeTab === 'tab1'" />
  </div>
</div>
```

**Правила:**
- Класс кнопки: `glass-button-text`
- Активная вкладка: `:class="{ active: activeTab === 'название' }`
- Обёртка: `.accounting` → `.accounting-nav` → `.tabs`
- Контент: `.tab-content`

---

### СТРУКТУРА ТАБЛИЦ (эталон: IncomeTable.vue) ⭐⭐⭐

**КРИТИЧНО: БЕЗ ЧЕКБОКСОВ!**

**Column definitions:**
```typescript
// Column definitions (БЕЗ checkbox)
const columns: ColumnDef<any>[] = [
  {
    accessorKey: 'id',
    header: 'ID'
  },
  {
    accessorKey: 'name',
    header: 'Название'
  }
]
```

**Table instance:**
```typescript
const table = useVueTable({
  get data() { return store.data },
  columns,
  state: {
    get sorting() { return sorting.value },
    get rowSelection() { return rowSelection.value },
    get columnFilters() { return columnFilters.value }
  },
  enableRowSelection: true,
  enableMultiRowSelection: false, // Только одна строка
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getFacetedUniqueValues: getFacetedUniqueValues()
})
```

**Кнопки управления:**
- Добавить (mdilPlus) - если нужно
- Просмотр (mdilEye) - :disabled="!hasSingleSelection"
- Редактировать (mdiFileEditOutline) - :disabled="!hasSingleSelection"
- Удалить (mdilDelete) - :disabled="!hasSelectedRow"
- Фильтры (mdilMagnify) - переключает showFilters
- Обновить (mdilRefresh) - вызывает refreshData

**Выбор строк:**
```typescript
const selectedItems = computed(() =>
  table.getFilteredSelectedRowModel().rows.map(row => row.original)
)
const selectedItem = computed(() =>
  selectedItems.value.length === 1 ? selectedItems.value[0] : null
)
const hasSelectedRow = computed(() =>
  Object.keys(rowSelection.value).length > 0
)
const hasSingleSelection = computed(() =>
  Object.keys(rowSelection.value).length === 1
)
```

---

### Sidebar - структура меню (14.02.2026)

**Кнопки:**
1. **Меню** (mdilMenu) - всегда
2. **Портфолио** (mdilImage) - все
3. **Бухгалтерия** (mdilCurrencyRub) - админ → Accounting.vue
4. **Справочники** (mdiFileCabinet) - админ → Settings.vue
5. **Настройки** (mdilSettings) - админ → ReferenceSettings.vue

**ВАЖНО:**
- Справочники (`'references'`) → References.vue (Клиенты, Типы съёмок, Акции)
- Настройки (`'settings'`) → Settings.vue (будущее)

---

### СТИЛИ МОДАЛОК (эталон: AddBookingModal.vue) ⭐⭐⭐

**КРИТИЧНО: ИСПОЛЬЗОВАТЬ ТОЛЬКО СУЩЕСТВУЮЩИЕ КЛАССЫ!**

**HTML структура модалки:**
```vue
<Teleport to="body">
  <div v-if="isVisible" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-glass">
      <h2>Заголовок модалки</h2>

      <!-- Поля ввода -->
      <div class="input-group">
        <div class="input-field">
          <label class="input-label">Название поля: <span class="required">*</span></label>
          <input type="text" class="modal-input" v-model="value" placeholder="Подсказка" />
        </div>

        <div class="input-field">
          <label class="input-label">Другое поле:</label>
          <textarea class="modal-input" v-model="notes" rows="3"></textarea>
        </div>
      </div>

      <!-- Кнопки -->
      <div class="modal-actions">
        <button class="glass-button" @click="emit('close')">
          <svg-icon type="mdi" :path="mdilCancel" />
        </button>
        <button class="glass-button" @click="handleSubmit">
          <svg-icon type="mdi" :path="mdilCheck" />
        </button>
      </div>
    </div>
  </div>
  <AlertModal :isVisible="showAlert" ... />
</Teleport>
```

**Обязательные CSS классы (из modal.css):**

**Структура:**
- `.modal-overlay` - фон модалки
- `.modal-glass` - само окно модалки
- `.input-group` - группа всех полей ввода
- `.input-field` - одно поле ввода
- `.input-row` - строка с несколькими полями (если в ряд)
- `.modal-actions` - блок с кнопками

**Элементы формы:**
- `.input-label` - подпись поля
- `.modal-input` - поле ввода (input, textarea, select)
- `.required` - звёздочка обязательного поля

**Кнопки:**
- `.glass-button` - кнопка с иконкой (40x40px)

**ЗАПРЕЩЕНО использовать:**
- ❌ `.form-group` - НЕТ ТАКОГО КЛАССА
- ❌ `.form-label` - НЕТ ТАКОГО КЛАССА
- ❌ `.form-input` - НЕТ ТАКОГО КЛАССА
- ❌ Любые самопридуманные классы

**Модалки просмотра (ViewModal):**
```vue
<div class="delivery-info">
  <p><strong>Поле:</strong> {{ value }}</p>
</div>
```

---

## План на завтра (14.02.2026)

1. **Изменить фильтры** (детали уточнить)

2. **Справочники - сделать редактируемыми:**
   - ✅ Клиенты (clients) - ГОТОВО
   - Типы съёмок (shooting_types)
   - Акции (promotions)

---

## ВАЖНОЕ ПРАВИЛО ДЛЯ CLAUDE
**ЗАПИСЫВАТЬ ВСЁ В:**
1. ✅ `D:\GitHub\maribulka\Memory.md` - ЭТОТ файл (главный файл памяти проекта)
2. ✅ MCP Memory (авто память через инструменты `mcp__memory__*`)
3. ❌ **НЕ** в `C:\Users\sava\.claude\projects\d--GitHub-maribulka\memory\MEMORY.md`

Это правило было нарушено более 10 раз за сегодня - исправлено!
