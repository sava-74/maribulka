#!/bin/bash

# Скрипт для запуска MCP серверов в терминале VS Code (macOS/Linux)
# Автор: AI Assistant для проекта Maribulka

echo "🚀 Запуск MCP серверов в терминале VS Code"
echo "=========================================="

# Установка переменных окружения для memento
export MEMENTO_API_KEY="mp_live_a6f04585bef67482d0629359a4d41cd0"
export MEMENTO_API_URL="https://memento-api.myrakrusemark.workers.dev"

echo "🔧 Запуск Sequential-thinking сервера..."
npx -y @modelcontextprotocol/server-sequential-thinking &

echo "🔧 Запуск Context7-mcp сервера..."
npx -y @upstash/context7-mcp@latest &

echo "🔧 Запуск Playwright-mcp сервера..."
npx @playwright/mcp@latest &

echo "🔧 Запуск Memento-protocol сервера..."
cd /d/GitHub/mcp-servers/memento-protocol && npm start &

echo ""
echo "✅ Все серверы запущены в фоновом режиме"
echo "Проверка статуса: jobs"
echo "Остановка: kill %<job-number>"