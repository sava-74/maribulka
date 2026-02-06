# 📁 Структура проекта

## Текущая структура (актуальная):

```
maribulka/
├── .github/
│   └── workflows/
│       └── deploy.yml           # 🤖 GitHub Actions CI/CD
│
├── maribulka-vue/               # 🎨 Frontend (Vue 3)
│   ├── src/
│   │   ├── components/          # Vue компоненты
│   │   │   ├── TopBar.vue       # Верхняя панель
│   │   │   ├── SideBar.vue      # Боковое меню
│   │   │   ├── LoginModal.vue   # Модалка логина
│   │   │   └── HelloWorld.vue
│   │   ├── stores/
│   │   │   └── auth.ts          # Pinia store для авторизации
│   │   ├── assets/              # CSS стили
│   │   │   ├── theme.css        # Цветовая схема
│   │   │   ├── buttons.css
│   │   │   ├── topbar.css
│   │   │   ├── sidebar.css
│   │   │   ├── modal.css
│   │   │   └── content.css
│   │   ├── App.vue              # Главный компонент
│   │   ├── main.ts              # Точка входа
│   │   └── style.css
│   ├── public/
│   │   ├── .htaccess            # Apache конфигурация
│   │   ├── img/
│   │   │   └── owner.jpg
│   │   └── vite.svg
│   ├── dist/                    # 📦 Собранные файлы (не в Git)
│   ├── index.html
│   ├── package.json
│   ├── vite.config.ts
│   ├── tsconfig.json
│   └── README.md
│
├── api/                         # 🔧 Backend (PHP)
│   └── login.php                # API endpoint для логина
│
├── .git/                        # Git репозиторий
├── .gitignore                   # Что не коммитить
├── .env.example                 # Пример переменных окружения
│
├── deploy.sh                    # 🚀 Bash скрипт деплоя
├── deploy.ps1                   # 🚀 PowerShell скрипт деплоя
│
├── README.md                    # 📖 Главная документация
├── QUICK_START.md               # ⚡ Шпаргалка
├── DEPLOY_GUIDE.md              # 📘 Гайд по деплою
├── GITHUB_ACTIONS_SETUP.md      # 🤖 Настройка CI/CD
└── PROJECT_STRUCTURE.md         # 📁 Этот файл
```

---

## Структура на сервере Beget:

```
/home/s/sava7424/
└── maribulka.rf/
    ├── maribulka-vue/
    │   ├── dist/                    # ← Продакшн (сюда деплоим)
    │   │   ├── api/                 # PHP backend
    │   │   │   └── login.php
    │   │   ├── assets/              # JS/CSS после сборки
    │   │   │   ├── index-*.js
    │   │   │   └── index-*.css
    │   │   ├── img/
    │   │   │   └── owner.jpg
    │   │   ├── .htaccess            # Apache конфигурация
    │   │   └── index.html           # Собранный HTML
    │   │
    │   ├── dist_backup_*/           # Автобэкапы при деплое
    │   └── ...
    │
    ├── public_html -> maribulka-vue/dist  # Симлинк (DocumentRoot)
    │
    └── *.log                        # Логи Apache
```

---

## Что где находится:

### Разработка (локально)
- **Исходный код:** `maribulka-vue/src/`
- **Разработка:** `npm run dev` → http://localhost:5173
- **Сборка:** `npm run build` → создаёт `maribulka-vue/dist/`

### Backend (PHP)
- **Локально:** `api/login.php`
- **На сервере:** `maribulka.rf/maribulka-vue/dist/api/login.php`
- **База данных:** MySQL 8.0 (`sava7424_mari`)

### Продакшн (Beget)
- **Сайт:** http://марибулька.рф
- **DocumentRoot:** `/home/s/sava7424/maribulka.rf/maribulka-vue/dist/`
- **PHP:** 8.4
- **Веб-сервер:** Apache 2.4

---

## Что НЕ хранится в Git:

```
# В .gitignore
node_modules/          # NPM пакеты (npm install восстановит)
dist/                  # Собранные файлы (npm run build создаст)
*.db                   # База данных SQLite (старая, не используется)
.env                   # Пароли и секреты
*.log                  # Логи
```

---

## Что происходит при деплое:

### Ручной деплой (deploy.ps1):
```
1. npm run build                    → создаёт dist/
2. rsync dist/ → сервер             → загружает фронтенд
3. rsync api/ → сервер/dist/api/    → загружает backend
```

### Автодеплой (GitHub Actions):
```
1. git push
2. GitHub Actions:
   ├─ npm ci                        → установка зависимостей
   ├─ npm run build                 → сборка Vue
   ├─ cp api/ → dist/api/           → копирование PHP API
   ├─ sed пароль в login.php        → подстановка DB_PASSWORD
   ├─ ssh создание бэкапа           → dist_backup_*
   └─ rsync → Beget                 → загрузка на сервер
3. Проверка сайта и API
4. Отчёт в GitHub
```

---

## Технологический стек:

### Frontend
- **Framework:** Vue 3.5.24
- **Language:** TypeScript 5.9.3
- **Build:** Vite 7.2.4
- **State:** Pinia 3.0.4
- **Icons:** @jamescoyle/vue-icon + @mdi/light-js

### Backend
- **Language:** PHP 8.4
- **Database:** MySQL 8.0
- **API:** REST JSON

### DevOps
- **Version Control:** Git + GitHub
- **CI/CD:** GitHub Actions
- **Hosting:** Beget shared hosting
- **Web Server:** Apache 2.4.63
- **Deploy:** rsync over SSH

### Design
- **Style:** Glassmorphism
- **Colors:** Neon green (#39FF14), Neon blue (#00F3FF)
- **Layout:** Responsive SPA

---

## История изменений структуры:

### Было (Node.js backend):
```
maribulka/
├── server.js           ❌ Node.js + Express
├── package.json        ❌ Node.js зависимости
├── maribulka.db        ❌ SQLite база
└── maribulka-vue/
```

### Стало (PHP backend):
```
maribulka/
├── api/                ✅ PHP backend
│   └── login.php
├── maribulka-vue/      ✅ Vue frontend
│   └── public/
│       └── .htaccess   ✅ Apache конфигурация
└── .github/            ✅ CI/CD
```

**Причина:** На Beget shared хостинге Node.js работает нестабильно через Passenger, а PHP + MySQL - нативная поддержка.

---

## Файлы конфигурации:

| Файл | Назначение |
|------|-----------|
| `.htaccess` | Apache rewrite правила для SPA |
| `vite.config.ts` | Vite сборщик Vue |
| `tsconfig.json` | TypeScript конфигурация |
| `.gitignore` | Исключения для Git |
| `.env.example` | Шаблон переменных окружения |
| `deploy.yml` | GitHub Actions workflow |

---

## Полезные пути:

### SSH подключение:
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
```

### Директории на сервере:
```bash
# Продакшн
cd /home/s/sava7424/maribulka.rf/maribulka-vue/dist

# Логи
cd /home/s/sava7424/maribulka.rf
tail -f *.error.log
```

### Локальные команды:
```bash
# Разработка
cd maribulka-vue && npm run dev

# Сборка
cd maribulka-vue && npm run build

# Деплой
./deploy.ps1  # Windows
./deploy.sh   # Linux/Mac
```

---

**Документация обновлена:** 2026-02-06
