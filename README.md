# 🎨 Maribulka - Портфолио сайт

Fullstack веб-приложение на Vue 3 + PHP + MySQL, развернутое на хостинге Beget.

## 🌐 Сайт

- **Продакшн:** http://марибулька.рф (http://xn--80aac1alfd7a3a5g.xn--p1ai)
- **Админ:** login: `admin` / password: `123`

## 🛠️ Технологии

### Frontend
- Vue 3.5 + TypeScript
- Vite 7.2
- Pinia (state management)
- Glassmorphism дизайн

### Backend
- PHP 8.4
- MySQL 8.0

### Хостинг
- Beget shared hosting
- SSH доступ

## 📦 Установка

### 1. Клонировать репозиторий
```bash
git clone <repo-url>
cd maribulka
```

### 2. Установить зависимости
```bash
cd maribulka-vue
npm install
```

### 3. Локальная разработка
```bash
npm run dev
```
Сайт будет доступен на http://localhost:5173

### 4. Сборка для продакшна
```bash
npm run build
```

## 🚀 Деплой на Beget

### Быстрый деплой (Windows)
```powershell
.\deploy.ps1
```

### Быстрый деплой (Linux/Mac)
```bash
chmod +x deploy.sh
./deploy.sh
```

### Ручной деплой
```bash
# 1. Сборка
cd maribulka-vue && npm run build && cd ..

# 2. Загрузка на сервер
scp -i ~/.ssh/beget_maribulka -r maribulka-vue/dist/* sava7424@sava7424.beget.tech:/home/s/sava7424/maribulka.rf/maribulka-vue/dist/
```

## 🔧 Конфигурация

### База данных MySQL
- **Host:** localhost
- **Database:** sava7424_mari
- **User:** sava7424_mari
- **Password:** (в `.env` файле на сервере)

### SSH подключение
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
```

### VSCode Remote SSH
1. Установить расширение "Remote - SSH"
2. F1 → "Remote-SSH: Connect to Host"
3. Выбрать `sava7424@sava7424.beget.tech`
4. Открыть папку `/home/s/sava7424/maribulka.rf`

## 📁 Структура проекта

```
maribulka/
├── maribulka-vue/          # Frontend Vue приложение
│   ├── src/
│   │   ├── components/     # Vue компоненты
│   │   ├── stores/         # Pinia stores
│   │   ├── assets/         # CSS стили
│   │   ├── App.vue
│   │   └── main.ts
│   ├── public/
│   ├── dist/               # Собранные файлы (не в Git)
│   ├── package.json
│   └── vite.config.ts
├── api/                    # PHP Backend (копируется в dist)
│   └── login.php
├── deploy.sh               # Bash скрипт деплоя
├── deploy.ps1              # PowerShell скрипт деплоя
├── .gitignore
└── README.md
```

## 🔐 Безопасность

**⚠️ Важно:**
- Никогда не коммитить пароли БД в Git
- Использовать `.env` для чувствительных данных
- Хранить SSH ключи локально (в `.gitignore`)

## 📝 Workflow разработки

1. **Разработка:**
   ```bash
   cd maribulka-vue
   npm run dev
   # Разрабатываешь в src/
   ```

2. **Коммит изменений:**
   ```bash
   git add .
   git commit -m "feat: добавил новую функцию"
   git push
   ```

3. **Деплой:**
   ```bash
   ./deploy.ps1  # Windows
   ./deploy.sh   # Linux/Mac
   ```

## 🐛 Отладка

### Логи на сервере
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
tail -f maribulka.rf/xn--80aac1alfd7a3a5g.xn--p1ai.error.log
```

### Проверка API
```bash
curl -X POST http://марибулька.рф/api/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"123"}'
```

## 📚 Полезные команды

### Бэкап БД
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
mysqldump -u sava7424_mari -p sava7424_mari > backup.sql
```

### Очистка старых бэкапов
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
cd /home/s/sava7424/maribulka.rf/maribulka-vue
rm -rf dist_backup_*
```

## 📞 Поддержка

- **Beget:** https://beget.com/ru/support
- **Git Issues:** <repo-url>/issues

## 📄 Лицензия

MIT

---


