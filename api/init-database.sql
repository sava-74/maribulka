-- ============================================
-- СХЕМА БАЗЫ ДАННЫХ ДЛЯ MARIBULKA
-- Бухгалтерия фотографа
-- ============================================

-- Удаляем старые таблицы если есть (для переинициализации)
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `income`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `promotions`;
DROP TABLE IF EXISTS `shooting_types`;
DROP TABLE IF EXISTS `clients`;

-- ============================================
-- СПРАВОЧНИК: КЛИЕНТЫ
-- ============================================
CREATE TABLE `clients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'ФИО клиента',
  `phone` VARCHAR(20) NOT NULL COMMENT 'Телефон',
  `total_bookings` INT(11) DEFAULT 0 COMMENT 'Количество съёмок',
  `notes` TEXT DEFAULT NULL COMMENT 'Заметки о клиенте',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- СПРАВОЧНИК: ТИПЫ СЪЁМОК
-- ============================================
CREATE TABLE `shooting_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'Название типа съёмки',
  `base_price` DECIMAL(10,2) NOT NULL COMMENT 'Базовая цена',
  `description` TEXT DEFAULT NULL COMMENT 'Описание',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Активен ли тип',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- СПРАВОЧНИК: АКЦИИ (СКИДКИ)
-- ============================================
CREATE TABLE `promotions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'Название акции',
  `discount_percent` DECIMAL(5,2) NOT NULL COMMENT 'Процент скидки',
  `start_date` DATE DEFAULT NULL COMMENT 'Дата начала',
  `end_date` DATE DEFAULT NULL COMMENT 'Дата окончания',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Активна ли акция',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ЗАПИСИ НА СЪЁМКУ
-- ============================================
CREATE TABLE `bookings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `booking_date` DATE NOT NULL COMMENT 'Дата записи',
  `shooting_date` DATE NOT NULL COMMENT 'Дата съёмки',
  `processing_days` INT(11) DEFAULT 7 COMMENT 'Дней на обработку',
  `delivery_date` DATE NOT NULL COMMENT 'Дата выдачи',

  `client_id` INT(11) NOT NULL COMMENT 'ID клиента',
  `phone` VARCHAR(20) NOT NULL COMMENT 'Телефон клиента',

  `shooting_type_id` INT(11) NOT NULL COMMENT 'ID типа съёмки',
  `quantity` INT(11) DEFAULT 1 COMMENT 'Количество человек/фото',
  `promotion_id` INT(11) DEFAULT NULL COMMENT 'ID акции',

  `base_price` DECIMAL(10,2) NOT NULL COMMENT 'Базовая цена',
  `discount` DECIMAL(10,2) DEFAULT 0 COMMENT 'Сумма скидки',
  `final_price` DECIMAL(10,2) NOT NULL COMMENT 'Цена после скидки',
  `total_amount` DECIMAL(10,2) NOT NULL COMMENT 'Итоговая сумма (цена * количество)',

  `paid_amount` DECIMAL(10,2) DEFAULT 0 COMMENT 'Оплачено',
  `payment_status` ENUM('unpaid', 'partially_paid', 'fully_paid') DEFAULT 'unpaid' COMMENT 'Статус оплаты',

  `status` ENUM('new', 'completed', 'delivered', 'cancelled') DEFAULT 'new' COMMENT 'Статус записи',
  `cancel_reason` TEXT DEFAULT NULL COMMENT 'Причина отмены',

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `shooting_type_id` (`shooting_type_id`),
  KEY `promotion_id` (`promotion_id`),
  KEY `shooting_date` (`shooting_date`),
  KEY `status` (`status`),

  CONSTRAINT `fk_bookings_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `fk_bookings_shooting_type` FOREIGN KEY (`shooting_type_id`) REFERENCES `shooting_types` (`id`),
  CONSTRAINT `fk_bookings_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ПРИХОД (ПЛАТЕЖИ)
-- ============================================
CREATE TABLE `income` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL COMMENT 'Дата платежа',
  `booking_id` INT(11) NOT NULL COMMENT 'ID записи на съёмку',
  `client_id` INT(11) NOT NULL COMMENT 'ID клиента',
  `amount` DECIMAL(10,2) NOT NULL COMMENT 'Сумма платежа',
  `category` ENUM('advance', 'balance', 'full_payment') NOT NULL COMMENT 'Категория: аванс, доплата, полная оплата',
  `notes` TEXT DEFAULT NULL COMMENT 'Примечания',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `client_id` (`client_id`),
  KEY `date` (`date`),

  CONSTRAINT `fk_income_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  CONSTRAINT `fk_income_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- РАСХОД
-- ============================================
CREATE TABLE `expenses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL COMMENT 'Дата расхода',
  `amount` DECIMAL(10,2) NOT NULL COMMENT 'Сумма',
  `category` ENUM('materials', 'rent', 'transport', 'software', 'equipment', 'refund', 'other') NOT NULL COMMENT 'Категория расхода',
  `description` TEXT NOT NULL COMMENT 'Описание',
  `booking_id` INT(11) DEFAULT NULL COMMENT 'Связь со съёмкой (опционально)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `date` (`date`),
  KEY `category` (`category`),

  CONSTRAINT `fk_expenses_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ТЕСТОВЫЕ ДАННЫЕ
-- ============================================

-- Типы съёмок
INSERT INTO `shooting_types` (`name`, `base_price`, `description`) VALUES
('Портрет', 5000.00, 'Индивидуальная фотосессия, до 2 часов'),
('Свадьба', 30000.00, 'Полный день съёмки свадьбы'),
('Детская съёмка', 3000.00, 'Фотосессия детей, до 1.5 часов'),
('Love Story', 4000.00, 'Романтическая фотосессия пары'),
('Семейная съёмка', 6000.00, 'Семейная фотосессия'),
('Выпускной', 2500.00, 'Фотосессия выпускников');

-- Акции
INSERT INTO `promotions` (`name`, `discount_percent`, `start_date`, `end_date`) VALUES
('Без скидки', 0.00, NULL, NULL),
('Постоянный клиент', 10.00, NULL, NULL),
('Весенняя акция', 20.00, '2026-03-01', '2026-05-31'),
('Летняя распродажа', 15.00, '2026-06-01', '2026-08-31'),
('Новогодняя акция', 25.00, '2025-12-20', '2026-01-10');

-- Клиенты
INSERT INTO `clients` (`name`, `phone`, `total_bookings`, `notes`) VALUES
('Иванова Анна Сергеевна', '+79001234567', 3, 'Постоянный клиент, всегда вовремя'),
('Петров Дмитрий Иванович', '+79009876543', 1, 'Новый клиент'),
('Сидорова Мария Петровна', '+79005555555', 2, 'Отменяла съёмку один раз'),
('Козлов Алексей Викторович', '+79007777777', 1, NULL),
('Новикова Елена Александровна', '+79003333333', 0, 'Только записалась');

-- Записи на съёмку (примеры)
INSERT INTO `bookings` (
  `booking_date`, `shooting_date`, `processing_days`, `delivery_date`,
  `client_id`, `phone`, `shooting_type_id`, `quantity`, `promotion_id`,
  `base_price`, `discount`, `final_price`, `total_amount`,
  `paid_amount`, `payment_status`, `status`
) VALUES
-- Полностью оплаченная съёмка
('2026-02-01', '2026-02-10', 7, '2026-02-17',
 1, '+79001234567', 1, 1, 2,
 5000.00, 500.00, 4500.00, 4500.00,
 4500.00, 'fully_paid', 'completed'),

-- Частично оплаченная съёмка (аванс)
('2026-02-05', '2026-02-15', 7, '2026-02-22',
 2, '+79009876543', 3, 2, 1,
 3000.00, 0.00, 3000.00, 6000.00,
 3000.00, 'partially_paid', 'new'),

-- Неоплаченная будущая съёмка
('2026-02-07', '2026-02-20', 10, '2026-03-02',
 5, '+79003333333', 4, 1, 3,
 4000.00, 800.00, 3200.00, 3200.00,
 0.00, 'unpaid', 'new'),

-- Отменённая съёмка
('2026-01-15', '2026-01-25', 7, '2026-02-01',
 3, '+79005555555', 2, 1, 1,
 30000.00, 0.00, 30000.00, 30000.00,
 10000.00, 'partially_paid', 'cancelled');

-- Приход (платежи)
INSERT INTO `income` (`date`, `booking_id`, `client_id`, `amount`, `category`, `notes`) VALUES
('2026-02-01', 1, 1, 4500.00, 'full_payment', 'Полная оплата за портретную съёмку'),
('2026-02-05', 2, 2, 3000.00, 'advance', 'Аванс 50% за детскую съёмку'),
('2026-01-15', 4, 3, 10000.00, 'advance', 'Аванс за свадьбу (отменена)');

-- Расходы
INSERT INTO `expenses` (`date`, `amount`, `category`, `description`, `booking_id`) VALUES
('2026-02-01', 1500.00, 'rent', 'Аренда студии на 3 часа', 1),
('2026-02-03', 800.00, 'materials', 'Реквизит для детской съёмки', 2),
('2026-01-20', 10000.00, 'refund', 'Возврат аванса клиенту Сидоровой (отмена свадьбы)', 4),
('2026-02-01', 2000.00, 'software', 'Подписка Adobe Creative Cloud', NULL),
('2026-01-28', 500.00, 'transport', 'Бензин, поездка на съёмку', NULL);

-- ============================================
-- ГОТОВО!
-- ============================================
