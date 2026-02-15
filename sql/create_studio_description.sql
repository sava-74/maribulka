-- Таблица для хранения описания студии
CREATE TABLE IF NOT EXISTS studio_description (
  id INT PRIMARY KEY AUTO_INCREMENT,
  content TEXT NOT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Вставка дефолтного описания
INSERT INTO studio_description (content) VALUES
('<p>Добро пожаловать в нашу фотостудию! Здесь вы можете создать незабываемые снимки в уютной атмосфере.</p>');
