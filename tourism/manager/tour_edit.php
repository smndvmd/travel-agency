<?php
require __DIR__ . '/../config.php';
$u = require_role('manager');
$toursRepo = new Tourism\Repositories\TourRepository();
$photosRepo = new Tourism\Repositories\PhotoRepository();
$id = (int)($_GET['id'] ?? 0); $t = null;
if ($id) {
    $t = $toursRepo->findForManager($id, (int)$u['id']);
    if (!$t) { http_response_code(404); exit('Тур ёфт нашуд.'); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['action'] ?? 'save';
    if ($a === 'save') {
        $f = [trim($_POST['title']), trim($_POST['region']), trim($_POST['short_desc']), trim($_POST['description']), trim($_POST['itinerary']),
              max(1, (int)$_POST['duration_days']), max(0, (float)$_POST['price']), in_array($_POST['difficulty'], ['easy', 'medium', 'hard']) ? $_POST['difficulty'] : 'medium'];
        if ($f[0] === '' || $f[1] === '') { flash('Ном ва минтақаро пур кунед.', 'err'); redirect('manager/tour_edit.php' . ($id ? "?id=$id" : '')); }
        $fields = ['title' => $f[0], 'region' => $f[1], 'short_desc' => $f[2], 'description' => $f[3], 'itinerary' => $f[4], 'duration_days' => $f[5], 'price' => $f[6], 'difficulty' => $f[7]];
        if ($id) $toursRepo->update($id, (int)$u['id'], $fields);
        else $id = $toursRepo->create((int)$u['id'], $fields);
        foreach ($_FILES['photos']['name'] ?? [] as $i => $n) {
            if ($n === '') continue;
            $path = upload_photo(['name' => $n, 'tmp_name' => $_FILES['photos']['tmp_name'][$i], 'error' => $_FILES['photos']['error'][$i]]);
            if ($path) {
                $photosRepo->add($id, $path, $photosRepo->countForTour($id) === 0);
            }
        }
        flash('Нигоҳ дошта шуд.'); redirect('manager/tour_edit.php?id=' . $id);
    }
    if ($id && $a === 'photo_delete') {
        $pid = (int)$_POST['pid'];
        if ($fp = $photosRepo->findPath($pid, $id)) {
            $photosRepo->delete($pid, $id);
            if (strpos($fp, 'uploads/') === 0) @unlink(__DIR__ . '/../' . $fp);
        }
    }
    if ($id && $a === 'photo_main') {
        $photosRepo->setMain((int)$_POST['pid'], $id);
    }
    if ($id && $a === 'photo_url' && preg_match('~^https?://~', trim($_POST['url'] ?? ''))) {
        $photosRepo->add($id, trim($_POST['url']), $photosRepo->countForTour($id) === 0);
    }
    redirect('manager/tour_edit.php?id=' . $id);
}
$photos = [];
if ($id) $photos = $photosRepo->forTour($id);
$v = fn($k, $d = '') => e($t[$k] ?? $d);
$title = $id ? 'Таҳрири тур' : 'Тури нав'; include __DIR__ . '/../inc/header.php'; ?>
<h1><?= $title ?></h1>
<form class="card" method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="save">
  <div class="row"><label>Ном</label><input name="title" value="<?= $v('title') ?>" required></div>
  <div class="filter row"><div><label>Минтақа</label><input name="region" value="<?= $v('region') ?>" placeholder="Помир, Кӯҳҳои Фон..." required></div>
    <div><label>Давомнокӣ (рӯз)</label><input type="number" min="1" name="duration_days" value="<?= $v('duration_days', 3) ?>"></div>
    <div><label>Нарх (барои 1 нафар)</label><input type="number" min="0" step="1" name="price" value="<?= $v('price', 0) ?>"></div>
    <div><label>Душворӣ</label><select name="difficulty"><?php foreach (['easy', 'medium', 'hard'] as $d): ?><option value="<?= $d ?>" <?= ($t['difficulty'] ?? 'medium') === $d ? 'selected' : '' ?>><?= difficulty_label($d) ?></option><?php endforeach; ?></select></div></div>
  <div class="row"><label>Тавсифи кӯтоҳ</label><input name="short_desc" maxlength="300" value="<?= $v('short_desc') ?>" required></div>
  <div class="row"><label>Тавсифи пурра</label><textarea name="description" required><?= $v('description') ?></textarea></div>
  <div class="row"><label>Барнома аз рӯи рӯзҳо (ҳар рӯз дар сатри алоҳида)</label><textarea name="itinerary" rows="6" required><?= $v('itinerary') ?></textarea></div>
  <div class="row"><label>Аксҳо (метавонед якчанд интихоб кунед)</label><input type="file" name="photos[]" multiple accept="image/*"></div>
  <button>Нигоҳ доштан</button> <a class="btn gray" href="<?= url('manager/index.php') ?>">Бозгашт</a>
</form>
<?php if ($id): ?>
<h2>Аксҳо</h2><div class="grid">
<?php foreach ($photos as $p): ?><div class="card"><img src="<?= e(photo_src($p['file_path'])) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:8px" alt="">
  <p><?= $p['is_main'] ? '<span class="badge open">Асосӣ</span>' : '' ?></p>
  <?php if (!$p['is_main']): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="photo_main"><input type="hidden" name="pid" value="<?= $p['id'] ?>"><button class="gray">Асосӣ</button></form><?php endif; ?>
  <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="photo_delete"><input type="hidden" name="pid" value="<?= $p['id'] ?>"><button class="red">Нест</button></form></div>
<?php endforeach; ?></div>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="photo_url"><label>Ё суроғаи акс (URL)</label><input name="url" placeholder="https://..."> <br><br><button>Илова</button></form>
<p><a class="btn" href="<?= url('manager/dates.php?tour_id=' . $id) ?>">Санаҳои сафарро идора кардан →</a></p>
<?php endif; include __DIR__ . '/../inc/footer.php';
