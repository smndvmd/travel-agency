<?php
declare(strict_types=1);
namespace Tourism\Core;

abstract class Controller
{
    protected readonly View $view;
    public function __construct() { $this->view = new View(dirname(__DIR__, 2) . '/views'); }
    protected function render(string $template, array $data = [], int $status = 200, ?string $layout = 'layout'): void
    {
        if (function_exists('me')) {
            if (!array_key_exists('u', $data)) $data['u'] = me();
            if (!array_key_exists('flash', $data)) $data['flash'] = flash();
        }
        http_response_code($status);
        echo $this->view->render($template, $data, $layout);
    }
    protected function redirect(string $path): never
    {
        header('Location: ' . $this->view->url($path), true, 302);
        exit;
    }
    protected function abort(int $status, string $message): never
    {
        $title = $status === 404 ? 'Страница не найдена' : 'Ошибка';
        $this->render('errors/error', compact('title', 'status', 'message'), $status, null);
        exit;
    }
    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
