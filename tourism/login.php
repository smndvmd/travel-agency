<?php
require __DIR__ . '/config.php';
if (me()) redirect('');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare('SELECT * FROM users WHERE email=?'); $st->execute([trim($_POST['email'] ?? '')]);
    $u = $st->fetch();
    if (!$u || !password_verify($_POST['password'] ?? '', $u['password_hash'])) $err = 'Email ё парол нодуруст аст.';
    elseif (!$u['is_active']) $err = 'Ҳисоби шумо баста шудааст.';
    else {
        session_regenerate_id(true); $_SESSION['uid'] = (int)$u['id'];
        redirect(['admin' => 'admin/index.php', 'manager' => 'manager/index.php'][$u['role']] ?? '');
    }
}
$title = 'Воридшавӣ'; include __DIR__ . '/inc/header.php'; ?>
<div class="card narrow"><h1>Воридшавӣ</h1>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
  <div class="row"><label>Email</label><input type="email" name="email" required></div>
  <div class="row"><label>Парол</label><input type="password" name="password" required></div>
  <button>Ворид шудан</button> <a href="<?= url('register.php') ?>">Сабти ном</a></form></div>
<?php include __DIR__ . '/inc/footer.php';
