# Maribulka — Проектная документация

## 📋 Обзор проекта

**Maribulka** — это fullstack веб-приложение для управления фотостудией (бухгалтерия, запись клиентов, управление заказами). Развёрнуто на хостинге Beget.

- **Сайт:** http://марибулька.рф (http://xn--80aac1alfd7a3a5g.xn--p1ai)
- **Админ-панель:** login: `admin` / password: `123`
- **Архитектура:** Vue 3 SPA + PHP REST API + MySQL

---

## 🛠️ Технологический стек

### Frontend
| Технология | Версия | Назначение |
|------------|--------|------------|
| Vue | 3.5.24 | Основной фреймворк |
| TypeScript | ~5.9.3 | Типизация |
| Vite | 7.2.4 | Сборщик |
| Pinia | 2.3.0 | State management |
| FullCalendar | 6.1.20 | Календарь записей |
| Chart.js | 4.5.1 | Графики и статистика |
| CKEditor5 | 47.6.0 | WYSIWYG редактор |
| TanStack Table | 8.21.3 | Таблицы данных |
| @mdi/js | 7.4.47 | Иконки Material Design |

### Backend
| Технология | Версия | Назначение |
|------------|--------|------------|
| PHP | 8.4 | API endpoints |
| MySQL | 8.0 | База данных |
| PDO | — | Работа с БД |

### DevOps
| Инструмент | Назначение |
|------------|------------|
| GitHub Actions | CI/CD (автодеплой при push) |
| rsync + SSH | Деплой файлов на Beget |
| Apache 2.4 | Веб-сервер на хостинге |

---

## 📁 Структура проекта

```
maribulka/
├── .github/workflows/
│   └── deploy.yml           # CI/CD workflow (автодеплой)
│
├── maribulka-vue/           # Frontend приложение
│   ├── src/
│   │   ├── components/      # Vue компоненты
│   │   │   ├── TopBar.vue
│   │   │   ├── SideBar.vue
│   │   │   ├── LoginModal.vue
│   │   │   ├── ValidAlertModal.vue
│   │   │   └── ...
│   │   ├── stores/          # Pinia stores
│   │   │   └── auth.ts
│   │   ├── composables/     # Vue composables
│   │   │   └── useGenie.ts
│   │   ├── assets/          # CSS стили
│   │   │   ├── validAlertModal.css
│   │   │   ├── buttonGlass.css
│   │   │   ├── padGlass.css
│   │   │   ├── modal.css
│   │   │   ├── animations.css
│   │   │   └── ...
│   │   ├── ui/              # UI компоненты
│   │   │   ├── selectBox/
│   │   │   ├── datePicker/
│   │   │   └── searchTable/
│   │   ├── App.vue
│   │   ├── main.ts          # Точка входа
│   │   └── style.css
│   ├── public/              # Статические файлы
│   │   ├── .htaccess        # Apache rewrite rules
│   │   └── img/
│   ├── dist/                # Сборка (не в Git)
│   ├── index.html
│   ├── package.json
│   ├── vite.config.ts
│   └── tsconfig.json
│
├── api/                     # PHP Backend
│   ├── login.php            # Авторизация
│   └── init-database.sql    # Схема БД
│
├── .env                     # Переменные окружения (не в Git)
├── .env.example             # Шаблон переменных
├── .gitignore
├── deploy.ps1               # PowerShell скрипт деплоя
├── deploy.sh                # Bash скрипт деплоя
├── README.md
├── QUICK_START.md
├── PROJECT_STRUCTURE.md
└── DEPLOY_GUIDE.md
```

---

## 🚀 Сборка и запуск

### Установка зависимостей
```bash
cd maribulka-vue
npm install
```

### Локальная разработка
```bash
cd maribulka-vue
npm run dev
```
Сайт доступен на **http://localhost:5173**

API запросы проксируются на продакшн сервер (настроено в `vite.config.ts`).

### Сборка для продакшена
```bash
cd maribulka-vue
npm run build
```
Собранные файлы создаются в `maribulka-vue/dist/`

### Предпросмотр сборки
```bash
cd maribulka-vue
npm run preview
```

---

## 📦 Деплой

### Автоматический (рекомендуется)
Просто сделайте push в ветку `main` или `master`:
```bash
git add .
git commit -m "feat: описание изменений"
git push
```
GitHub Actions автоматически:
1. Установит зависимости
2. Соберёт Vue проект
3. Скопирует PHP API в `dist/`
4. Создаст бэкап на сервере
5. Загрузит файлы на Beget через rsync
6. Проверит доступность сайта и API

### Полуавтоматический (скрипты)
```powershell
# Windows
.\deploy.ps1

# Linux/Mac
./deploy.sh
```

### Ручной
```bash
# Сборка
cd maribulka-vue && npm run build && cd ..

# Загрузка на сервер
scp -i ~/.ssh/beget_maribulka -r maribulka-vue/dist/* \
  sava7424@sava7424.beget.tech:/home/s/sava7424/maribulka.rf/maribulka-vue/dist/
```

---

## 🔧 Конфигурация

### Переменные окружения (.env)
```env
# Database
DB_HOST=localhost
DB_NAME=sava7424_mari
DB_USER=sava7424_mari
DB_PASS=Zxc456Siti

# API
API_BASE_URL=/api

# SSH
SSH_HOST=sava7424.beget.tech
SSH_USER=sava7424
SSH_PORT=22
SSH_KEY=~/.ssh/beget_maribulka
```

### База данных
- **Host:** localhost
- **Database:** sava7424_mari
- **User:** sava7424_mari
- **Password:** см. `.env`

### SSH подключение к серверу
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
```

---

## 🗄️ База данных

### Основные таблицы
| Таблица | Описание |
|---------|----------|
| `users` | Пользователи системы |
| `user_role` | Роли (admin, superuser, user) |
| `clients` | Клиенты |
| `bookings` | Записи на съёмку |
| `booking_history` | История статусов заказов |
| `income` | Приходы (платежи) |
| `expenses` | Расходы |
| `expense_categories` | Категории расходов |
| `shooting_types` | Типы съёмок |
| `promotions` | Акции и скидки |
| `profession` | Профессии |
| `salary_type` | Типы зарплат |
| `user_permissions` | Права пользователей |
| `home_blocks` | Блоки контента главной страницы |
| `studio_description` | Описание студии |
| `studio_photos` | Фотографии студии |

Полная схема в файле `api/init-database.sql`.

---

## 📐 Архитектура

### Frontend архитектура
- **SPA (Single Page Application)** с роутингом через состояние
- **Pinia** для глобального состояния (авторизация, данные)
- **Компонентный подход** с переиспользуемыми UI компонентами
- **Glassmorphism дизайн** с неоновыми акцентами

### Backend архитектура
- **REST-like API** с JSON ответами
- **CORS** настроен для localhost и продакшн домена
- **Сессионная авторизация** через PHP sessions
- **PDO** для безопасной работы с БД

### Структура API endpoints
| Endpoint | Метод | Описание |
|----------|-------|----------|
| `/api/login.php` | POST | Авторизация пользователя |
| `/api/logout.php` | POST | Выход из системы |

---

## 🎨 Разработка

### Добавление нового компонента
1. Создайте файл в `src/components/НазваниеКомпонента.vue`
2. Используйте `<script setup lang="ts">` для TypeScript
3. Импортируйте стили либо локально, либо в `main.ts` для глобальных

### Добавление стилей
- **Глобальные стили:** добавьте импорт в `src/main.ts`
- **Локальные стили компонента:** импорт в `<script setup>`

Пример:
```vue
<script setup lang="ts">
import './НазваниеКомпонента.css'
</script>
```

### Работа с состоянием (Pinia)
```typescript
// Создание store
import { defineStore } from 'pinia'

export const useMyStore = defineStore('myStore', {
  state: () => ({ count: 0 }),
  actions: {
    increment() { this.count++ }
  }
})

// Использование в компоненте
import { useMyStore } from '@/stores/myStore'
const store = useMyStore()
store.increment()
```

### TypeScript соглашения
- Используйте `interface` для типов пропсов
- Типизируйте все функции и переменные
- Избегайте `any`, используйте неизвестные типы осторожно

---

## 🧪 Тестирование

### Проверка API
```bash
# Проверка авторизации
curl -X POST http://марибулька.рф/api/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"123"}'
```

### Проверка сайта
```bash
curl -I http://марибулька.рф/
```

### Логи на сервере
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
tail -f maribulka.rf/*.error.log
```

---

## 📝 Conventions

### Именование файлов
- **Vue компоненты:** `PascalCase.vue` (например, `ValidAlertModal.vue`)
- **CSS файлы:** `camelCase.css` (например, `validAlertModal.css`)
- **TS/JS файлы:** `camelCase.ts` (например, `useGenie.ts`)

### Структура коммитов
```
feat: добавил новую функцию
fix: исправил ошибку
docs: обновил документацию
style: форматирование, отступы
refactor: рефакторинг кода
test: добавил тесты
chore: обновление зависимостей, настройки
```

### Стиль кода
- 2 пробела для отступов
- Одинарные кавычки в строках
- Точка с запятой в конце инструкций
- Экспортируйте компоненты по умолчанию

---

## 🔐 Безопасность

### Никогда не коммитьте
- Файл `.env` с паролями
- SSH ключи
- Файлы баз данных
- Логи с чувствительной информацией

### GitHub Secrets
Для работы CI/CD необходимы секреты:
- `SSH_PRIVATE_KEY` — приватный SSH ключ для доступа к серверу
- `DB_PASSWORD` — пароль от базы данных

---

## 🆘 Troubleshooting

| Проблема | Решение |
|----------|---------|
| Сайт не обновляется после деплоя | Очистите кеш браузера (Ctrl+F5) |
| API возвращает 401 | Проверьте credentials в `.env` |
| Ошибки сборки TypeScript | Запустите `npm run build` для проверки |
| Конфликты Git | Используйте `git pull --rebase` |
| GitHub Actions failed | Проверьте логи во вкладке Actions на GitHub |
| CSS не применяется | Проверьте импорт в `main.ts` или компоненте |

---

## 📚 Дополнительные ресурсы

- [README.md](README.md) — Общая информация о проекте
- [QUICK_START.md](QUICK_START.md) — Быстрый старт и шпаргалка
- [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md) — Детальная структура
- [DEPLOY_GUIDE.md](DEPLOY_GUIDE.md) — Руководство по деплою
- [GITHUB_ACTIONS_SETUP.md](GITHUB_ACTIONS_SETUP.md) — Настройка CI/CD

---

## 📞 Контакты

- **Хостинг Beget:** https://beget.com/ru/support
- **GitHub Issues:** для багрепортов и предложений

---

*Документация актуальна на: 1 апреля 2026*

## Qwen Added Memories
- Критические правила проекта Maribulka: 1) КАТЕГОРИЧЕСКИ ЗАПРЕЩЕНО писать стили в .vue файлах - только отдельные CSS файлы в assets/ 2) CSS переменные создаются ТОЛЬКО в style.css - все переменные должны быть в одном месте
