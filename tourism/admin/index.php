<?php
require __DIR__ . '/../config.php';
$u = require_role('admin');
$usersRepo = new Tourism\Repositories\UserRepository();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        $n = trim($_POST['full_name']); $em = trim($_POST['email']); $pw = $_POST['password'] ?? '';
        if ($n === '' || !filter_var($em, FILTER_VALIDATE_EMAIL) || strlen($pw) < 6) flash('Маълумотро дуруст пур кунед (парол ≥ 6 аломат).', 'err');
        else try {
            $usersRepo->createManager($n, $em, trim($_POST['phone'] ?? ''), password_hash($pw, PASSWORD_DEFAULT));
            flash('Менеҷер илова шуд.');
        } catch (PDOException $e) { flash('Ин email аллакай ҳаст.', 'err'); }
    } elseif ($a === 'toggle') {
        $manager = $usersRepo->findById((int)$_POST['id']);
        if ($manager && $manager['role'] === 'manager') $usersRepo->setManagerActive((int)$manager['id'], !(bool)$manager['is_active']);
    } elseif ($a === 'reset') {
        $usersRepo->managerPasswordUpdateStatement()
            ->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), (int)$_POST['id']]); flash('Парол иваз шуд.');
    }
    redirect('admin/index.php');
}
$stats = (new Tourism\Repositories\DashboardRepository())->adminStats();
$mgrs = $usersRepo->managers();
$title = 'Менеҷерҳо'; include __DIR__ . '/../inc/header.php'; ?>
<h1>Панели маъмур</h1>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
  <div class="card"><div class="price"><?= $stats['tourists'] ?></div>Сайёҳон</div>
  <div class="card"><div class="price"><?= $stats['tours'] ?></div>Турҳо</div>
  <div class="card"><div class="price"><?= $stats['bookings'] ?></div>Бандҳои фаъол</div>
  <div class="card"><div class="price"><?= $stats['pending'] ?></div><a href="<?= url('admin/reviews.php') ?>">Фикрҳои интизор</a></div></div>
<h2>Менеҷерҳо</h2>
<form class="card filter" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div><label>Ному насаб</label><input name="full_name" required></div><div><label>Email</label><input type="email" name="email" required></div>
  <div><label>Телефон</label><input name="phone"></div><div><label>Парол</label><input type="password" name="password" required></div>
  <div><button>Илова кардан</button></div></form>
<div class="scroll"><table><tr><th>Ном</th><th>Email</th><th>Турҳо</th><th>Ҳолат</th><th></th></tr>
<?php foreach ($mgrs as $m): ?><tr><td><?= e($m['full_name']) ?></td><td><?= e($m['email']) ?></td><td><?= (int)$m['tc'] ?></td>
<td><span class="badge <?= $m['is_active'] ? 'open' : 'closed' ?>"><?= $m['is_active'] ? 'Фаъол' : 'Масдуд' ?></span></td>
<td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="gray"><?= $m['is_active'] ? 'Масдуд кардан' : 'Фаъол кардан' ?></button></form>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= $m['id'] ?>">
<input type="password" name="password" placeholder="Парол нав" minlength="6" required style="width:120px"> <button>Иваз</button></form></td></tr>
<?php endforeach; if (!$mgrs) echo '<tr><td colspan="5">Менеҷер нест.</td></tr>'; ?></table></div>
<?php include __DIR__ . '/../inc/footer.php';
