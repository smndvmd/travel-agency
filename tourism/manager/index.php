<?php
require __DIR__ . '/../config.php';
$u = require_role('manager');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $tid = (int)$_POST['id'];
    if (($_POST['action'] ?? '') === 'toggle')
        db()->prepare('UPDATE tours SET is_active=1-is_active WHERE id=? AND manager_id=?')->execute([$tid, $u['id']]);
    if (($_POST['action'] ?? '') === 'delete') {
        try { db()->prepare('DELETE FROM tours WHERE id=? AND manager_id=?')->execute([$tid, $u['id']]); flash('Тур нест карда шуд.'); }
        catch (PDOException $e) { flash('Нест кардан имконнопазир аст (аллакай бандҳо ҳастанд). Турро пинҳон кунед.', 'err'); }
    }
    redirect('manager/index.php');
}
$st = db()->prepare("SELECT t.*, (SELECT COUNT(*) FROM tour_dates WHERE tour_id=t.id AND start_date>=CURDATE()) AS dcount FROM tours t WHERE manager_id=? ORDER BY id DESC");
$st->execute([$u['id']]); $tours = $st->fetchAll();
$title = 'Турҳои ман'; include __DIR__ . '/../inc/header.php'; ?>
<h1>Турҳои ман <a class="btn small" href="<?= url('manager/tour_edit.php') ?>">+ Тури нав</a></h1>
<div class="scroll"><table><tr><th>Тур</th><th>Минтақа</th><th>Рӯз</th><th>Нарх</th><th>Санаҳо</th><th>Ҳолат</th><th></th></tr>
<?php foreach ($tours as $t): ?>
<tr><td><?= e($t['title']) ?></td><td><?= e($t['region']) ?></td><td><?= (int)$t['duration_days'] ?></td><td><?= money($t['price']) ?></td>
<td><a href="<?= url('manager/dates.php?tour_id=' . $t['id']) ?>"><?= (int)$t['dcount'] ?> сана — идора</a></td>
<td><span class="badge <?= $t['is_active'] ? 'open' : 'closed' ?>"><?= $t['is_active'] ? 'Фаъол' : 'Пинҳон' ?></span></td>
<td style="white-space:nowrap"><a class="btn small" href="<?= url('manager/tour_edit.php?id=' . $t['id']) ?>">Таҳрир</a>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="gray">Пинҳон/Нишон</button></form>
<form method="post" class="inline" onsubmit="return confirm('Нест карда шавад?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="action" value="delete"><button class="red">✕</button></form></td></tr>
<?php endforeach; if (!$tours) echo '<tr><td colspan="7">Ҳанӯз тур нест.</td></tr>'; ?></table></div>
<?php include __DIR__ . '/../inc/footer.php';
