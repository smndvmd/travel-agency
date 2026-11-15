<?php
require __DIR__ . '/../config.php';
$u = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)$_POST['id']; $a = $_POST['action'] ?? '';
    if ($a === 'approve') db()->prepare("UPDATE reviews SET status='approved' WHERE id=?")->execute([$id]);
    if ($a === 'reject')  db()->prepare("UPDATE reviews SET status='rejected' WHERE id=?")->execute([$id]);
    if ($a === 'delete')  db()->prepare('DELETE FROM reviews WHERE id=?')->execute([$id]);
    flash('Иҷро шуд.'); redirect('admin/reviews.php?status=' . urlencode($_GET['status'] ?? 'pending'));
}
$f = $_GET['status'] ?? 'pending';
$sql = "SELECT r.*, u.full_name, t.title FROM reviews r JOIN users u ON u.id=r.user_id JOIN tours t ON t.id=r.tour_id";
$p = [];
if (in_array($f, ['pending', 'approved', 'rejected'])) { $sql .= ' WHERE r.status=?'; $p[] = $f; }
$st = db()->prepare($sql . ' ORDER BY r.created_at DESC'); $st->execute($p); $rows = $st->fetchAll();
$title = 'Модератсия'; include __DIR__ . '/../inc/header.php'; ?>
<h1>Модератсияи фикрҳо</h1>
<p><?php foreach (['pending' => 'Дар интизорӣ', 'approved' => 'Нашршуда', 'rejected' => 'Радшуда', 'all' => 'Ҳама'] as $k => $v): ?>
  <a class="btn small <?= $f === $k ? '' : 'gray' ?>" href="?status=<?= $k ?>"><?= $v ?></a> <?php endforeach; ?></p>
<?php foreach ($rows as $r): ?><div class="card">
  <strong><?= e($r['full_name']) ?></strong> → <a href="<?= url('tour.php?id=' . $r['tour_id']) ?>"><?= e($r['title']) ?></a>
  <?= stars((float)$r['rating']) ?> <span class="badge <?= e($r['status']) ?>"><?= status_label($r['status']) ?></span>
  <p><?= nl2br(e($r['comment'])) ?></p>
  <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
    <?php if ($r['status'] !== 'approved'): ?><button name="action" value="approve">Нашр кардан</button><?php endif; ?>
    <?php if ($r['status'] !== 'rejected'): ?><button name="action" value="reject" class="gray">Рад кардан</button><?php endif; ?>
    <button name="action" value="delete" class="red" onclick="return confirm('Нест карда шавад?')">Нест</button></form></div>
<?php endforeach; if (!$rows) echo '<div class="card">Фикр нест.</div>'; ?>
<?php include __DIR__ . '/../inc/footer.php';
