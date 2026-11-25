<?php
require __DIR__ . '/../config.php';
$u = require_role('manager');
$toursRepo = new Tourism\Repositories\TourRepository();
$datesRepo = new Tourism\Repositories\DateRepository();
$tid = (int)($_GET['tour_id'] ?? 0);
$t = $toursRepo->findForManager($tid, (int)$u['id']);
if (!$t) { http_response_code(404); exit('Тур ёфт нашуд.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        $d = $_POST['start_date'] ?? ''; $seats = (int)($_POST['total_seats'] ?? 0);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || $d < date('Y-m-d') || $seats < 1) flash('Санаи оянда ва шумораи ҷойҳоро дуруст ворид кунед.', 'err');
        else try { $datesRepo->create($tid, $d, $seats); flash('Сана илова шуд.'); }
        catch (PDOException $e) { flash('Ин сана аллакай ҳаст.', 'err'); }
    } else {
        $did = (int)($_POST['id'] ?? 0);
        $row = $datesRepo->findForTour($did, $tid);
        if ($row) {
            if ($a === 'seats') {
                $n = (int)$_POST['total_seats'];
                if ($n < $row['booked_seats'] || $n < 1) flash('Ҷойҳо аз шумораи бандшуда (' . $row['booked_seats'] . ') кам буда наметавонанд.', 'err');
                else { $datesRepo->updateSeats($did, $tid, $n); flash('Нав карда шуд.'); }
            } elseif ($a === 'toggle') $datesRepo->toggle($did, $tid);
            elseif ($a === 'delete') {
                if ($row['booked_seats'] > 0) flash('Санаро бо бандҳо нест кардан мумкин нест. Онро пӯшед.', 'err');
                else { $datesRepo->delete($did, $tid); flash('Сана нест шуд.'); }
            }
        }
    }
    redirect('manager/dates.php?tour_id=' . $tid);
}
$dates = $datesRepo->forTour($tid);
$title = 'Санаҳо'; include __DIR__ . '/../inc/header.php'; ?>
<h1>Санаҳои сафар: <?= e($t['title']) ?></h1>
<form class="card filter" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div><label>Санаи оғоз</label><input type="date" name="start_date" min="<?= date('Y-m-d') ?>" required></div>
  <div><label>Шумораи ҷойҳо</label><input type="number" name="total_seats" min="1" value="10" required></div>
  <div><button>Илова кардан</button></div></form>
<div class="scroll"><table><tr><th>Сана</th><th>Ҷойҳо (ҳамагӣ)</th><th>Банд</th><th>Холӣ</th><th>Ҳолат</th><th></th></tr>
<?php foreach ($dates as $d): ?><tr><td><?= e(date('d.m.Y', strtotime($d['start_date']))) ?></td>
<td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="seats"><input type="hidden" name="id" value="<?= $d['id'] ?>">
  <input type="number" name="total_seats" value="<?= $d['total_seats'] ?>" min="1" style="width:80px"> <button>OK</button></form></td>
<td><?= (int)$d['booked_seats'] ?></td><td><strong><?= (int)$d['free_seats'] ?></strong></td>
<td><span class="badge <?= e($d['status']) ?>"><?= status_label($d['status']) ?></span><?= $d['free_seats'] <= 0 ? ' <span class="badge closed">Ҷой нест</span>' : '' ?></td>
<td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $d['id'] ?>"><button class="gray"><?= $d['status'] === 'open' ? 'Пӯшидан' : 'Кушодан' ?></button></form>
<form method="post" class="inline" onsubmit="return confirm('Нест карда шавад?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $d['id'] ?>"><button class="red">✕</button></form></td></tr>
<?php endforeach; if (!$dates) echo '<tr><td colspan="6">Санаҳо нестанд.</td></tr>'; ?></table></div>
<p><a href="<?= url('manager/index.php') ?>">← Бозгашт</a></p>
<?php include __DIR__ . '/../inc/footer.php';
