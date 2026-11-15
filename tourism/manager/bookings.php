<?php
require __DIR__ . '/../config.php';
$u = require_role('manager');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $new = ['confirm' => 'confirmed', 'reject' => 'rejected'][$_POST['action'] ?? ''] ?? null;
    if ($new) {
        $n = db()->prepare("UPDATE bookings b JOIN tour_dates d ON d.id=b.tour_date_id JOIN tours t ON t.id=d.tour_id
            SET b.status=? WHERE b.id=? AND t.manager_id=? AND b.status='pending'");
        $n->execute([$new, (int)$_POST['id'], $u['id']]);
        flash($n->rowCount() ? ($new === 'confirmed' ? 'Банд тасдиқ шуд.' : 'Банд рад шуд, ҷойҳо озод гардиданд.') : 'Амалро иҷро карда натавонистем.', $n->rowCount() ? 'ok' : 'err');
    }
    redirect('manager/bookings.php');
}
$f = $_GET['status'] ?? 'pending';
$sql = "SELECT b.*, d.start_date, t.title, us.full_name, us.phone, us.email FROM bookings b
  JOIN tour_dates d ON d.id=b.tour_date_id JOIN tours t ON t.id=d.tour_id JOIN users us ON us.id=b.user_id WHERE t.manager_id=?";
$p = [$u['id']];
if (in_array($f, ['pending', 'confirmed', 'rejected', 'cancelled'])) { $sql .= ' AND b.status=?'; $p[] = $f; }
$st = db()->prepare($sql . ' ORDER BY b.created_at DESC'); $st->execute($p); $rows = $st->fetchAll();
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
