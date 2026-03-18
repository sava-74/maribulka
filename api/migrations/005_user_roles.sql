-- 005_user_roles.sql
-- Миграция: система ролей и прав пользователей

-- 1. Расширяем таблицу users
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS role ENUM('admin','superuser','auser','prouser','user') NOT NULL DEFAULT 'prouser',
  ADD COLUMN IF NOT EXISTS full_name VARCHAR(255),
  ADD COLUMN IF NOT EXISTS is_photographer BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS is_hairdresser BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS is_admin_role BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS hired_at DATE,
  ADD COLUMN IF NOT EXISTS fired_at DATE,
  ADD COLUMN IF NOT EXISTS salary_type ENUM('fixed','percent','fixed_percent'),
  ADD COLUMN IF NOT EXISTS notes TEXT,
  ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT NOW();

-- 2. Существующий admin получает роль admin
UPDATE users SET role = 'admin' WHERE login = 'admin';

-- 3. Таблица индивидуальных прав (переопределения дефолтов роли)
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
