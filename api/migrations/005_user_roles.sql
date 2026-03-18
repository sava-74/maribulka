-- 005_user_roles.sql
-- Миграция: система ролей и прав пользователей
-- Совместима с Beget MySQL 8.0.34 (ADD COLUMN IF NOT EXISTS не поддерживается)

-- 1. Изменить тип колонки role на ENUM (была VARCHAR)
ALTER TABLE users MODIFY COLUMN role ENUM('admin','superuser','auser','prouser','user') NOT NULL DEFAULT 'prouser';

-- 2. Добавить новые колонки через процедуру (идемпотентно)
DROP PROCEDURE IF EXISTS add_user_columns;
DELIMITER $$
CREATE PROCEDURE add_user_columns()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'full_name') THEN
    ALTER TABLE users ADD COLUMN full_name VARCHAR(255);
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_photographer') THEN
    ALTER TABLE users ADD COLUMN is_photographer BOOLEAN NOT NULL DEFAULT FALSE;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_hairdresser') THEN
    ALTER TABLE users ADD COLUMN is_hairdresser BOOLEAN NOT NULL DEFAULT FALSE;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_admin_role') THEN
    ALTER TABLE users ADD COLUMN is_admin_role BOOLEAN NOT NULL DEFAULT FALSE;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'hired_at') THEN
    ALTER TABLE users ADD COLUMN hired_at DATE;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'fired_at') THEN
    ALTER TABLE users ADD COLUMN fired_at DATE;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'salary_type') THEN
    ALTER TABLE users ADD COLUMN salary_type ENUM('fixed','percent','fixed_percent');
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'notes') THEN
    ALTER TABLE users ADD COLUMN notes TEXT;
  END IF;
END$$
DELIMITER ;
CALL add_user_columns();
DROP PROCEDURE IF EXISTS add_user_columns;

-- 3. Существующий admin получает роль admin
UPDATE users SET role = 'admin' WHERE login = 'admin';

-- 4. Таблица индивидуальных прав
CREATE TABLE IF NOT EXISTS user_permissions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  section VARCHAR(64) NOT NULL,
  action VARCHAR(32) NOT NULL,
  allowed BOOLEAN NOT NULL,
  granted_by INT,
  created_at DATETIME DEFAULT NOW(),
  UNIQUE KEY uq_user_section_action (user_id, section, action),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
);
