# Deployment - Деплой и инфраструктура

## 🚨 КРИТИЧНО: НЕТ ЛОКАЛЬНОГО СЕРВЕРА!

У нас **НЕТ** локального PHP сервера! Все API запросы идут через **Vite proxy** на удалённый сервер BeGet.

---

## Структура серверов

### Локальная разработка
- **URL:** http://localhost:5173
- **Сервер:** Vite dev server
- **Frontend:** работает локально
- **API:** проксируются на BeGet

### Продакшн
- **URL:** http://марибулька.рф (http://xn--80aac1alfd7a3a5g.xn--p1ai)
- **Сервер:** BeGet shared hosting
- **SSH:** sava7424@sava7424.beget.tech
- **Путь:** /home/s/sava7424/maribulka.rf/maribulka-vue/dist/

---

## Vite Proxy

**Файл:** vite.config.ts

```typescript
server: {
  proxy: {
    '/api': {
      target: 'http://xn--80aac1alfd7a3a5g.xn--p1ai',
      changeOrigin: true,
      secure: false
    }
  }
}
```

---

## Процедура деплоя (2 шага ТОЛЬКО!)

### ⚠️ КРИТИЧНО: ДЕПЛОЙ ТОЛЬКО ЧЕРЕЗ GITHUB ACTIONS!

**Файлы deploy.ps1 и deploy.sh исключительно для личного использования владельца проекта.**

### 1. Коммит
```bash
git add .
git commit -m "описание изменений"
```

### 2. Пуш в GitHub (автодеплой!)
```bash
git push
```

**GitHub Actions автоматически:**
1. Собирает Vue проект (`npm run build`)
2. Загружает `dist/` на сервер через rsync
3. Загружает `api/` на сервер
4. **Создаёт симлинк с абсолютными путями:**
   ```bash
   rm -f /home/s/sava7424/maribulka.rf/public_html/media
   ln -s /home/s/sava7424/maribulka.rf/media /home/s/sava7424/maribulka.rf/public_html/media
   ```

---

## База данных (на BeGet)

| Параметр | Значение |
|----------|----------|
| Host | localhost (**НА СЕРВЕРЕ!**) |
| Database | sava7424_mari |
| User | sava7424_mari |
| Password | Zxc456Siti |

**Важно:** Пароль хранится в `.env` на сервере, **НЕ** в репозитории!

---

## Медиа-файлы (фото, изображения)

### 🚨 КРИТИЧНО: Медиа НЕ в dist!

Медиа хранятся в постоянной директории **вне dist/** чтобы не пересоздаваться при каждом деплое.

### Структура

```
/home/s/sava7424/maribulka.rf/media/
  ├── home/           # Фото для главной страницы
  └── feed/           # (будущее) Фото для ленты работ
```

### URL доступа
```
https://марибулька.рф/media/home/studio_123.jpg
```

### API загрузки

**Файл:** `api/studio_photos.php`

**Автоматическая обработка:**
- Загрузка в `/home/s/sava7424/maribulka.rf/media/home/`
- Сжатие фото (макс 1920x1080, качество 85%)
- Сохранение пути в БД как `/media/home/filename.jpg`
- Форматы: JPG, PNG, WEBP
- Размер: макс 5 МБ

### Симлинк (ВАЖНО!)

В `dist/` создаётся симлинк:
```
dist/media -> ../../media
```

**Зачем:**
- Nginx видит файлы по URL `/media/home/...`
- Медиа сохраняются между деплоями
- Не нужно копировать фото после билда

**Создание:** автоматически в deploy.ps1 (строка 84)

---

## Подключение к серверу BeGet

### ⚠️ КРИТИЧНО: Unix-стиль путь к ключу!

**SSH подключение (ТОЛЬКО так!):**
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
```

**SCP команды (ТОЛЬКО так!):**
```bash
scp -i ~/.ssh/beget_maribulka -r файлы/* sava7424@sava7424.beget.tech:/путь/
```

**❌ НЕ ИСПОЛЬЗОВАТЬ:**
```
C:\Users\sava\.ssh\beget_maribulka  # НЕ работает в Git Bash!
```

---

## Быстрые команды для анализа

```bash
# Структура проекта на сервере
ls -la /home/s/sava7424/maribulka.rf/

# База данных - количество записей
mysql -u sava7424_mari -p'Zxc456Siti' -e "SELECT COUNT(*) FROM bookings" sava7424_mari

# Логи ошибок (последние 5 строк)
tail -5 /home/s/sava7424/maribulka.rf/xn--80aac1alfd7a3a5g.xn--p1ai.error.log

# Проверка API файлов
ls -la /home/s/sava7424/maribulka.rf/maribulka-vue/dist/api/

# Мониторинг сайта (HTTP код ответа)
curl -s -o /dev/null -w "%{http_code}" http://марибулька.рф/
```

---

## Таблица данных для подключения

| Параметр | Значение |
|----------|----------|
| **SSH Host** | sava7424.beget.tech |
| **SSH User** | sava7424 |
| **SSH Key** | ~/.ssh/beget_maribulka |
| **DB Host** | localhost (НА СЕРВЕРЕ!) |
| **DB Name** | sava7424_mari |
| **DB User** | sava7424_mari |
| **DB Pass** | Zxc456Siti |
| **Сайт** | http://марибулька.рф |

---

## GitHub Actions

**Файл:** `.github/workflows/deploy.yml`

**Исправления (13.02.2026):**
- Проверка сайта: **HTTPS** вместо HTTP (редирект 301 → 200)
- Проверка API: **HTTPS** вместо HTTP
- PowerShell скрипт проверяет HTTPS с `-MaximumRedirection 5`
