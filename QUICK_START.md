# ⚡ Quick Start - Шпаргалка

## 🎯 Три способа деплоя

### 1️⃣ Автоматический (через GitHub Actions) ⭐ РЕКОМЕНДУЕТСЯ
```bash
git add .
git commit -m "feat: добавил фичу"
git push
```
**✨ Магия! Всё происходит автоматически на GitHub!**

---

### 2️⃣ Полуавтоматический (скрипт)
```powershell
# Windows
.\deploy.ps1

# Linux/Mac
./deploy.sh
```

---

### 3️⃣ Ручной
```bash
# Сборка
cd maribulka-vue && npm run build && cd ..

# Загрузка
scp -i ~/.ssh/beget_maribulka -r maribulka-vue/dist/* sava7424@sava7424.beget.tech:/home/s/sava7424/maribulka.rf/maribulka-vue/dist/
```

---

## 🔥 Частые команды

### Разработка
```bash
cd maribulka-vue
npm run dev              # Запуск dev сервера
npm run build            # Сборка для продакшена
```

### Git
```bash
git status               # Статус
git add .                # Добавить всё
git commit -m "message"  # Коммит
git push                 # Отправить на GitHub (→ автодеплой!)
git log --oneline        # История коммитов
```

### SSH
```bash
# Подключение
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech

# Быстрые команды
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech "команда"
```

### Проверка сайта
```bash
# Проверка главной
curl http://марибулька.рф/

# Проверка API
curl -X POST http://марибулька.рф/api/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"123"}'
```

---

## ⚡ Быстрый старт для VSCode

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

Готово! Сервер работает. Ошибки типа "EOF while parsing a value" - это нормально.

---

## 📁 Важные пути

### Локально
```
d:\GitHub\maribulka\
├── maribulka-vue/src/      ← Здесь пишешь код
├── api/login.php           ← PHP backend
└── deploy.ps1              ← Скрипт деплоя
```

### На сервере
```
/home/s/sava7424/maribulka.rf/
└── maribulka-vue/
    └── dist/               ← Продакшн файлы
        ├── api/            ← PHP API
        ├── assets/         ← JS/CSS
        └── index.html      ← Фронтенд
```

---

## 🆘 Проблемы и решения

| Проблема | Решение |
|----------|---------|
| Сайт не обновляется | `Ctrl + F5` (очистка кеша) |
| API не работает | Проверь логи: `ssh ... "tail -f *.error.log"` |
| Git конфликт | `git pull --rebase` |
| Скрипт не запускается | `chmod +x deploy.sh` |
| GitHub Actions failed | GitHub → Actions → посмотри лог |

---

## 🎨 Workflow на каждый день

```
Утро:
├─ git pull                    # Получить последние изменения
├─ cd maribulka-vue
├─ npm run dev                 # Запустить dev сервер
│
Работа:
├─ Редактируешь src/...
├─ Проверяешь в браузере
│
Конец дня:
├─ git add .
├─ git commit -m "..."
└─ git push                    # → Автодеплой!
```

---

## 🔐 Важные данные

### MySQL
- Host: `localhost`
- Database: `sava7424_mari`
- User: `sava7424_mari`
- Password: `Zxc456Siti`

### Сайт
- Продакшн: http://марибулька.рф
- Punycode: http://xn--80aac1alfd7a3a5g.xn--p1ai

### SSH
- Host: `sava7424.beget.tech`
- User: `sava7424`
- Key: `~/.ssh/beget_maribulka`

---

## 🎯 Чеклист первой настройки

- [ ] Git инициализирован (`git init`)
- [ ] Создан репозиторий на GitHub
- [ ] Добавлен remote (`git remote add origin ...`)
- [ ] SSH ключ добавлен в GitHub Secrets
- [ ] DB_PASSWORD добавлен в GitHub Secrets
- [ ] Сделан первый push (`git push -u origin main`)
- [ ] GitHub Actions успешно отработал
- [ ] Сайт работает и логин функционирует

---

## 📚 Документация

- [README.md](README.md) - Общая информация
- [DEPLOY_GUIDE.md](DEPLOY_GUIDE.md) - Подробный гайд по деплою
- [GITHUB_ACTIONS_SETUP.md](GITHUB_ACTIONS_SETUP.md) - Настройка GitHub Actions

---

**💡 Совет:** Добавь этот файл в закладки браузера для быстрого доступа!
