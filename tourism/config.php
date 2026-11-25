<?php
// ===== Танзимот =====
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_NAME', getenv('DB_NAME') ?: 'tajik_tours');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('BASE_URL', rtrim(getenv('BASE_URL') ?: '/tourism', '/'));

spl_autoload_register(static function (string $class): void {
    $prefix = 'Tourism\\Repositories\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $file = __DIR__ . '/src/Repositories/' . substr($class, strlen($prefix)) . '.php';
    if (is_file($file)) require $file;
});

session_start();
date_default_timezone_set('Asia/Dushanbe');

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
require_once __DIR__ . '/inc/functions.php';
