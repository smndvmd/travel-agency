<?php
require __DIR__ . '/config.php';
$u = require_role('tourist');
$bookingsRepo = new Tourism\Repositories\BookingRepository();
$usersRepo = new Tourism\Repositories\UserRepository();
$reviewsRepo = new Tourism\Repositories\ReviewRepository();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'cancel') {
        $n = $bookingsRepo->cancelForUser((int)$_POST['id'], (int)$u['id']);
        flash($n->rowCount() ? 'Банд бекор шуд, ҷойҳо озод гардиданд.' : 'Бекор кардан имконнопазир аст.', $n->rowCount() ? 'ok' : 'err');
    } elseif (($_POST['action'] ?? '') === 'profile') {
        $usersRepo->updateProfile((int)$u['id'], trim($_POST['full_name'] ?? ''), trim($_POST['phone'] ?? ''));
        flash('Маълумот нигоҳ дошта шуд.');
    }
    redirect('profile.php');
}
$bookings = $bookingsRepo->forUser((int)$u['id']);
$reviews = $reviewsRepo->forUser((int)$u['id']);
$title = 'Утоқи ман'; include __DIR__ . '/inc/header.php'; ?>
<h1>Утоқи ман</h1>
<div class="card"><form method="post" class="filter"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
  <div><label>Ному насаб</label><input name="full_name" value="<?= e($u['full_name']) ?>" required></div>
  <div><label>Телефон</label><input name="phone" value="<?= e($u['phone']) ?>"></div>
  <div><label>Email</label><input value="<?= e($u['email']) ?>" disabled></div>
  <div><button>Нигоҳ доштан</button></div></form></div>
<h2>Бандҳои ман</h2>
<div class="scroll"><table><tr><th>Тур</th><th>Сана</th><th>Нафар</th><th>Маблағ</th><th>Ҳолат</th><th></th></tr>
<?php foreach ($bookings as $b): ?>
  <tr><td><a href="<?= url('tour.php?id=' . $b['tid']) ?>"><?= e($b['title']) ?></a></td>
    <td><?= e(date('d.m.Y', strtotime($b['start_date']))) ?></td><td><?= (int)$b['persons'] ?></td><td><?= money($b['total_price']) ?></td>
    <td><span class="badge <?= e($b['status']) ?>"><?= status_label($b['status']) ?></span></td>
    <td><?php if (in_array($b['status'], ['pending', 'confirmed']) && $b['start_date'] > date('Y-m-d')): ?>
      <form method="post" class="inline" onsubmit="return confirm('Банд бекор шавад?')"><?= csrf_field() ?>
        <input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= $b['id'] ?>"><button class="red">Бекор</button></form><?php endif; ?></td></tr>
<?php endforeach; if (!$bookings) echo '<tr><td colspan="6">Бандҳо нестанд.</td></tr>'; ?></table></div>
<h2>Фикрҳои ман</h2>
<?php foreach ($reviews as $r): ?><div class="card"><strong><?= e($r['title']) ?></strong> <?= stars((float)$r['rating']) ?>
  <span class="badge <?= e($r['status']) ?>"><?= status_label($r['status']) ?></span><p><?= nl2br(e($r['comment'])) ?></p></div>
<?php endforeach; if (!$reviews) echo '<p class="meta">Фикрҳо нестанд.</p>'; ?>
<?php include __DIR__ . '/inc/footer.php';
