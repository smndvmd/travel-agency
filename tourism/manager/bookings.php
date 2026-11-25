<?php
require __DIR__ . '/../config.php';
$u = require_role('manager');
$bookingsRepo = new Tourism\Repositories\BookingRepository();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $new = ['confirm' => 'confirmed', 'reject' => 'rejected'][$_POST['action'] ?? ''] ?? null;
    if ($new) {
        $n = $bookingsRepo->setStatusForManager((int)$_POST['id'], (int)$u['id'], $new);
        flash($n->rowCount() ? ($new === 'confirmed' ? 'Банд тасдиқ шуд.' : 'Банд рад шуд, ҷойҳо озод гардиданд.') : 'Амалро иҷро карда натавонистем.', $n->rowCount() ? 'ok' : 'err');
    }
    redirect('manager/bookings.php');
}
$f = $_GET['status'] ?? 'pending';
$rows = $bookingsRepo->forManager((int)$u['id'], $f);
$title = 'Бандҳо'; include __DIR__ . '/../inc/header.php'; ?>
<h1>Бандҳо</h1>
<p><?php foreach (['pending' => 'Дар интизорӣ', 'confirmed' => 'Тасдиқшуда', 'rejected' => 'Рад шуда', 'cancelled' => 'Бекоршуда', 'all' => 'Ҳама'] as $k => $v): ?>
  <a class="btn small <?= $f === $k ? '' : 'gray' ?>" href="?status=<?= $k ?>"><?= $v ?></a> <?php endforeach; ?></p>
<div class="scroll"><table><tr><th>Сайёҳ</th><th>Тур</th><th>Сана</th><th>Нафар</th><th>Маблағ</th><th>Ҳолат</th><th></th></tr>
<?php foreach ($rows as $b): ?><tr><td><?= e($b['full_name']) ?><br><span class="meta"><?= e($b['phone']) ?> · <?= e($b['email']) ?></span></td>
<td><?= e($b['title']) ?></td><td><?= e(date('d.m.Y', strtotime($b['start_date']))) ?></td><td><?= (int)$b['persons'] ?></td><td><?= money($b['total_price']) ?></td>
<td><span class="badge <?= e($b['status']) ?>"><?= status_label($b['status']) ?></span></td>
<td><?php if ($b['status'] === 'pending'): ?>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="confirm"><button>Тасдиқ</button></form>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="reject"><button class="red">Рад</button></form><?php endif; ?></td></tr>
<?php endforeach; if (!$rows) echo '<tr><td colspan="7">Бандҳо нестанд.</td></tr>'; ?></table></div>
<?php include __DIR__ . '/../inc/footer.php';
