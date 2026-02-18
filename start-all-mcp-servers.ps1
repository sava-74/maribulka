# Скрипт для автоматического запуска всех MCP серверов в отдельных консолях
# Автор: AI Assistant для проекта Maribulka
# Дата: 2026-02-19

Write-Host "🚀 Запуск всех MCP серверов для проекта Maribulka" -ForegroundColor Green
Write-Host "===============================================" -ForegroundColor Green

# Проверка наличия Node.js
try {
    $nodeVersion = node --version
    Write-Host "✅ Node.js найден: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Node.js не найден. Пожалуйста, установите Node.js" -ForegroundColor Red
    exit 1
}

# Проверка наличия uvx
try {
    $uvxVersion = uvx --version
    Write-Host "✅ uvx найден: $uvxVersion" -ForegroundColor Green
} catch {
    Write-Host "⚠️ uvx не найден. Будет установлен при необходимости." -ForegroundColor Yellow
}

# Функция для запуска сервера в новой консоли
function Start-MCPServer {
    param(
        [string]$ServerName,
        [string]$Command,
        [string]$Description
    )
    
    Write-Host "🔧 Запуск $ServerName..." -ForegroundColor Cyan
    
    # Создаем PowerShell скрипт для запуска сервера
    $scriptContent = @"
Write-Host "🚀 Запуск $ServerName" -ForegroundColor Green
Write-Host "$Description" -ForegroundColor Yellow
Write-Host "Команда: $Command" -ForegroundColor Gray
Write-Host "================================" -ForegroundColor Gray

try {
    $Command
} catch {
    Write-Host "❌ Ошибка запуска $ServerName: `$_.Exception.Message" -ForegroundColor Red
    Read-Host "Нажмите Enter для закрытия окна"
}
"@
    
    # Сохраняем скрипт во временный файл
    $tempScript = [System.IO.Path]::GetTempFileName() + ".ps1"
    $scriptContent | Out-File -FilePath $tempScript -Encoding UTF8
    
    # Запускаем в новой консоли
    Start-Process powershell.exe -ArgumentList "-NoExit", "-ExecutionPolicy", "Bypass", "-File", "`"$tempScript`""
    
    Write-Host "✅ $ServerName запущен в новой консоли" -ForegroundColor Green
    Start-Sleep -Seconds 2
}

# Запуск серверов в порядке приоритета
Write-Host "`n📡 Запуск MCP серверов..." -ForegroundColor Blue

# 1. Basic-memory сервер (самый важный)
Start-MCPServer `
    -ServerName "Basic-memory сервер" `
    -Command "uvx basic-memory mcp" `
    -Description "Основной сервер памяти для хранения контекста и информации"

# 2. Sequential-thinking сервер
Start-MCPServer `
    -ServerName "Sequential-thinking сервер" `
    -Command "npx -y @modelcontextprotocol/server-sequential-thinking" `
    -Description "Сервер для пошагового анализа и логических рассуждений"

# 3. Context7-mcp сервер
Start-MCPServer `
    -ServerName "Context7-mcp сервер" `
    -Command "npx -y @upstash/context7-mcp@latest" `
    -Description "Сервер для управления контекстом и поиска в документации"

# 4. Playwright-mcp сервер
Start-MCPServer `
    -ServerName "Playwright-mcp сервер" `
    -Command "npx @playwright/mcp@latest" `
    -Description "Сервер для автоматизации браузера и тестирования"

Write-Host "`n⏱️ Ожидание запуска всех серверов..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

# Проверка состояния серверов
Write-Host "`n🔍 Проверка состояния серверов..." -ForegroundColor Blue

try {
    Write-Host "Запуск скрипта проверки..." -ForegroundColor Gray
    node check-mcp-servers.js
} catch {
    Write-Host "⚠️ Скрипт проверки не найден или возникла ошибка" -ForegroundColor Yellow
    Write-Host "Выполняю ручную проверку процессов..." -ForegroundColor Gray
    
    # Ручная проверка процессов
    $processes = Get-Process | Where-Object { 
        $_.CommandLine -like "*modelcontextprotocol*" -or 
        $_.CommandLine -like "*context7-mcp*" -or 
        $_.CommandLine -like "*playwright/mcp*" -or 
        $_.CommandLine -like "*basic-memory*mcp*" 
    }
    
    if ($processes.Count -gt 0) {
        Write-Host "✅ Найдено $($processes.Count) MCP процессов:" -ForegroundColor Green
        $processes | ForEach-Object {
            Write-Host "  - $($_.ProcessName) (PID: $($_.Id))" -ForegroundColor Gray
        }
    } else {
        Write-Host "⚠️ MCP процессы не найдены" -ForegroundColor Yellow
    }
}

Write-Host "`n🎉 Запуск завершен!" -ForegroundColor Green
Write-Host "Все серверы должны быть запущены в отдельных консолях." -ForegroundColor Cyan
Write-Host "Для проверки состояния используйте: node check-mcp-servers.js" -ForegroundColor Gray

# Ожидание перед закрытием
Write-Host "`nНажмите любую клавишу для выхода..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")