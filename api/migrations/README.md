# Миграции базы данных

## Как применить миграцию

### Способ 1: Через phpMyAdmin (BeGet)

1. Зайти на [phpMyAdmin BeGet](https://cp.beget.com/mysql)
2. Выбрать БД `sava7424_mari`
3. Перейти на вкладку "SQL"
4. Скопировать содержимое файла `migration_business_process.sql`
5. Вставить в поле SQL запроса
6. Нажать "Выполнить"

### Способ 2: Через SSH

```bash
# Подключиться к серверу
ssh sava7424@sava7424.beget.tech -i ~/.ssh/beget_maribulka

# Перейти в папку миграций
cd /home/s/sava7424/maribulka.rf/public_html/api/migrations

# Выполнить миграцию
mysql -u sava7424_mari -p sava7424_mari < migration_business_process.sql
```

---

## Миграции в проекте

### migration_business_process.sql (22.02.2026)

**Что делает:**
- Добавляет 8 новых статусов заказа
- Добавляет блокировку редактирования (`is_locked`)
- Добавляет связь возвратов с заказами (`expenses.booking_id`)
- Добавляет типы возвратов (`expenses.refund_type`)
- Добавляет колонки мультипользователя (`created_by`, `updated_by`, `master_id`)
- Создаёт VIEW `v_booking_profit` для расчёта прибыли
- Создаёт таблицу `booking_history` для истории изменений
- Конвертирует старые статусы в новые

**Откат:** `rollback_business_process.sql`

---

## ⚠️ ВАЖНО!

### Перед применением миграции:

1. **Сделать бэкап БД!**
   ```bash
   # Через SSH
   mysqldump -u sava7424_mari -p sava7424_mari > backup_before_migration.sql
   ```

2. **Проверить текущие статусы заказов:**
   ```sql
   SELECT status, COUNT(*) FROM bookings GROUP BY status;
   ```

3. **Убедиться, что нет активных пользователей в системе**

### После применения миграции:

1. Проверить, что миграция прошла успешно:
   ```sql
   -- Проверить статусы
   SELECT status, COUNT(*) FROM bookings GROUP BY status;

   -- Проверить VIEW
   SELECT * FROM v_booking_profit LIMIT 5;

   -- Проверить новые колонки
   SHOW COLUMNS FROM bookings;
   ```

2. Если что-то пошло не так — применить откат:
   ```bash
   mysql -u sava7424_mari -p sava7424_mari < rollback_business_process.sql
   ```

---

## Конвертация старых статусов

| Старый статус | Новый статус | Комментарий |
|---------------|--------------|-------------|
| `new` | `new` | Без изменений |
| `completed` | `completed` | Без изменений |
| `delivered` | `completed` | Автоматическая конвертация |
| `cancelled` | `cancelled_by_client` | Автоматическая конвертация (по умолчанию считаем, что отменил клиент) |
| `failed` | `client_no_show` | Автоматическая конвертация |

---

## Новые статусы

1. **new** - Новый заказ
2. **in_progress** - В работе (после подтверждения съёмки)
3. **completed** - Выполнен (клиент принял полностью)
4. **completed_partially** - Клиент принял частично
5. **not_completed** - Клиент не принял заказ
6. **cancelled_by_photographer** - Отменён фотографом
7. **cancelled_by_client** - Отменён клиентом
8. **client_no_show** - Клиент не пришёл

---

## Контакты

Если возникли проблемы с миграцией — обратитесь к администратору.
