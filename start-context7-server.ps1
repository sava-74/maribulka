# Скрипт для запуска Context7-mcp сервера
# Автор: AI Assistant для проекта Maribulka

Write-Host "🚀 Запуск Context7-mcp сервера" -ForegroundColor Green
Write-Host "==============================" -ForegroundColor Green

# Загрузка переменных окружения
$envFile = ".env.context7"
if (Test-Path $envFile) {
    Write-Host "📄 Загрузка конфигурации из $envFile" -ForegroundColor Cyan
    
    Get-Content $envFile | ForEach-Object {
        if ($_ -match "^([^#=]+)=(.*)$") {
            $key = $matches[1].Trim()
            $value = $matches[2].Trim()
            [Environment]::SetEnvironmentVariable($key, $value, "Process")
            Write-Host "  Установлена переменная: $key" -ForegroundColor Gray
        }
    }
} else {
    Write-Host "⚠️ Файл конфигурации $envFile не найден" -ForegroundColor Yellow
}

# Проверка наличия API ключа
$apiKey = [Environment]::GetEnvironmentVariable("UPSTASH_REDIS_REST_TOKEN")
if ($apiKey) {
    Write-Host "✅ API ключ загружен" -ForegroundColor Green
    # Показываем только начало ключа для безопасности
    Write-Host "🔑 Ключ: $($apiKey.Substring(0, [Math]::Min(10, $apiKey.Length)))..." -ForegroundColor Gray
} else {
    Write-Host "❌ API ключ не найден!" -ForegroundColor Red
    Write-Host "Пожалуйста, добавьте UPSTASH_REDIS_REST_TOKEN в файл .env.context7" -ForegroundColor Yellow
    exit 1
}

# Проверка Node.js
try {
    $nodeVersion = node --version
    Write-Host "✅ Node.js: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Node.js не найден. Пожалуйста, установите Node.js" -ForegroundColor Red
    exit 1
}

# Запуск сервера
Write-Host "`n📡 Запуск Context7-mcp сервера..." -ForegroundColor Blue
Write-Host "Команда: npx -y @upstash/context7-mcp@latest" -ForegroundColor Gray

try {
    npx -y @upstash/context7-mcp@latest
} catch {
    Write-Host "❌ Ошибка запуска сервера: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n🏁 Скрипт завершен" -ForegroundColor Green