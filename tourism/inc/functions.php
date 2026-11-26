<?php
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p) { header('Location: ' . url($p)); exit; }
function money($n): string { return number_format((float)$n, 0, '.', ' ') . ' сом.'; }
function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function me(): ?array {
    if (empty($_SESSION['uid'])) return null;
    static $u = false;
    if ($u === false) {
        $u = (new \Tourism\Repositories\UserRepository())->findById((int)$_SESSION['uid']);
        if ($u && !$u['is_active']) { session_destroy(); $u = null; }
    }
    return $u;
}
function require_role(string ...$roles): array {
    $u = me();
    if (!$u) { flash('Аввал ба система ворид шавед.', 'err'); redirect('login.php'); }
    if ($roles && !in_array($u['role'], $roles, true)) { http_response_code(403); exit('Дастрасӣ манъ аст.'); }
    return $u;
}
function csrf_field(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function csrf_check(): void {
    $submitted = $_POST['csrf'] ?? '';
    $expected = $_SESSION['csrf'] ?? '';
    if (!is_string($submitted) || !is_string($expected) || $expected === '' || !hash_equals($expected, $submitted)) { http_response_code(400); exit('Хатогии CSRF.'); }
}
function stars(float $r): string {
    $f = (int)round($r);
    return '<span class="stars">' . str_repeat('★', $f) . str_repeat('☆', 5 - $f) . '</span>';
}
function photo_src(?string $p): string {
    if (!$p) return '';
    return preg_match('~^https?://~', $p) ? $p : url($p);
}
function upload_storage_dir(): string {
    $publicDocumentRoot = BASE_URL === '' || str_ends_with(BASE_URL, '/public');
    return dirname(__DIR__) . ($publicDocumentRoot ? '/public/uploads' : '/uploads');
}
function upload_storage_path(string $relativePath): ?string {
    if (!str_starts_with($relativePath, 'uploads/')) return null;
    return upload_storage_dir() . '/' . basename($relativePath);
}
function difficulty_label(string $d): string {
    return ['easy' => 'Осон', 'medium' => 'Миёна', 'hard' => 'Душвор'][$d] ?? $d;
}
function status_label(string $s): string {
    return ['pending' => 'Дар интизорӣ', 'confirmed' => 'Тасдиқ шуд', 'rejected' => 'Рад шуд',
            'cancelled' => 'Бекор шуд', 'approved' => 'Нашр шуд', 'open' => 'Кушода', 'closed' => 'Баста'][$s] ?? $s;
}
function upload_photo(array $file): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) return null;
    if (!@getimagesize($file['tmp_name'])) return null;
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], upload_storage_dir() . '/' . $name)) return null;
    return 'uploads/' . $name;
}
