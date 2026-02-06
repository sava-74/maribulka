# 🚀 Гайд по деплою

## Первый деплой (Setup)

### 1. Настройка сервера

**Подключись к серверу:**
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
```

**Создай .env файл на сервере:**
```bash
cd /home/s/sava7424/maribulka.rf/maribulka-vue/dist/api
nano .env
```

**Содержимое .env:**
```env
DB_HOST=localhost
DB_NAME=sava7424_mari
DB_USER=sava7424_mari
DB_PASS=Zxc456Siti
```

**Сохрани:** `Ctrl+O` → `Enter` → `Ctrl+X`

### 2. Обнови login.php на сервере

Отредактируй `/home/s/sava7424/maribulka.rf/maribulka-vue/dist/api/login.php`:

```php
// Вместо хардкода:
$dbpass = 'YOUR_PASSWORD_HERE';

// Используй .env:
$dotenv = parse_ini_file(__DIR__ . '/.env');
$dbpass = $dotenv['DB_PASS'];
```

---

## Регулярный деплой

### Windows (PowerShell)
```powershell
.\deploy.ps1
```

### Linux/Mac (Bash)
```bash
./deploy.sh
```

### Что делает скрипт:
1. ✅ Проверяет Git статус
2. ✅ Собирает Vue проект (`npm run build`)
3. ✅ Создаёт бэкап на сервере
4. ✅ Загружает `dist/` на сервер
5. ✅ Загружает `api/` на сервер
6. ✅ Проверяет доступность сайта

---

## Ручной деплой (если скрипт не работает)

### Шаг 1: Сборка
```bash
cd maribulka-vue
npm run build
cd ..
```

### Шаг 2: Загрузка фронтенда
```bash
scp -i ~/.ssh/beget_maribulka -r maribulka-vue/dist/* sava7424@sava7424.beget.tech:/home/s/sava7424/maribulka.rf/maribulka-vue/dist/
```

### Шаг 3: Загрузка API
```bash
scp -i ~/.ssh/beget_maribulka -r api/* sava7424@sava7424.beget.tech:/home/s/sava7424/maribulka.rf/maribulka-vue/dist/api/
```

---

## Откат изменений

### 1. Восстановление из бэкапа
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech

cd /home/s/sava7424/maribulka.rf/maribulka-vue

# Смотрим доступные бэкапы
ls -la | grep dist_backup

# Восстанавливаем
rm -rf dist
cp -r dist_backup_YYYYMMDD_HHMMSS dist
```

### 2. Откат через Git
```bash
git log --oneline
git revert <commit-hash>
./deploy.ps1
```

---

## Проверка деплоя

### 1. Проверка сайта
```bash
curl http://марибулька.рф/
```

### 2. Проверка API
```bash
curl -X POST http://марибулька.рф/api/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"123"}'
```

**Ожидаемый результат:**
```json
{"success":true}
```

### 3. Проверка логов
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
tail -f maribulka.rf/xn--80aac1alfd7a3a5g.xn--p1ai.error.log
```

---

## Troubleshooting

### Проблема: "Permission denied"
```bash
chmod +x deploy.sh
```

### Проблема: "rsync: command not found" (Windows)
Установи Git для Windows, который включает rsync:
https://git-scm.com/download/win

### Проблема: "API возвращает 500"
1. Проверь логи на сервере
2. Убедись, что пароль БД правильный в `.env`
3. Проверь версию PHP (должна быть 8.x)

### Проблема: "Сайт отдаёт старую версию"
Очисти кеш браузера: `Ctrl + F5`

---

## Безопасность

**⚠️ Важно:**
- Никогда не коммить `.env` в Git
- Никогда не коммить пароли
- Регулярно делай бэкапы БД
- Храни SSH ключи в безопасном месте

---

## Полезные команды

### Просмотр структуры на сервере
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
tree -L 3 maribulka.rf/
```

### Бэкап базы данных
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
mysqldump -u sava7424_mari -p sava7424_mari > ~/backup_$(date +%Y%m%d).sql
```

### Мониторинг в реальном времени
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
watch -n 2 'curl -s -o /dev/null -w "%{http_code}" http://марибулька.рф/'
```

---

**Готово! Теперь у тебя профессиональный workflow! 🚀**
