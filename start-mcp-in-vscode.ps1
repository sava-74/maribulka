# Скрипт для запуска MCP серверов в одном терминале VS Code
# Автор: AI Assistant для проекта Maribulka

Write-Host "🚀 Запуск MCP серверов в терминале VS Code" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green

# Установка переменных окружения для memento
$env:MEMENTO_API_KEY = "mp_live_a6f04585bef67482d0629359a4d41cd0"
$env:MEMENTO_API_URL = "https://memento-api.myrakrusemark.workers.dev"

Write-Host "🔧 Запуск Sequential-thinking сервера..." -ForegroundColor Cyan
Start-Job -ScriptBlock { npx -y @modelcontextprotocol/server-sequential-thinking } -Name "sequential"

Write-Host "🔧 Запуск Context7-mcp сервера..." -ForegroundColor Cyan
Start-Job -ScriptBlock { npx -y @upstash/context7-mcp@latest } -Name "context7"

Write-Host "🔧 Запуск Playwright-mcp сервера..." -ForegroundColor Cyan
Start-Job -ScriptBlock { npx @playwright/mcp@latest } -Name "playwright"

Write-Host "🔧 Запуск Memento-protocol сервера..." -ForegroundColor Cyan
Start-Job -ScriptBlock { 
    Set-Location "D:\GitHub\mcp-servers\memento-protocol"
    npm start 
} -Name "memento"

Write-Host "`n✅ Все серверы запущены в фоновых задачах" -ForegroundColor Green
Write-Host "Проверка статуса: Get-Job" -ForegroundColor Gray
Write-Host "Остановка: Stop-Job -Name <server-name>" -ForegroundColor Gray