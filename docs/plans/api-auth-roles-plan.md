# План: Авторизация и ролевой доступ в API

**Дата:** 3 апреля 2026  
**Приоритет:** Критический (P1)  
**Статус:** На утверждение

---

## Проблема

Старые API (`bookings.php`, `income.php`, `expenses.php`) созданы **до** системы пользователей и ролей. Они:
- Не используют `session.php` — сессия не читается
- Имеют `Access-Control-Allow-Origin: *` — несовместимо с cookie-аутентификацией
- Открыты для любого запроса без авторизации

Плюс `session.php` имеет `secure: true` — ломает сессии на localhost (HTTP).

---

## Матрица доступа (из блок-схемы прав)

| Раздел | admin (1) | superUser (2) | superUser1 (3) | AUser (4) | proUser (5) | User (6) |
|--------|:---------:|:-------------:|:--------------:|:---------:|:-----------:|:--------:|
| Календарь (GET) | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Записи (POST/PUT) | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Записи (DELETE) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Оплата (payment) | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Отмена (cancel) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Доходы | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Расходы | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Клиенты (GET) | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Клиенты (POST/PUT/DELETE) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Справочники (GET) | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Справочники (POST/PUT/DELETE) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |

---

## Этапы реализации

### Этап 1 — `api/session.php`: исправить secure + добавить requireRole()

**Файл:** `api/session.php`

**Изменение 1** — условный `secure`/`samesite`:
```php
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => $isHttps ? 'None' : 'Lax',
        ]);
        session_start();
    }
}
```

**Изменение 2** — добавить после `initSession()`:
```php
/**
 * Проверяет аутентификацию и роль.
 * При провале — JSON-ошибка + exit.
 * @return int  role id текущего пользователя
 */
function requireRole(int ...$allowedRoles): int {
    initSession();

    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $role = (int)($_SESSION['user']['role'] ?? 0);

    if (!empty($allowedRoles) && !in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    return $role;
}
```

**Риск:** нулевой. На продакшне (HTTPS) значения остаются теми же.

---

### Этап 2 — `api/income.php` и `api/expenses.php`: CORS + requireRole

**Изменение в обоих файлах** — заменить блок заголовков и добавить auth:

```php
// Заменить:
header('Access-Control-Allow-Origin: *');

// На (по образцу clients.php):
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'http://localhost:5173',
    'https://xn--80aac1alfd7a3a5g.xn--p1ai',
    'http://xn--80aac1alfd7a3a5g.xn--p1ai'
];
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
```

После OPTIONS-блока, до `require_once 'database.php'`:
```php
require_once 'session.php';
requireRole(1, 2, 3);
```

**Риск:** низкий. Только финансовые разделы.

---

### Этап 3 — `api/bookings.php`: CORS + requireRole + гранулярные проверки

**Структура файла после изменений:**
```
1. header() — Content-Type
2. CORS-блок (explicit origins + credentials)
3. OPTIONS → exit(0)          ← OPTIONS ВСЕГДА ДО requireRole!
4. require_once 'session.php'
5. require_once 'database.php'
6. $currentRole = requireRole(1, 2, 3, 4, 5)
7. Флаги ролей:
   $canFull   = in_array($currentRole, [1, 2, 3], true)
   $canAdmin  = in_array($currentRole, [1, 2, 3, 4], true)
8. Основная логика с проверками внутри
```

**Гранулярные проверки внутри обработчиков:**

| Action / метод | Проверка |
|---|---|
| GET (все) | разрешено (requireRole уже отсеял 6+анон) |
| POST создать запись | `if (!$canAdmin) → 403` |
| PUT обновить запись | `if (!$canAdmin) → 403` |
| DELETE | `if (!$canFull) → 403` |
| action=payment, quick_payment | `if (!$canAdmin) → 403` |
| action=confirm_session | разрешено всем (роли 1-5) |
| action=complete_order | `if (!$canAdmin) → 403` |
| action=cancel | `if (!$canFull) → 403` |

**Риск:** средний. Рекомендуется сначала только `requireRole(1,2,3,4,5)` без гранулярных, проверить, потом добавить гранулярность.

---

### Этап 4 — Frontend `stores/bookings.ts` и `stores/finance.ts`: credentials

Добавить `credentials: 'include'` во все `fetch()` вызовы в обоих store.

```typescript
// Было:
const response = await fetch('/api/bookings.php')

// Стало:
const response = await fetch('/api/bookings.php', { credentials: 'include' })
```

Для POST/PUT/DELETE — добавить в существующий объект options.

**Важно:** делать только ПОСЛЕ этапов 2-3, иначе браузер отклонит запросы из-за `Allow-Origin: *`.

**Обработка ошибок** — добавить в fetch-обработчики:
```typescript
if (response.status === 401) {
    useAuthStore().logout()
    return
}
if (response.status === 403) {
    console.warn('Доступ запрещён')
    return
}
```

**Риск:** низкий.

---

### Этап 5 — `stores/permissions.ts`: исправить ROLE_DEFAULTS для роли 3

В `ROLE_DEFAULTS[3]` (superUser1/Руководитель) исправить `income` и `expenses` на `'FULL'` — сейчас там `'NO'`, что скрывает финансовые разделы на фронтенде, хотя бэкенд их разрешает.

**Риск:** низкий.

---

## Итоговый чеклист

| # | Файл | Изменение | Риск |
|---|---|---|---|
| 1 | `api/session.php` | Условный `secure`/`samesite` | Нулевой |
| 2 | `api/session.php` | Добавить `requireRole()` | Нулевой |
| 3 | `api/income.php` | CORS + `requireRole(1,2,3)` | Низкий |
| 4 | `api/expenses.php` | CORS + `requireRole(1,2,3)` | Низкий |
| 5 | `api/bookings.php` | CORS + `requireRole(1-5)` + гранулярные проверки | Средний |
| 6 | `src/stores/bookings.ts` | `credentials: 'include'` + 401/403 | Низкий |
| 7 | `src/stores/finance.ts` | `credentials: 'include'` + 401/403 | Низкий |
| 8 | `src/stores/permissions.ts` | ROLE_DEFAULTS[3]: income/expenses → FULL | Низкий |

---

## Критические замечания

1. **OPTIONS preflight** — всегда до `requireRole`, иначе браузер получит 401 на preflight и заблокирует все CORS-запросы
2. **`income.php` не вызывается из `bookings.php`** — операции payment/quick_payment пишут в таблицу `income` напрямую через `$db`, не через API. Защита `income.php` не ломает эти операции
3. **Vite proxy** — `cookieDomainRewrite: 'localhost'` уже настроен. После исправления `session.php` сессионное cookie будет корректно переписано на localhost

---

*План создан: 3 апреля 2026*
