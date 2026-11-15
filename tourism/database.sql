-- Пойгоҳи додаҳо: Агентии сайёҳӣ (Тоҷикистон)
CREATE DATABASE IF NOT EXISTS tajik_tours CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tajik_tours;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW  IF EXISTS v_date_seats;
DROP TABLE IF EXISTS reviews, photos, bookings, tour_dates, tours, users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Корбарон (сайёҳ / менеҷер / маъмур)
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(120) NOT NULL UNIQUE,
  phone         VARCHAR(30)  NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('tourist','manager','admin') NOT NULL DEFAULT 'tourist',
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Турҳо
CREATE TABLE tours (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  manager_id    INT NOT NULL,
  title         VARCHAR(160) NOT NULL,
  region        VARCHAR(80)  NOT NULL,
  short_desc    VARCHAR(300) NOT NULL,
  description   TEXT NOT NULL,
  itinerary     TEXT NOT NULL COMMENT 'Барнома аз рӯи рӯзҳо, ҳар рӯз дар сатри алоҳида',
  duration_days TINYINT UNSIGNED NOT NULL,
  price         DECIMAL(10,2) NOT NULL COMMENT 'Нарх барои як нафар (сомонӣ)',
  difficulty    ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_region (region),
  INDEX idx_price (price),
  CONSTRAINT fk_tours_manager FOREIGN KEY (manager_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- 3. Санаҳои сафар бо шумораи ҷойҳо
CREATE TABLE tour_dates (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  tour_id     INT NOT NULL,
  start_date  DATE NOT NULL,
  total_seats SMALLINT UNSIGNED NOT NULL,
  status      ENUM('open','closed') NOT NULL DEFAULT 'open',
  UNIQUE KEY uq_tour_date (tour_id, start_date),
  CONSTRAINT fk_dates_tour FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Бандҳо
CREATE TABLE bookings (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  tour_date_id INT NOT NULL,
  persons      SMALLINT UNSIGNED NOT NULL,
  total_price  DECIMAL(10,2) NOT NULL,
  status       ENUM('pending','confirmed','rejected','cancelled') NOT NULL DEFAULT 'pending',
  note         VARCHAR(300) NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_date_status (tour_date_id, status),
  CONSTRAINT fk_book_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_book_date FOREIGN KEY (tour_date_id) REFERENCES tour_dates(id) ON DELETE CASCADE,
  CONSTRAINT chk_persons CHECK (persons > 0)
) ENGINE=InnoDB;

-- 5. Фикру мулоҳизаҳо ва баҳо (пас аз модератсия нашр мешавад)
CREATE TABLE reviews (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  tour_id    INT NOT NULL,
  booking_id INT NOT NULL,
  rating     TINYINT UNSIGNED NOT NULL,
  comment    TEXT NOT NULL,
  status     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_tour (user_id, tour_id),
  CONSTRAINT fk_rev_user    FOREIGN KEY (user_id)    REFERENCES users(id),
  CONSTRAINT fk_rev_tour    FOREIGN KEY (tour_id)    REFERENCES tours(id) ON DELETE CASCADE,
  CONSTRAINT fk_rev_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- 6. Аксҳо
CREATE TABLE photos (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  tour_id   INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  is_main   TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_photo_tour FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Ҷойҳои холӣ барои ҳар санаи сафар
CREATE VIEW v_date_seats AS
SELECT d.id, d.tour_id, d.start_date, d.total_seats, d.status,
       COALESCE(SUM(CASE WHEN b.status IN ('pending','confirmed') THEN b.persons END),0) AS booked_seats,
       d.total_seats - COALESCE(SUM(CASE WHEN b.status IN ('pending','confirmed') THEN b.persons END),0) AS free_seats
FROM tour_dates d
LEFT JOIN bookings b ON b.tour_date_id = d.id
GROUP BY d.id, d.tour_id, d.start_date, d.total_seats, d.status;

-- ===== Маълумоти намунавӣ =====
-- Паролҳо: Admin123 / Manager123 / Tourist123
INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES
(1,'Маъмур','admin@tour.tj','+992900000001','$2b$12$xo27J6yqCJFWFxm8VWOoXe8wFRGze6./5ys.yYAUqrkjAoa83yg0O','admin'),
(2,'Рустам Каримов','manager@tour.tj','+992900000002','$2b$12$HPspYqlmrjV9QLkLSa9g4eFNAtYdxls/0PesEAcMGQydHPaFuFbmi','manager'),
(3,'Мадина Самандова','tourist@tour.tj','+992900000003','$2b$12$RiahkwdJxAlVT4/XlidX0eNlERASsAhxuWmXO7SVPVYPpSNcsyUxK','tourist');

INSERT INTO tours (id, manager_id, title, region, short_desc, description, itinerary, duration_days, price, difficulty) VALUES
(1,2,'Искандаркӯл ва кӯҳҳои Фон','Кӯҳҳои Фон','Сафар ба кӯли зебои Искандаркӯл ва кӯлҳои Фон.',
 'Се рӯз дар дили кӯҳҳои Фон: кӯли Искандаркӯл, шаршараи Фон-Ёрсаҳ, деҳаҳои кӯҳӣ ва хӯрокҳои миллӣ.',
 'Рӯзи 1: Душанбе – Искандаркӯл, ҷойгиршавӣ дар базаи сайёҳӣ.\nРӯзи 2: Пиёдагардӣ ба шаршара ва кӯлҳои атроф.\nРӯзи 3: Бозгашт ба Душанбе.',
 3,1200.00,'easy'),
(2,2,'Роҳи Помир: Хоруғ – Ишкошим','Помир','Сафари ҳаштрӯза тавассути Роҳи Помир.',
 'Сафар аз Душанбе ба Помир: Хоруғ, водии Вахон, қалъаҳои қадимӣ ва чашмаҳои гарм.',
 'Рӯзи 1: Душанбе – Қалъаихум.\nРӯзи 2: Қалъаихум – Хоруғ.\nРӯзи 3: Хоруғ – Ҷарданаи Гарм.\nРӯзи 4: Водии Вахон, қалъаи Ямчун.\nРӯзи 5: Ишкошим.\nРӯзи 6: Чашмаи БибиФотима.\nРӯзи 7: Бозгашт ба Хоруғ.\nРӯзи 8: Хоруғ – Душанбе (парвоз).',
 8,6500.00,'hard'),
(3,2,'Кӯлҳои Ҳафткӯл (Шинг)','Кӯҳҳои Фон','Ҳафт кӯли зебои водии Шинг.',
 'Сафари чаҳоррӯза ба ҳафт кӯли Мархузӯ – Нофин – Хурдак – Ҳазорчашма – Софиён – Мизҳон – Фирӯзаӣ.',
 'Рӯзи 1: Душанбе – Шинг.\nРӯзи 2: Кӯлҳои якум ва дуюм.\nРӯзи 3: Кӯлҳои сеюм то ҳафтум.\nРӯзи 4: Бозгашт.',
 4,1900.00,'medium'),
(4,2,'Тури тарҷиҳии Сараз','Помир','Кӯли Сарез — дурдонаи Помир бо оби кабуди беназир.',
 'Сафар ба кӯли Сарез, ки дар натиҷаи заминҷунбии соли 1911 ба вуҷуд омадааст. Манзараҳои ҳайратангези кӯҳӣ ва деҳаҳои Помир.',
 'Рӯзи 1: Душанбе – Хоруғ (парвоз).\nРӯзи 2: Хоруғ – Мурғоб, роҳи Помир.\nРӯзи 3: Роҳ ба сӯи Сарез, шабро дар деҳа.\nРӯзи 4: Пиёдагардӣ ба соҳили кӯл.\nРӯзи 5: Бозгашт ба Мурғоб.\nРӯзи 6: Мурғоб – Хоруғ – Душанбе.',
 6,5200.00,'hard');

INSERT INTO tour_dates (tour_id, start_date, total_seats, status) VALUES
(1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 12, 'open'),
(1, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 12, 'open'),
(1, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 10, 'open'),
(2, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 8,  'open'),
(3, DATE_ADD(CURDATE(), INTERVAL 20 DAY), 2,  'open'),
(4, DATE_ADD(CURDATE(), INTERVAL 40 DAY), 6,  'open'),
(4, DATE_ADD(CURDATE(), INTERVAL 70 DAY), 8,  'open');

-- Бандҳои намунавӣ (санаи гузашта – барои санҷиши фикр)
INSERT INTO bookings (id, user_id, tour_date_id, persons, total_price, status) VALUES
(1,3,1,2,2400.00,'confirmed');

-- Аксҳои турҳо (файлҳо дар папкаи uploads/)
INSERT INTO photos (tour_id, file_path, is_main) VALUES
(1,'uploads/t1_a.svg',1),(1,'uploads/t1_b.svg',0),
(2,'uploads/t2_a.svg',1),(2,'uploads/t2_b.svg',0),
(3,'uploads/t3_a.svg',1),(3,'uploads/t3_b.svg',0),
(4,'uploads/t4_a.svg',1),(4,'uploads/t4_b.svg',0);
