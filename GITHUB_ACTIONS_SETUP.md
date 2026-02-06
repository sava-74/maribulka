# 🤖 Настройка GitHub Actions для автодеплоя

## 📋 Что это даст:

**Push в GitHub → Автоматическая сборка → Автодеплой на Beget**

- ✅ Автоматическая сборка при каждом push
- ✅ Автоматическое создание бэкапа
- ✅ Автоматическая загрузка на сервер
- ✅ Проверка работоспособности сайта и API
- ✅ Красивый отчёт о деплое
- ✅ Уведомления при ошибках

---

## 🔧 Пошаговая настройка

### Шаг 1: Добавь SSH ключ в GitHub Secrets

**1.1. Скопируй свой SSH приватный ключ:**

**Windows (PowerShell):**
```powershell
Get-Content $env:USERPROFILE\.ssh\beget_maribulka | Set-Clipboard
# Ключ скопирован в буфер обмена!
```

**Linux/Mac:**
```bash
cat ~/.ssh/beget_maribulka | pbcopy  # Mac
cat ~/.ssh/beget_maribulka | xclip   # Linux
```

**Или просто открой файл:**
```powershell
notepad $env:USERPROFILE\.ssh\beget_maribulka
```

**1.2. Добавь в GitHub:**

1. Открой свой репозиторий на GitHub
2. Перейди: **Settings** → **Secrets and variables** → **Actions**
3. Нажми **"New repository secret"**
4. Имя: `SSH_PRIVATE_KEY`
5. Вставь содержимое ключа (весь текст, включая `-----BEGIN OPENSSH PRIVATE KEY-----`)
6. **Add secret**

---

### Шаг 2: Добавь пароль БД в GitHub Secrets

**2.1. В том же разделе Secrets создай ещё один:**

1. Нажми **"New repository secret"**
2. Имя: `DB_PASSWORD`
3. Значение: `Zxc456Siti`
4. **Add secret**

---

### Шаг 3: Измени login.php для продакшена

**На СЕРВЕРЕ** (через SSH или VSCode Remote):

Отредактируй `/home/s/sava7424/maribulka.rf/maribulka-vue/dist/api/login.php`:

**Было:**
```php
$dbpass = 'Zxc456Siti';
```

**Должно быть:**
```php
$dbpass = 'YOUR_PASSWORD_HERE';  // Плейсхолдер для GitHub Actions
```

**Или используй .env файл (лучше):**
```php
// Если файл .env существует, берём из него
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = parse_ini_file(__DIR__ . '/.env');
    $dbpass = $dotenv['DB_PASS'];
} else {
    $dbpass = 'YOUR_PASSWORD_HERE';  // Fallback для GitHub Actions
}
```

---

### Шаг 4: Запуш всё в GitHub

```bash
cd d:/GitHub/maribulka

# Инициализируй Git (если ещё не сделал)
git init

# Добавь remote
git remote add origin https://github.com/<твой-username>/maribulka.git

# Коммит
git add .
git commit -m "feat: add GitHub Actions auto-deploy"

# Первый push
git branch -M main
git push -u origin main
```

**🎉 Всё! Теперь при каждом push будет автоматический деплой!**

---

## 🚀 Как использовать

### Обычная разработка:

```bash
# 1. Пишешь код
cd maribulka-vue/src
# ... редактируешь файлы

# 2. Коммитишь и пушишь
git add .
git commit -m "feat: добавил новую фичу"
git push

# 3. GitHub Actions АВТОМАТИЧЕСКИ:
#    ✅ Соберёт проект
#    ✅ Создаст бэкап
#    ✅ Задеплоит на Beget
#    ✅ Проверит работоспособность
```

### Ручной запуск деплоя:

1. Открой GitHub → **Actions** → **Deploy to Beget**
2. Нажми **"Run workflow"** → **"Run workflow"**
3. Смотри лог в реальном времени!

---

## 📊 Просмотр результатов

### После каждого push:

1. Открой GitHub → **Actions**
2. Увидишь зелёную галочку ✅ или красный крестик ❌
3. Кликни на workflow для детального лога
4. Внизу будет **красивый отчёт** о деплое!

**Пример отчёта:**
```
## ✨ Deployment Successful!

🌐 Site: http://марибулька.рф
⏰ Time: 2026-02-06 12:34:56
📝 Commit: abc123def

### Tests Passed:
- ✅ Site is accessible
- ✅ API is working
```

---

## 🎯 Продвинутые настройки

### Деплой только на production ветку:

Измени `.github/workflows/deploy.yml`:

```yaml
on:
  push:
    branches: [ production ]  # Только production ветка
```

**Тогда workflow:**
```bash
# Разработка в main
git checkout main
git push  # ❌ Не деплоится

# Деплой в production
git checkout production
git merge main
git push  # ✅ Автодеплой!
```

### Деплой по расписанию:

```yaml
on:
  schedule:
    - cron: '0 3 * * *'  # Каждый день в 3 ночи
```

### Деплой с подтверждением:

```yaml
on:
  workflow_dispatch:  # Только ручной запуск
    inputs:
      environment:
        description: 'Deployment environment'
        required: true
        type: choice
        options:
          - production
          - staging
```

---

## 🔐 Безопасность

**✅ Что в GitHub Secrets:**
- `SSH_PRIVATE_KEY` - приватный ключ SSH
- `DB_PASSWORD` - пароль от БД

**✅ Что НЕ в Git:**
- `.env` (в `.gitignore`)
- `node_modules/` (в `.gitignore`)
- `dist/` (в `.gitignore`)

**⚠️ Важно:**
- Никогда не коммить пароли в открытом виде
- Использовать GitHub Secrets для чувствительных данных
- Регулярно ротировать SSH ключи

---

## 🐛 Troubleshooting

### Ошибка: "Permission denied (publickey)"

**Решение:**
1. Проверь, что SSH ключ правильно скопирован в GitHub Secrets
2. Убедись, что ключ имеет правильный формат (включая заголовки)
3. Проверь права доступа на сервере

### Ошибка: "rsync: command not found"

**Решение:**
GitHub Actions runner уже имеет rsync. Если ошибка - проверь синтаксис команды.

### Workflow не запускается

**Решение:**
1. Проверь название ветки (`main` или `master`)
2. Проверь синтаксис YAML файла
3. Посмотри GitHub Actions → Вкладка "All workflows"

---

## 📈 Мониторинг

### Получение уведомлений:

**Email:**
GitHub → Settings → Notifications → Actions → ✅ Enable

**Telegram бот (продвинуто):**
Добавь в workflow:
```yaml
- name: Send Telegram notification
  if: always()
  uses: appleboy/telegram-action@master
  with:
    to: ${{ secrets.TELEGRAM_TO }}
    token: ${{ secrets.TELEGRAM_TOKEN }}
    message: |
      🚀 Deploy ${{ job.status }}
      Commit: ${{ github.sha }}
      Site: http://марибулька.рф
```

---

## 🎉 Готово!

Теперь у тебя **полностью автоматизированный CI/CD pipeline**!

**Просто делай `git push` и всё остальное произойдёт автоматически! 🚀**

---

**Полезные ссылки:**
- [GitHub Actions документация](https://docs.github.com/en/actions)
- [GitHub Secrets guide](https://docs.github.com/en/actions/security-guides/encrypted-secrets)
