-- 006_seed_permanent_users.sql
-- Миграция: создание вечных пользователей admin и superuser
-- Пароль по умолчанию: 123 (bcrypt hash)
-- При первом входе система потребует сменить пароль (mustChangePassword)

-- Таблица profession (если ещё не создана)
CREATE TABLE IF NOT EXISTS profession (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  notes TEXT
);

-- Базовые профессии
INSERT IGNORE INTO profession (id, title, active) VALUES
  (1, 'СисАдмин', 1),
  (2, 'Директор', 1),
  (3, 'Руководитель', 1),
  (4, 'Администратор', 1),
  (5, 'Фотограф', 1),
  (6, 'Парикмахер', 1);

-- Обновить ENUM роли — добавить superuser1
ALTER TABLE users MODIFY COLUMN role ENUM('admin','superuser','superuser1','auser','prouser','user') NOT NULL DEFAULT 'prouser';

-- Вечный пользователь admin
INSERT INTO users (
  full_name, login, password, role, id_profession,
  is_photographer, is_hairdresser, is_admin_role,
  salary_type, hired_at, fired_at,
  region, city, street, house_building,
  phone_user, date_of_birth, notes, created_at
) VALUES (
  'Администратор ресурса', 'admin',
  '$2y$10$BdFut.QXsnGo7eSpHwZHde7ylnB/13iKj2iJqtZ7psRMJl/GHFClq',
  'admin', 1,
  0, 0, 1,
  'fixed', NULL, NULL,
  '', '', '', '',
  '', '1990-01-01', NULL, NOW()
) ON DUPLICATE KEY UPDATE
  password = IF(role = 'admin', VALUES(password), password);

-- Вечный пользователь superuser
INSERT INTO users (
  full_name, login, password, role, id_profession,
  is_photographer, is_hairdresser, is_admin_role,
  salary_type, hired_at, fired_at,
  region, city, street, house_building,
  phone_user, date_of_birth, notes, created_at
) VALUES (
  'Руководитель', 'director',
  '$2y$10$BdFut.QXsnGo7eSpHwZHde7ylnB/13iKj2iJqtZ7psRMJl/GHFClq',
  'superuser', 2,
  0, 0, 1,
  'fixed', NULL, NULL,
  '', '', '', '',
  '', '1990-01-01', NULL, NOW()
) ON DUPLICATE KEY UPDATE
  password = IF(role = 'superuser', VALUES(password), password);
