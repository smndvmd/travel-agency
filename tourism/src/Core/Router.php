<?php
declare(strict_types=1);
namespace Tourism\Core;

final class Router
{
    private array $routes = [];
    public function get(string $path, callable $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable $handler): void { $this->add('POST', $path, $handler); }
    private function add(string $method, string $path, callable $handler): void
    {
        $quoted = preg_quote('/' . trim($path, '/'), '#');
        $regex = preg_replace_callback('/\\\\\{(\w+)\\\\\}/', static fn(array $m): string => '(?P<' . $m[1] . '>\d+)', $quoted);
        $this->routes[] = ['method' => $method, 'regex' => '#^' . ($path === '/' ? '/' : $regex) . '/?$#', 'handler' => $handler];
    }
    public static function basePath(): string
    {
        $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        return rtrim($directory, '/');
    }
    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = self::basePath();
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) $path = substr($path, strlen($base));
        $path = '/' . trim(rawurldecode($path), '/');
        if ($path === '//') $path = '/';
        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) continue;
            if ($route['method'] !== strtoupper($method)) { $allowed[] = $route['method']; continue; }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            ($route['handler'])(...array_map('intval', array_values($params)));
            return;
        }
        if ($allowed) { http_response_code(405); header('Allow: ' . implode(', ', array_unique($allowed))); echo '405 Method Not Allowed'; return; }
        http_response_code(404);
        echo (new View(dirname(__DIR__, 2) . '/views'))->render('errors/error', [
            'title' => 'Page not found', 'code' => 404, 'message' => 'Page not found.',
        ]);
    }
}
