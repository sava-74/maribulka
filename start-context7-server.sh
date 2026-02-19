#!/bin/bash

# Скрипт для запуска Context7-mcp сервера
# Автор: AI Assistant для проекта Maribulka

echo "🚀 Запуск Context7-mcp сервера"
echo "=============================="

# Загрузка переменных окружения
ENV_FILE=".env.context7"
if [ -f "$ENV_FILE" ]; then
    echo "📄 Загрузка конфигурации из $ENV_FILE"
    
    # Загружаем переменные в текущую сессию
    export $(grep -v '^#' $ENV_FILE | xargs)
    
    # Показываем загруженные переменные (без значений для безопасности)
    while IFS='=' read -r key value; do
        if [[ $key != \#* ]] && [[ -n $key ]]; then
            echo "  Установлена переменная: $key"
        fi
    done < $ENV_FILE
else
    echo "⚠️ Файл конфигурации $ENV_FILE не найден"
fi

# Проверка наличия API ключа
if [ -n "$UPSTASH_REDIS_REST_TOKEN" ]; then
    echo "✅ API ключ загружен"
    # Показываем только начало ключа для безопасности
    echo "🔑 Ключ: ${UPSTASH_REDIS_REST_TOKEN:0:10}..."
else
    echo "❌ API ключ не найден!"
    echo "Пожалуйста, добавьте UPSTASH_REDIS_REST_TOKEN в файл .env.context7"
    exit 1
fi

# Проверка Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo "✅ Node.js: $NODE_VERSION"
else
    echo "❌ Node.js не найден. Пожалуйста, установите Node.js"
    exit 1
fi

# Активация nvm если установлен
if [ -s "$HOME/.nvm/nvm.sh" ]; then
    source "$HOME/.nvm/nvm.sh"
fi

# Запуск сервера
echo ""
echo "📡 Запуск Context7-mcp сервера..."
echo "Команда: npx -y @upstash/context7-mcp@latest"

npx -y @upstash/context7-mcp@latest

echo ""
echo "🏁 Скрипт завершен"