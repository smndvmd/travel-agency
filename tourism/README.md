# Туристическое агентство «Таджикистан»

PHP 8.2+ приложение с MySQL/MariaDB: каталог туров, даты отправления, бронирования, отзывы и роли туриста, менеджера и администратора.

## Запуск в XAMPP

1. Скопируйте `tourism` в `C:\xampp\htdocs`; для Apache задайте `tourism/public` как DocumentRoot (либо откройте `/tourism/public/`).
2. Запустите Apache и MySQL.
3. Импортируйте `database.sql` через phpMyAdmin.
4. При необходимости задайте переменные окружения `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` и `BASE_URL`. Значения по умолчанию подходят для локальной установки XAMPP.
5. Для XAMPP откройте `http://localhost/tourism/public/`. Для встроенного сервера из папки `tourism` выполните `php -S localhost:8000 -t public public/router.php`, затем откройте `http://localhost:8000/`.

Демо-учётные записи: администратор `admin@tour.tj` / `Admin123`, менеджер `manager@tour.tj` / `Manager123`, турист `tourist@tour.tj` / `Tourist123`.

## PDO и слой данных

`config.php` создаёт единое PDO-соединение (`utf8mb4`, исключения, ассоциативные выборки, отключённая эмуляция подготовленных запросов) и загружает классы пространства имён `Tourism\Repositories` из `src/Repositories`.

SQL находится в репозиториях: `TourRepository`, `DateRepository`, `PhotoRepository`, `BookingRepository`, `ReviewRepository`, `UserRepository` и `DashboardRepository`. Каталог поддерживает безопасный поиск и фильтры, подсчёт общего количества и пагинацию через целочисленные `LIMIT`/`OFFSET`. Изменяющие действия используют POST, CSRF-токены и перенаправление после POST. Бронирование блокирует запись даты в транзакции и перепроверяет остаток мест перед вставкой. Тур с бронями нельзя удалить, чтобы сохранить историю; его можно скрыть.

См. [маршруты лабораторной №12](docs/12-routes.txt) и [описание реализации и ручные проверки лабораторной №13](docs/13-pdo-repositories-crud.md).

## Front controller, Router и View (лабораторная №12)

Все чистые URL входят через `public/index.php`. `src/Core/Router.php` сопоставляет HTTP-метод и путь, передаёт числовые параметры контроллеру и формирует ответы 404/405. `src/Controllers/` содержит обработчики страниц, `src/Core/Controller.php` — общие методы render/redirect/json/abort, а `src/Core/View.php` выводит PHP-шаблоны из `views/` через буферизацию вывода и предоставляет HTML-экранирование. Для Composer-классов используется PSR-4 (`Tourism\` → `src/`); выполните `composer dump-autoload` из `tourism`, если Composer установлен. Без `vendor/` работает встроенный PSR-4 fallback.

В XAMPP включите `mod_rewrite` и `AllowOverride All` для `public/.htaccess`. Для совместимости предыдущие PHP-обработчики бронирования, профиля и административных страниц доступны через front controller и остаются работоспособными.
