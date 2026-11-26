<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === '1' ? '1' : '0');
if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class): void {
        if (!str_starts_with($class, 'Tourism\\')) return;
        $file = BASE_PATH . '/src/' . str_replace('\\', '/', substr($class, strlen('Tourism\\'))) . '.php';
        if (is_file($file)) require $file;
    });
}

set_exception_handler(static function (Throwable $exception): void {
    error_log((string)$exception);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $debug = getenv('APP_DEBUG') === '1';
    $view = new Tourism\Core\View(BASE_PATH . '/views');
    echo $view->render('errors/error', [
        'title' => 'Хатои дохилӣ', 'code' => 500,
        'message' => $debug ? $exception->getMessage() : 'Дар сервер хатое рух дод.',
    ]);
});

$site = new Tourism\Controllers\SiteController();
$auth = new Tourism\Controllers\AuthController();
$router = new Tourism\Core\Router();
$router->get('/', [$site, 'home']);
$router->get('/tours', [$site, 'home']);
$router->get('/tour/{id}', [$site, 'tour']);
$router->get('/login', [$auth, 'showLogin']);
$router->post('/login', [$auth, 'login']);
$router->get('/register', [$auth, 'showRegister']);
$router->post('/register', [$auth, 'register']);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
