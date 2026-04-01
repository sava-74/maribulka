-- ============================================
-- СХЕМА БАЗЫ ДАННЫХ ДЛЯ MARIBULKA
-- Бухгалтерия фотографа
-- Обновлено: 01.04.2026
-- ============================================

-- Порядок удаления: зависимые таблицы первыми
DROP TABLE IF EXISTS `user_permissions`;
DROP TABLE IF EXISTS `booking_history`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `income`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `promotions`;
DROP TABLE IF EXISTS `shooting_types`;
DROP TABLE IF EXISTS `clients`;
DROP TABLE IF EXISTS `expense_categories`;
DROP TABLE IF EXISTS `salary_type`;
DROP TABLE IF EXISTS `profession`;
DROP TABLE IF EXISTS `user_role`;
DROP TABLE IF EXISTS `studio_photos`;
DROP TABLE IF EXISTS `studio_description`;
DROP TABLE IF EXISTS `home_blocks`;

-- ============================================
-- СПРАВОЧНИК: РОЛИ ПОЛЬЗОВАТЕЛЕЙ
-- ============================================
CREATE TABLE `user_role` (
  `id`     INT NOT NULL AUTO_INCREMENT,
  `title`  VARCHAR(255) NOT NULL COMMENT 'Роль (латиница)',
  `name`   VARCHAR(255) NOT NULL COMMENT 'Роль (кириллица)',
  `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Вкл/Выкл',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user_role` (`id`, `title`, `name`, `active`) VALUES
(1, 'admin',      'СисАдмин',    1),
(2, 'superuser',  'Директор',    1),
(3, 'superuser1', 'Руководитель',1),
(4, 'auser',      'Администратор',1),
(5, 'prouser',    'Работник',    1),
(6, 'user',       'Пользователь',1);

-- ============================================
-- СПРАВОЧНИК: ПРОФЕССИИ
-- ============================================
CREATE TABLE `profession` (
  `id`     INT NOT NULL AUTO_INCREMENT,
  `title`  VARCHAR(255) NOT NULL COMMENT 'Профессия',
  `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Вкл/Выкл',
  `notes`  TEXT NOT NULL DEFAULT '' COMMENT 'Заметки',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `profession` (`id`, `title`, `active`, `notes`) VALUES
(1, 'СисАдмин',    1, ''),
(2, 'Директор',    1, ''),
(3, 'Руководитель',1, ''),
(4, 'Администратор',1,''),
(5, 'Фотограф',    1, ''),
(6, 'Парикмахер',  1, '');

-- ============================================
-- СПРАВОЧНИК: ТИПЫ ЗАРПЛАТЫ
-- ============================================
CREATE TABLE `salary_type` (
  `id`                         INT NOT NULL AUTO_INCREMENT,
  `title`                      VARCHAR(255) NOT NULL COMMENT 'Название',
  `monthly_salary`             TINYINT DEFAULT 0 COMMENT 'Оклад в месяц',
  `salary_value`               INT DEFAULT 10000 COMMENT 'Значение оклада',
  `percentage_of_the_order`    TINYINT DEFAULT 0 COMMENT 'Процент от заказа',
  `the_percentage_value`       INT DEFAULT 30 COMMENT 'Значение процента от заказа',
  `interest_dividends`         TINYINT DEFAULT 0 COMMENT 'Проценты дивидендов',
  `value_dividend_percentages` INT DEFAULT 60 COMMENT 'Значение процентов дивидендов',
  `fixed_order`                TINYINT DEFAULT 0 COMMENT 'Фиксированное от заказа',
  `fixed_value_order`          INT DEFAULT 1000 COMMENT 'Фиксированное значение от заказа (руб)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Типы зарплат сотрудников';

-- ============================================
-- ПОЛЬЗОВАТЕЛИ
-- ============================================
CREATE TABLE `users` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `login`         VARCHAR(255) DEFAULT NULL COMMENT 'Логин (NULL при увольнении)',
  `password`      VARCHAR(255) DEFAULT NULL COMMENT 'Пароль (NULL при увольнении)',
  `role`          INT NOT NULL COMMENT 'FK → user_role.id',
  `full_name`     VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'ФИО',
  `name`          VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'ФИО (устаревшее поле)',
  `id_profession` INT NOT NULL COMMENT 'FK → profession.id',
  `id_salary_type`INT NOT NULL COMMENT 'FK → salary_type.id',
  `is_photographer` TINYINT(1) NOT NULL DEFAULT 0,
  `is_hairdresser`  TINYINT(1) NOT NULL DEFAULT 0,
  `is_admin_role`   TINYINT(1) NOT NULL DEFAULT 0,
  `hired_at`      DATE NOT NULL COMMENT 'Дата приёма',
  `fired_at`      DATE DEFAULT NULL COMMENT 'Дата увольнения',
  `notes`         TEXT COMMENT 'Примечания',
  `region`        TEXT NOT NULL DEFAULT '' COMMENT 'Область',
  `city`          TEXT NOT NULL DEFAULT '' COMMENT 'Город',
  `street`        TEXT NOT NULL DEFAULT '' COMMENT 'Улица',
  `house_building`TEXT NOT NULL DEFAULT '' COMMENT 'Дом/корпус',
  `flat`          INT DEFAULT NULL COMMENT 'Квартира',
  `phone_user`    VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Телефон',
  `email_user`    VARCHAR(100) DEFAULT NULL COMMENT 'Email',
  `date_of_birth` DATE NOT NULL COMMENT 'Дата рождения',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `role` (`role`),
  KEY `id_profession` (`id_profession`),
  KEY `id_salary_type` (`id_salary_type`),
  CONSTRAINT `id_role`  FOREIGN KEY (`role`)          REFERENCES `user_role` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `id_prof`  FOREIGN KEY (`id_profession`) REFERENCES `profession` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `id_sp`    FOREIGN KEY (`id_salary_type`) REFERENCES `salary_type` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ИНДИВИДУАЛЬНЫЕ ПРАВА ПОЛЬЗОВАТЕЛЕЙ
-- ============================================
CREATE TABLE `user_permissions` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `user_id`    INT NOT NULL,
  `section`    VARCHAR(64) NOT NULL COMMENT 'Секция (clients, income, etc.)',
  `action`     VARCHAR(32) NOT NULL COMMENT 'Действие (view, create, edit, delete)',
  `allowed`    TINYINT(1) NOT NULL COMMENT '1=разрешено, 0=запрещено',
  `granted_by` INT DEFAULT NULL COMMENT 'Кто выдал право',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_section_action` (`user_id`, `section`, `action`),
  KEY `fk_up_granted` (`granted_by`),
  CONSTRAINT `fk_up_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_up_granted` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- СПРАВОЧНИК: КЛИЕНТЫ
-- ============================================
CREATE TABLE `clients` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(100) NOT NULL COMMENT 'ФИО клиента',
  `phone`          VARCHAR(20) NOT NULL COMMENT 'Телефон',
  `total_bookings` INT DEFAULT 0 COMMENT 'Количество съёмок',
  `notes`          TEXT DEFAULT NULL COMMENT 'Заметки о клиенте',
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by`     INT DEFAULT NULL COMMENT 'Кто создал',
  `updated_by`     INT DEFAULT NULL COMMENT 'Кто обновил',
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- СПРАВОЧНИК: ТИПЫ СЪЁМОК
-- ============================================
CREATE TABLE `shooting_types` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(100) NOT NULL COMMENT 'Название типа съёмки',
  `base_price`       DECIMAL(10,2) NOT NULL COMMENT 'Базовая цена',
  `duration_minutes` INT DEFAULT 30 COMMENT 'Длительность в минутах',
  `description`      TEXT DEFAULT NULL COMMENT 'Описание',
  `is_active`        TINYINT(1) DEFAULT 1 COMMENT 'Активен ли тип',
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by`       INT DEFAULT NULL COMMENT 'Кто создал',
  `updated_by`       INT DEFAULT NULL COMMENT 'Кто обновил',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- СПРАВОЧНИК: АКЦИИ (СКИДКИ)
-- ============================================
CREATE TABLE `promotions` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(100) NOT NULL COMMENT 'Название акции',
  `discount_percent` DECIMAL(5,2) NOT NULL COMMENT 'Процент скидки',
  `start_date`       DATE DEFAULT NULL COMMENT 'Дата начала',
  `end_date`         DATE DEFAULT NULL COMMENT 'Дата окончания',
  `is_active`        TINYINT(1) DEFAULT 1 COMMENT 'Активна ли акция',
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by`       INT DEFAULT NULL COMMENT 'Кто создал',
  `updated_by`       INT DEFAULT NULL COMMENT 'Кто обновил',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- СПРАВОЧНИК: КАТЕГОРИИ РАСХОДОВ
-- ============================================
CREATE TABLE `expense_categories` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL COMMENT 'Название категории',
  `is_active`  TINYINT(1) DEFAULT 1 COMMENT 'Активна ли категория',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT DEFAULT NULL COMMENT 'Кто создал',
  `updated_by` INT DEFAULT NULL COMMENT 'Кто обновил',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ЗАПИСИ НА СЪЁМКУ
-- ============================================
CREATE TABLE `bookings` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `order_number`   VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Номер заказа МБ{id}{magic}{year}',
  `booking_date`   DATE NOT NULL COMMENT 'Дата записи',
  `shooting_date`  DATETIME NOT NULL COMMENT 'Дата и время съёмки',
  `processing_days`INT DEFAULT 7 COMMENT 'Дней на обработку',
  `delivery_date`  DATE NOT NULL COMMENT 'Дата выдачи',
  `client_id`      INT NOT NULL COMMENT 'FK → clients.id',
  `master_id`      INT DEFAULT NULL COMMENT 'Фотограф (мастер)',
  `phone`          VARCHAR(20) NOT NULL COMMENT 'Телефон клиента',
  `shooting_type_id` INT NOT NULL COMMENT 'FK → shooting_types.id',
  `quantity`       INT DEFAULT 1 COMMENT 'Количество человек/фото',
  `promotion_id`   INT DEFAULT NULL COMMENT 'FK → promotions.id',
  `base_price`     DECIMAL(10,2) NOT NULL COMMENT 'Базовая цена',
  `discount`       DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Сумма скидки',
  `final_price`    DECIMAL(10,2) NOT NULL COMMENT 'Цена после скидки',
  `total_amount`   DECIMAL(10,2) NOT NULL COMMENT 'Итоговая сумма',
  `paid_amount`    DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Оплачено',
  `payment_status` ENUM('unpaid','partially_paid','fully_paid') DEFAULT 'unpaid',
  `status`         ENUM('new','in_progress','completed','completed_partially','not_completed','cancelled_by_photographer','cancelled_by_client','client_no_show') NOT NULL DEFAULT 'new',
  `is_locked`      TINYINT(1) DEFAULT 0 COMMENT 'Блокировка редактирования',
  `cancel_reason`  TEXT DEFAULT NULL COMMENT 'Причина отмены',
  `notes`          TEXT DEFAULT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by`     INT DEFAULT NULL COMMENT 'Кто создал',
  `updated_by`     INT DEFAULT NULL COMMENT 'Кто обновил',
  `processed_at`   DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_number` (`order_number`),
  KEY `client_id` (`client_id`),
  KEY `shooting_type_id` (`shooting_type_id`),
  KEY `promotion_id` (`promotion_id`),
  KEY `shooting_date` (`shooting_date`),
  KEY `status` (`status`),
  KEY `idx_bookings_master_id` (`master_id`),
  CONSTRAINT `fk_bookings_client`        FOREIGN KEY (`client_id`)        REFERENCES `clients` (`id`),
  CONSTRAINT `fk_bookings_shooting_type` FOREIGN KEY (`shooting_type_id`) REFERENCES `shooting_types` (`id`),
  CONSTRAINT `fk_bookings_promotion`     FOREIGN KEY (`promotion_id`)     REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ИСТОРИЯ ИЗМЕНЕНИЙ СТАТУСОВ ЗАКАЗОВ
-- ============================================
CREATE TABLE `booking_history` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `booking_id` INT NOT NULL COMMENT 'FK → bookings.id',
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `reason`     TEXT DEFAULT NULL,
  `changed_by` INT DEFAULT NULL,
  `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking_id` (`booking_id`),
  KEY `idx_changed_at` (`changed_at`),
  CONSTRAINT `fk_history_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='История изменений статусов заказов';

-- ============================================
-- ПРИХОД (ПЛАТЕЖИ)
-- ============================================
CREATE TABLE `income` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `date`       DATE NOT NULL COMMENT 'Дата платежа',
  `booking_id` INT DEFAULT NULL COMMENT 'FK → bookings.id',
  `client_id`  INT DEFAULT NULL COMMENT 'FK → clients.id',
  `user_id`    INT DEFAULT NULL COMMENT 'Кто принял платёж',
  `amount`     DECIMAL(10,2) NOT NULL COMMENT 'Сумма платежа',
  `category`   ENUM('advance','balance','full_payment') NOT NULL COMMENT 'Аванс/доплата/полная оплата',
  `notes`      TEXT DEFAULT NULL COMMENT 'Примечания',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT DEFAULT NULL COMMENT 'Кто создал',
  `updated_by` INT DEFAULT NULL COMMENT 'Кто обновил',
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `client_id` (`client_id`),
  KEY `date` (`date`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_income_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  CONSTRAINT `fk_income_client`  FOREIGN KEY (`client_id`)  REFERENCES `clients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- РАСХОДЫ
-- ============================================
CREATE TABLE `expenses` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `date`        DATE NOT NULL COMMENT 'Дата расхода',
  `amount`      DECIMAL(10,2) NOT NULL COMMENT 'Сумма',
  `category`    INT NOT NULL COMMENT 'FK → expense_categories.id',
  `description` TEXT NOT NULL COMMENT 'Описание',
  `booking_id`  INT DEFAULT NULL COMMENT 'FK → bookings.id (опционально)',
  `refund_type` ENUM('prepayment','partial','full') DEFAULT NULL COMMENT 'Тип возврата',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by`  INT DEFAULT NULL COMMENT 'Кто создал',
  `updated_by`  INT DEFAULT NULL COMMENT 'Кто обновил',
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `date` (`date`),
  KEY `fk_expense_category` (`category`),
  CONSTRAINT `fk_expenses_booking`  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  CONSTRAINT `fk_expense_category`  FOREIGN KEY (`category`)   REFERENCES `expense_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ДОМАШНЯЯ СТРАНИЦА: БЛОКИ КОНТЕНТА
-- ============================================
CREATE TABLE `home_blocks` (
  `id`         TINYINT UNSIGNED NOT NULL,
  `content`    LONGTEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ДОМАШНЯЯ СТРАНИЦА: ОПИСАНИЕ СТУДИИ
-- ============================================
CREATE TABLE `studio_description` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `content`    MEDIUMTEXT NOT NULL COMMENT 'HTML-контент',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `studio_description` (`content`) VALUES
('<p>Добро пожаловать в нашу фотостудию!</p>');

-- ============================================
-- ДОМАШНЯЯ СТРАНИЦА: ФОТО СТУДИИ
-- ============================================
CREATE TABLE `studio_photos` (
  `id`        INT NOT NULL AUTO_INCREMENT,
  `photo_url` VARCHAR(255) NOT NULL COMMENT 'URL фото',
  `position`  INT NOT NULL COMMENT 'Позиция фото (0-3)',
  `created_at`DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `position` (`position`),
  KEY `idx_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ТЕСТОВЫЕ ДАННЫЕ
-- ============================================

-- Типы съёмок
INSERT INTO `shooting_types` (`name`, `base_price`, `duration_minutes`, `description`) VALUES
('Портрет',          5000.00, 120, 'Индивидуальная фотосессия, до 2 часов'),
('Свадьба',         30000.00, 480, 'Полный день съёмки свадьбы'),
('Детская съёмка',   3000.00,  90, 'Фотосессия детей, до 1.5 часов'),
('Love Story',       4000.00, 120, 'Романтическая фотосессия пары'),
('Семейная съёмка',  6000.00, 120, 'Семейная фотосессия'),
('Выпускной',        2500.00,  60, 'Фотосессия выпускников');

-- Акции
INSERT INTO `promotions` (`name`, `discount_percent`, `start_date`, `end_date`) VALUES
('Без скидки',         0.00, NULL, NULL),
('Постоянный клиент', 10.00, NULL, NULL),
('Весенняя акция',    20.00, '2026-03-01', '2026-05-31'),
('Летняя распродажа', 15.00, '2026-06-01', '2026-08-31'),
('Новогодняя акция',  25.00, '2025-12-20', '2026-01-10');

-- Категории расходов
INSERT INTO `expense_categories` (`name`) VALUES
('Материалы'),
('Аренда студии'),
('Транспорт'),
('Программное обеспечение'),
('Оборудование'),
('Возврат средств'),
('Прочее');

-- Клиенты
INSERT INTO `clients` (`name`, `phone`, `total_bookings`, `notes`) VALUES
('Иванова Анна Сергеевна',       '+79001234567', 3, 'Постоянный клиент, всегда вовремя'),
('Петров Дмитрий Иванович',      '+79009876543', 1, 'Новый клиент'),
('Сидорова Мария Петровна',      '+79005555555', 2, 'Отменяла съёмку один раз'),
('Козлов Алексей Викторович',    '+79007777777', 1, NULL),
('Новикова Елена Александровна', '+79003333333', 0, 'Только записалась');

-- ============================================
-- ГОТОВО!
-- ============================================
