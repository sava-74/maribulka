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

**Последнее обновление:** 11.02.2026
