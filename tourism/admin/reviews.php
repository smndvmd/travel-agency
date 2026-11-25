<?php
require __DIR__ . '/../config.php';
$u = require_role('admin');
$reviewsRepo = new Tourism\Repositories\ReviewRepository();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)$_POST['id']; $a = $_POST['action'] ?? '';
    if ($a === 'approve') $reviewsRepo->setStatus($id, 'approved');
    if ($a === 'reject')  $reviewsRepo->setStatus($id, 'rejected');
    if ($a === 'delete')  $reviewsRepo->delete($id);
    flash('Иҷро шуд.'); redirect('admin/reviews.php?status=' . urlencode($_GET['status'] ?? 'pending'));
}
$f = $_GET['status'] ?? 'pending';
$rows = $reviewsRepo->forModeration($f);
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
