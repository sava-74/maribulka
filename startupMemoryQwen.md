# Инструкция по запуску MCP-серверов

Этот документ содержит инструкции по запуску MCP-серверов, включая основной сервер basic-memory.

## Требования

Для работы MCP-серверов необходимо установить утилиту `uv`, которая включает в себя `uvx`.

### Установка uv

#### На Windows:
```powershell
powershell -ExecutionPolicy ByPass -c "irm https://astral.sh/uv/install.ps1 | iex"
```

После установки добавьте путь к исполняемым файлам uv в переменную окружения PATH:
```powershell
$env:Path = "$env:USERPROFILE\.local\bin;$env:Path"
```

Для постоянного добавления пути в систему выполните:
```cmd
setx PATH "%PATH%;%USERPROFILE%\.local\bin"
```

#### На macOS и Linux:
```bash
curl -LsSf https://astral.sh/uv/install.sh | sh
```

## Проверка установки

Проверьте, что утилиты установлены правильно:

```bash
uv --version
uvx --version
```

## Запуск основного сервера basic-memory

Для запуска основного MCP-сервера выполните:

```bash
uvx basic-memory mcp
```

## Автоматизация запуска

В соответствии с требованиями проекта, для автоматизации процесса можно создать скрипт запуска.

### PowerShell скрипт (для Windows):

Создайте файл `start-mcp.ps1`:

```powershell
# Проверяем, установлена ли утилита uv
if (Get-Command "uv" -ErrorAction SilentlyContinue) {
    Write-Host "Запуск MCP сервера basic-memory..."
    uvx basic-memory mcp
} else {
    Write-Host "Ошибка: Утилита uv не найдена в системе."
    Write-Host "Пожалуйста, установите uv, выполнив:"
    Write-Host "powershell -ExecutionPolicy ByPass -c `"irm https://astral.sh/uv/install.ps1 | iex`""
    Write-Host "Затем добавьте путь к uv в переменную PATH:"
    Write-Host "setx PATH `"%PATH%;%USERPROFILE%\.local\bin`""
}
```

### Bash скрипт (для macOS/Linux):

Создайте файл `start-mcp.sh`:

```bash
#!/bin/bash

if command -v uv &> /dev/null; then
    echo "Запуск MCP сервера basic-memory..."
    uvx basic-memory mcp
else
    echo "Ошибка: Утилита uv не найдена в системе."
    echo "Пожалуйста, установите uv, выполнив:"
    echo "curl -LsSf https://astral.sh/uv/install.sh | sh"
fi
```

Не забудьте сделать скрипт исполняемым:
```bash
chmod +x start-mcp.sh
```

## Спецификация проекта

Согласно спецификации проекта Maribulka:

- Основной сервер @modelcontextprotocol/basic-memory должен быть доступен как обязательный компонент
- Все остальные MCP-серверы рассматриваются как опциональные
- В случае неработоспособности дополнительных серверов, система должна использовать только основной сервер basic-memory
- Все MCP серверы должны быть установлены через `npm install -g` и работать как постоянные фоновые процессы (через PM2 или системные сервисы), чтобы быть доступными во всех сессиях

## Установка MCP серверов как постоянных фоновых процессов

Для обеспечения постоянной доступности MCP серверов во всех сессиях, рекомендуется установить их как системные службы:

### Установка PM2 для управления процессами:

```bash
npm install -g pm2
pm2 startup
```

### Запуск основного сервера как постоянного процесса:

```bash
pm2 start "uvx basic-memory mcp" --name "mcp-basic-memory"
pm2 save
```

## Простая инструкция для запуска при открытии VSCode

Когда вы открыли проект в VSCode:

1. Откройте integrated terminal: `Ctrl + `` (backtick)
2. Выполните команду:

```bash
uvx basic-memory mcp
```

Если uvx не установлен, сначала выполните:

```powershell
powershell -ExecutionPolicy ByPass -c "irm https://astral.sh/uv/install.ps1 | iex"
$env:Path += ";$env:USERPROFILE\.local\bin"
uvx basic-memory mcp
```

Готово! Сервер работает. Ошибки типа "EOF while parsing a value" - это нормально, это означает, что сервер ожидает подключения клиента.

## Решение типичных проблем

### Проблема: "exec: 'uvx': executable file not found in %PATH%"

Решение:
1. Убедитесь, что утилита `uv` установлена правильно
2. Проверьте, что путь к исполняемым файлам `uv` добавлен в переменную окружения PATH
3. Перезапустите терминал после установки

### Проблема: Сервер не запускается

Решение:
1. Проверьте подключение к интернету
2. Убедитесь, что у вас последние версии утилит
3. Обновите uv: `uv self update`

### Проблема: Сервер запускается, но не принимает соединения

Решение:
Согласно спецификации, это нормальное поведение - сервер может выдавать ошибки типа "EOF while parsing a value", что является нормальным состоянием ожидания подключения клиента, а не сбоем сервиса.