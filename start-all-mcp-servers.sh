#!/bin/bash

# Скрипт для автоматического запуска всех MCP серверов в отдельных терминалах
# Автор: AI Assistant для проекта Maribulka
# Дата: 2026-02-19

echo "🚀 Запуск всех MCP серверов для проекта Maribulka"
echo "==============================================="

# Проверка наличия Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo "✅ Node.js найден: $NODE_VERSION"
else
    echo "❌ Node.js не найден. Пожалуйста, установите Node.js"
    exit 1
fi

# Проверка наличия uvx
if command -v uvx &> /dev/null; then
    UVX_VERSION=$(uvx --version)
    echo "✅ uvx найден: $UVX_VERSION"
else
    echo "⚠️ uvx не найден. Будет установлен при необходимости."
fi

# Функция для запуска сервера в новом терминале
start_mcp_server() {
    local server_name="$1"
    local command="$2"
    local description="$3"
    
    echo "🔧 Запуск $server_name..."
    
    # Создаем временный скрипт для запуска сервера
    local temp_script=$(mktemp)
    cat > "$temp_script" << EOF
#!/bin/bash
echo "🚀 Запуск $server_name"
echo "$description"
echo "Команда: $command"
echo "================================"

# Активируем nvm если установлен
if [ -s "\$HOME/.nvm/nvm.sh" ]; then
    source "\$HOME/.nvm/nvm.sh"
fi

# Выполняем команду сервера
$command

# Если команда завершилась, ждем нажатия клавиши перед закрытием
echo ""
echo "Процесс завершен. Нажмите Enter для закрытия окна..."
read
EOF
    
    chmod +x "$temp_script"
    
    # Определяем тип терминала и запускаем скрипт
    if command -v gnome-terminal &> /dev/null; then
        # GNOME Terminal
        gnome-terminal --title="$server_name" -- bash "$temp_script" &
    elif command -v konsole &> /dev/null; then
        # Konsole (KDE)
        konsole --new-tab -e bash "$temp_script" &
    elif command -v xterm &> /dev/null; then
        # XTerm
        xterm -title "$server_name" -e bash "$temp_script" &
    elif command -v tilix &> /dev/null; then
        # Tilix
        tilix -t "$server_name" -e bash "$temp_script" &
    else
        # fallback - запуск в фоне
        echo "⚠️ Совместимый терминал не найден. Запуск в фоновом режиме..."
        bash "$temp_script" &
    fi
    
    echo "✅ $server_name запущен"
    sleep 2
}

# Запуск серверов в порядке приоритета
echo ""
echo "📡 Запуск MCP серверов..."

# 1. Basic-memory сервер (самый важный)
start_mcp_server \
    "Basic-memory сервер" \
    "uvx basic-memory mcp" \
    "Основной сервер памяти для хранения контекста и информации"

# 2. Sequential-thinking сервер
start_mcp_server \
    "Sequential-thinking сервер" \
    "npx -y @modelcontextprotocol/server-sequential-thinking" \
    "Сервер для пошагового анализа и логических рассуждений"

# 3. Context7-mcp сервер
start_mcp_server \
    "Context7-mcp сервер" \
    "npx -y @upstash/context7-mcp@latest" \
    "Сервер для управления контекстом и поиска в документации"

# 4. Playwright-mcp сервер
start_mcp_server \
    "Playwright-mcp сервер" \
    "npx @playwright/mcp@latest" \
    "Сервер для автоматизации браузера и тестирования"

echo ""
echo "⏱️ Ожидание запуска всех серверов..."
sleep 5

# Проверка состояния серверов
echo ""
echo "🔍 Проверка состояния серверов..."

if command -v node &> /dev/null && [ -f "check-mcp-servers.js" ]; then
    echo "Запуск скрипта проверки..."
    node check-mcp-servers.js
else
    echo "⚠️ Скрипт проверки не найден или Node.js недоступен"
    echo "Выполняю ручную проверку процессов..."
    
    # Ручная проверка процессов
    mcp_processes=$(pgrep -f "modelcontextprotocol|context7-mcp|playwright/mcp|basic-memory")
    if [ ! -z "$mcp_processes" ]; then
        echo "✅ Найдены MCP процессы:"
        ps aux | grep -E "(modelcontextprotocol|context7-mcp|playwright/mcp|basic-memory)" | grep -v grep
    else
        echo "⚠️ MCP процессы не найдены"
    fi
fi

echo ""
echo "🎉 Запуск завершен!"
echo "Все серверы должны быть запущены в отдельных терминалах."
echo "Для проверки состояния используйте: node check-mcp-servers.js"

echo ""
echo "Нажмите Enter для выхода..."
read