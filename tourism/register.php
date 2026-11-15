<?php
require __DIR__ . '/config.php';
if (me()) redirect('');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['full_name'] ?? ''); $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? ''); $pass = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Ном ва email-ро дуруст ворид кунед.';
    elseif (strlen($pass) < 6) $err = 'Парол ҳадди ақал 6 аломат бошад.';
    else {
        $c = db()->prepare('SELECT 1 FROM users WHERE email=?'); $c->execute([$email]);
        if ($c->fetch()) $err = 'Ин email аллакай сабт шудааст.';
        else {
            db()->prepare("INSERT INTO users (full_name,email,phone,password_hash,role) VALUES (?,?,?,?, 'tourist')")
                ->execute([$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT)]);
            $_SESSION['uid'] = (int)db()->lastInsertId(); session_regenerate_id(true);
            flash('Хуш омадед! Сабти ном анҷом ёфт.'); redirect('');
        }
    }
}
$title = 'Сабти ном'; include __DIR__ . '/inc/header.php'; ?>
<div class="card narrow"><h1>Сабти ном</h1>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
  <div class="row"><label>Ному насаб</label><input name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>"></div>
  <div class="row"><label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
  <div class="row"><label>Телефон</label><input name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></div>
  <div class="row"><label>Парол</label><input type="password" name="password" required></div>
  <button>Сабти ном</button></form></div>
<?php include __DIR__ . '/inc/footer.php';
