<?php
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
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
        $st = db()->prepare('SELECT id,full_name,email,phone,role,is_active FROM users WHERE id=?');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
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
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? 'x')) { http_response_code(400); exit('Хатогии CSRF.'); }
}
function stars(float $r): string {
    $f = (int)round($r);
    return '<span class="stars">' . str_repeat('★', $f) . str_repeat('☆', 5 - $f) . '</span>';
}
function photo_src(?string $p): string {
    if (!$p) return '';
    return preg_match('~^https?://~', $p) ? $p : url($p);
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
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/' . $name)) return null;
    return 'uploads/' . $name;
}
