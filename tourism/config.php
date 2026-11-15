<?php
// ===== Танзимот =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'tajik_tours');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/tourism');   // номи папка дар htdocs

session_start();
date_default_timezone_set('Asia/Dushanbe');

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
require_once __DIR__ . '/inc/functions.php';
