# Статус MCP серверов проекта Maribulka

## 📊 Текущий статус (обновлено 2026-02-19)

### ✅ Активные серверы:
1. **Sequential-thinking сервер** - `npx @modelcontextprotocol/server-sequential-thinking`
   - Назначение: Пошаговый анализ и логические рассуждения
   - Статус: ✅ Активен

2. **Context7-mcp сервер** - `npx @upstash/context7-mcp@latest`  
   - Назначение: Управление контекстом и поиск в документации
   - Статус: ✅ Активен

3. **Playwright-mcp сервер** - `npx @playwright/mcp@latest`
   - Назначение: Автоматизация браузера и тестирование
   - Статус: ✅ Активен

4. **Memento-protocol сервер** - `@myrakrusemark/memento-protocol`
   - Назначение: Долговременная память между сессиями
   - Статус: ✅ Активен
   - API ключ: mp_live_a6f04585bef67482d0629359a4d41cd0

### ❌ Удаленные серверы:
- **MCP Memory Service** - технические проблемы
- **Basic-memory сервер** - заменен на нормальные решения
- **Mode-manager-mcp** - проблемы совместимости с FastMCP 2.x

## 🚀 Скрипты запуска

### Автоматический запуск всех серверов:
```bash
# Windows
.\start-all-mcp-servers.ps1

# Linux/Mac  
./start-all-mcp-servers.sh
```

### Проверка статуса:
```bash
node check-mcp-servers.js
```

## 📋 Конфигурация

Серверы запускаются в следующем порядке приоритета:
1. Sequential-thinking (наиболее важный для логики)
2. Context7-mcp (для контекста и документации)  
3. Playwright-mcp (для автоматизации)
4. Memento-protocol (для долговременной памяти)

## ⚠️ Важно

- Все серверы стабильно работают
- Memento-protocol обеспечивает сохранение памяти между сессиями
- Система полностью функциональна