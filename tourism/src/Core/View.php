<?php
declare(strict_types=1);
namespace Tourism\Core;
use RuntimeException;

final class View
{
    public function __construct(private readonly string $viewsPath) {}
    public function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = $this->renderFile($template, $data);
        return $layout === null ? $content : $this->renderFile($layout, array_merge($data, ['content' => $content]));
    }
    private function renderFile(string $template, array $data): string
    {
        if (!preg_match('~^[A-Za-z0-9_/-]+$~', $template) || str_contains($template, '..')) {
            throw new RuntimeException('Invalid view name.');
        }
        $file = $this->viewsPath . '/' . $template . '.php';
        if (!is_file($file)) throw new RuntimeException("View not found: {$template}");
        extract($data, EXTR_SKIP);
        ob_start();
        try { require $file; }
        catch (\Throwable $error) { ob_end_clean(); throw $error; }
        return (string) ob_get_clean();
    }
    public function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    public function partial(string $name, array $data = []): string { return $this->renderFile('partials/' . $name, $data); }
    public function url(string $path = '/'): string
    {
        if (function_exists('url')) return url(ltrim($path, '/'));
        return rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/' . ltrim($path, '/');
    }
}
