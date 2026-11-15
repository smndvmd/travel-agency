USE tajik_tours;
DELETE FROM photos;
INSERT IGNORE INTO tours (id, manager_id, title, region, short_desc, description, itinerary, duration_days, price, difficulty) VALUES
(4,2,'Тури тарҷиҳии Сараз','Помир','Кӯли Сарез — дурдонаи Помир бо оби кабуди беназир.',
 'Сафар ба кӯли Сарез, ки дар натиҷаи заминҷунбии соли 1911 ба вуҷуд омадааст. Манзараҳои ҳайратангези кӯҳӣ ва деҳаҳои Помир.',
 'Рӯзи 1: Душанбе – Хоруғ (парвоз).\nРӯзи 2: Хоруғ – Мурғоб, роҳи Помир.\nРӯзи 3: Роҳ ба сӯи Сарез, шабро дар деҳа.\nРӯзи 4: Пиёдагардӣ ба соҳили кӯл.\nРӯзи 5: Бозгашт ба Мурғоб.\nРӯзи 6: Мурғоб – Хоруғ – Душанбе.',
 6,5200.00,'hard');
INSERT IGNORE INTO tour_dates (tour_id, start_date, total_seats, status) VALUES
(4, DATE_ADD(CURDATE(), INTERVAL 40 DAY), 6, 'open'),
(4, DATE_ADD(CURDATE(), INTERVAL 70 DAY), 8, 'open');

-- Аксҳои турҳо (файлҳо дар папкаи uploads/)
INSERT INTO photos (tour_id, file_path, is_main) VALUES
(1,'uploads/t1_a.svg',1),(1,'uploads/t1_b.svg',0),
(2,'uploads/t2_a.svg',1),(2,'uploads/t2_b.svg',0),
(3,'uploads/t3_a.svg',1),(3,'uploads/t3_b.svg',0),
(4,'uploads/t4_a.svg',1),(4,'uploads/t4_b.svg',0);
