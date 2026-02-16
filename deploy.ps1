# ==========================================
# 🚀 PowerShell скрипт автодеплоя на Beget
# ==========================================

$ErrorActionPreference = "Stop"

# Конфигурация
$SSH_KEY = "$env:USERPROFILE\.ssh\beget_maribulka"
$SSH_HOST = "sava7424@sava7424.beget.tech"
$REMOTE_PATH = "/home/s/sava7424/maribulka.rf/maribulka-vue/dist"
$LOCAL_BUILD_PATH = "maribulka-vue\dist"
$LOCAL_API_PATH = "api"

Write-Host "==========================================" -ForegroundColor Blue
Write-Host "🚀 Начинаем деплой на Beget" -ForegroundColor Blue
Write-Host "==========================================" -ForegroundColor Blue
Write-Host ""

# 1. Проверка Git статуса
Write-Host "📋 Проверка Git статуса..." -ForegroundColor Yellow
$gitStatus = git status -s
if ($gitStatus) {
    Write-Host "⚠️  Есть несохраненные изменения!" -ForegroundColor Red
    git status -s
    $continue = Read-Host "Продолжить деплой? (y/n)"
    if ($continue -ne "y") {
        Write-Host "❌ Деплой отменен" -ForegroundColor Red
        exit 1
    }
}
Write-Host "✅ Git статус проверен" -ForegroundColor Green
Write-Host ""

# 2. Сборка Vue проекта
Write-Host "🏗️  Сборка Vue проекта..." -ForegroundColor Yellow
Set-Location maribulka-vue
npm run build
Set-Location ..
Write-Host "✅ Сборка завершена" -ForegroundColor Green
Write-Host ""

# 3. Создание бэкапа на сервере
Write-Host "💾 Создание бэкапа на сервере..." -ForegroundColor Yellow
$BACKUP_NAME = "dist_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
ssh -i $SSH_KEY $SSH_HOST "cd /home/s/sava7424/maribulka.rf/maribulka-vue && cp -r dist $BACKUP_NAME && echo 'Бэкап создан: $BACKUP_NAME'"
Write-Host "✅ Бэкап создан" -ForegroundColor Green
Write-Host ""

# 4. Загрузка dist на сервер
Write-Host "📤 Загрузка фронтенда (dist)..." -ForegroundColor Yellow
# Используем Git Bash rsync, если доступен
if (Get-Command "C:\Program Files\Git\usr\bin\rsync.exe" -ErrorAction SilentlyContinue) {
    & "C:\Program Files\Git\usr\bin\rsync.exe" -avz --delete `
        --exclude 'api' `
        -e "ssh -i $SSH_KEY" `
        "$LOCAL_BUILD_PATH/" `
        "${SSH_HOST}:${REMOTE_PATH}/"
} else {
    # Альтернатива: используем scp
    scp -i $SSH_KEY -r "$LOCAL_BUILD_PATH\*" "${SSH_HOST}:${REMOTE_PATH}/"
}
Write-Host "✅ Фронтенд загружен" -ForegroundColor Green
Write-Host ""

# 5. Загрузка API на сервер
Write-Host "📤 Загрузка API (PHP)..." -ForegroundColor Yellow
if (Test-Path $LOCAL_API_PATH) {
    if (Get-Command "C:\Program Files\Git\usr\bin\rsync.exe" -ErrorAction SilentlyContinue) {
        & "C:\Program Files\Git\usr\bin\rsync.exe" -avz `
            -e "ssh -i $SSH_KEY" `
            "$LOCAL_API_PATH/" `
            "${SSH_HOST}:${REMOTE_PATH}/api/"
    } else {
        scp -i $SSH_KEY -r "$LOCAL_API_PATH\*" "${SSH_HOST}:${REMOTE_PATH}/api/"
    }
    Write-Host "✅ API загружен" -ForegroundColor Green
} else {
    Write-Host "⚠️  Папка api не найдена, пропускаем" -ForegroundColor Yellow
}
Write-Host ""

# 6. Создание симлинка для media
Write-Host "🔗 Создание симлинка для media..." -ForegroundColor Yellow
ssh -i $SSH_KEY $SSH_HOST "cd $REMOTE_PATH && rm -f media && ln -s ../../media media && echo 'Симлинк media создан'"
Write-Host "✅ Симлинк создан" -ForegroundColor Green
Write-Host ""

# 7. Проверка деплоя
Write-Host "🔍 Проверка деплоя..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://xn--80aac1alfd7a3a5g.xn--p1ai/" -UseBasicParsing
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ Сайт доступен (HTTP $($response.StatusCode))" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ Сайт недоступен" -ForegroundColor Red
}
Write-Host ""

# 8. Финальная информация
Write-Host "==========================================" -ForegroundColor Blue
Write-Host "✨ Деплой успешно завершен!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Blue
Write-Host ""
Write-Host "🌐 Сайт: " -NoNewline
Write-Host "http://марибулька.рф" -ForegroundColor Blue
Write-Host "📁 Бэкап: " -NoNewline
Write-Host $BACKUP_NAME -ForegroundColor Yellow
Write-Host "⏰ Время: " -NoNewline
Write-Host "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Yellow
Write-Host ""
Write-Host "🎉 Готово!" -ForegroundColor Green
