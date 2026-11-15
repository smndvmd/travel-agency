<?php
require __DIR__ . '/config.php';
$title = 'Турҳо';
$regions = db()->query("SELECT DISTINCT region FROM tours WHERE is_active=1 ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);

$where = ['t.is_active=1']; $p = [];
if (($_GET['q'] ?? '') !== '')      { $where[] = '(t.title LIKE ? OR t.short_desc LIKE ?)'; $p[] = '%'.$_GET['q'].'%'; $p[] = '%'.$_GET['q'].'%'; }
if (($_GET['region'] ?? '') !== '') { $where[] = 't.region=?'; $p[] = $_GET['region']; }
$dur = $_GET['duration'] ?? '';
if ($dur === '1-3') $where[] = 't.duration_days BETWEEN 1 AND 3';
elseif ($dur === '4-7') $where[] = 't.duration_days BETWEEN 4 AND 7';
elseif ($dur === '8+') $where[] = 't.duration_days >= 8';
if (($_GET['min'] ?? '') !== '' && is_numeric($_GET['min'])) { $where[] = 't.price>=?'; $p[] = $_GET['min']; }
if (($_GET['max'] ?? '') !== '' && is_numeric($_GET['max'])) { $where[] = 't.price<=?'; $p[] = $_GET['max']; }

$sql = "SELECT t.*,
  (SELECT file_path FROM photos WHERE tour_id=t.id ORDER BY is_main DESC, id LIMIT 1) AS cover,
  (SELECT ROUND(AVG(rating),1) FROM reviews WHERE tour_id=t.id AND status='approved') AS rating,
  (SELECT COUNT(*) FROM reviews WHERE tour_id=t.id AND status='approved') AS rcount,
  (SELECT COALESCE(SUM(free_seats),0) FROM v_date_seats WHERE tour_id=t.id AND status='open' AND start_date>=CURDATE()) AS free_total
  FROM tours t WHERE " . implode(' AND ', $where) . " ORDER BY t.created_at DESC";
$st = db()->prepare($sql); $st->execute($p); $tours = $st->fetchAll();
$hs = db()->query("SELECT (SELECT COUNT(*) FROM tours WHERE is_active=1) t, (SELECT COUNT(DISTINCT region) FROM tours WHERE is_active=1) r, (SELECT COALESCE(SUM(persons),0) FROM bookings WHERE status='confirmed') p")->fetch();
include __DIR__ . '/inc/header.php';
?>
<section class="hero">
  <span class="shape s1">🏔️</span><span class="shape s2">🌄</span><span class="shape s3">🧭</span>
  <span class="pill"><i></i> Сафарҳои нав барои мавсими <?= date('Y') + (date('n') > 9 ? 1 : 0) ?></span>
  <h1>Тоҷикистонро бо <span class="grad-text">чашмони нав</span> кашф кунед</h1>
  <p class="lead">Аз Искандаркӯл то Помир — турҳои боэътимод бо роҳнамоҳои маҳаллӣ. Интихоб кунед, ҷойро банд намоед ва сафарро оғоз кунед.</p>
  <div class="cta"><a class="btn" href="#tours">Турҳоро дидан →</a><a class="btn gray" href="<?= url('register.php') ?>">Сабти ном</a></div>
  <div class="stats">
    <div class="stat"><b data-count="<?= (int)$hs['t'] ?>">0</b><span>Турҳо</span></div>
    <div class="stat"><b data-count="<?= (int)$hs['r'] ?>">0</b><span>Минтақаҳо</span></div>
    <div class="stat"><b data-count="<?= (int)$hs['p'] ?>">0</b><span>Сайёҳони шод</span></div>
  </div>
</section>
<h1 id="tours">Турҳо дар Тоҷикистон</h1>
<form class="card filter" method="get">
  <div><label>Ҷустуҷӯ</label><input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Номи тур..."></div>
  <div><label>Минтақа</label><select name="region"><option value="">Ҳама</option>
    <?php foreach ($regions as $r): ?><option <?= ($_GET['region'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></div>
  <div><label>Давомнокӣ</label><select name="duration">
    <option value="">Ҳама</option>
    <?php foreach (['1-3' => '1–3 рӯз', '4-7' => '4–7 рӯз', '8+' => '8+ рӯз'] as $k => $v): ?>
      <option value="<?= $k ?>" <?= $dur === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
  <div><label>Нарх аз</label><input type="number" name="min" value="<?= e($_GET['min'] ?? '') ?>"></div>
  <div><label>Нарх то</label><input type="number" name="max" value="<?= e($_GET['max'] ?? '') ?>"></div>
  <div><button>Ҷустуҷӯ</button> <a class="btn gray" href="<?= url() ?>">Тоза</a></div>
</form>
<?php if (!$tours): ?><div class="card">Тур ёфт нашуд.</div><?php endif; ?>
<div class="grid">
<?php foreach ($tours as $t): ?>
  <div class="card tour">
    <a class="cover" href="<?= url('tour.php?id=' . $t['id']) ?>" style="<?= $t['cover'] ? "background-image:url('" . e(photo_src($t['cover'])) . "')" : '' ?>">
      <span class="chip">📍 <?= e($t['region']) ?></span>
      <?php if ($t['rating']): ?><span class="chip r">★ <?= e($t['rating']) ?></span><?php endif; ?></a>
    <div class="body">
      <h3 style="margin:0"><a href="<?= url('tour.php?id=' . $t['id']) ?>"><?= e($t['title']) ?></a></h3>
      <div class="meta"><span>🕒 <?= (int)$t['duration_days'] ?> рӯз</span><span>⛰ <?= difficulty_label($t['difficulty']) ?></span>
        <span><?= $t['rating'] ? stars((float)$t['rating']) . ' ' . $t['rating'] . ' (' . $t['rcount'] . ')' : 'Баҳо нест' ?></span></div>
      <p style="flex:1"><?= e($t['short_desc']) ?></p>
      <div class="meta"><?= $t['free_total'] > 0 ? 'Ҷойҳои холӣ: ' . (int)$t['free_total'] : '<span class="seats-low">Ҷой нест</span>' ?></div>
      <div style="display:flex;justify-content:space-between;align-items:center">
        <span><small class="meta">аз</small><br><span class="price"><?= money($t['price']) ?></span></span>
        <span style="display:flex;gap:8px"><a class="btn small gray" href="<?= url('tour.php?id=' . $t['id']) ?>">Тафсилот</a><a class="btn small" href="<?= url('tour.php?id=' . $t['id']) ?>#dates">Банд кардан →</a></span></div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/inc/footer.php';
