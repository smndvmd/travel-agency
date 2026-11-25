<?php
require __DIR__ . '/config.php';
$id = (int)($_GET['id'] ?? 0);
$toursRepo = new Tourism\Repositories\TourRepository();
$datesRepo = new Tourism\Repositories\DateRepository();
$photosRepo = new Tourism\Repositories\PhotoRepository();
$reviewsRepo = new Tourism\Repositories\ReviewRepository();
$bookingsRepo = new Tourism\Repositories\BookingRepository();
$t = $toursRepo->findActive($id);
if (!$t) { http_response_code(404); exit('Тур ёфт нашуд.'); }

$photos = $photosRepo->pathsForTour($id);
$dates = $datesRepo->forTour($id, true);
$reviews = $reviewsRepo->forTour($id);
$avg = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

$u = me(); $canReview = false; $myReview = null; $bookingId = 0;
if ($u && $u['role'] === 'tourist') {
    $bookingId = $bookingsRepo->findReviewable((int)$u['id'], $id) ?? 0;
    $myReview = $reviewsRepo->findByUserAndTour((int)$u['id'], $id);
    $canReview = $bookingId && !$myReview;
}
$title = $t['title']; include __DIR__ . '/inc/header.php'; ?>
<h1><?= e($t['title']) ?></h1>
<div class="meta" style="margin-bottom:12px"><span>📍 <?= e($t['region']) ?></span><span>🕒 <?= (int)$t['duration_days'] ?> рӯз</span>
  <span>⛰ <?= difficulty_label($t['difficulty']) ?></span><span><?= $reviews ? stars($avg) . ' ' . round($avg, 1) . ' (' . count($reviews) . ')' : 'Ҳанӯз баҳо нест' ?></span></div>
<?php if ($photos): ?><div class="gallery"><?php foreach ($photos as $p): ?><img src="<?= e(photo_src($p)) ?>" alt=""><?php endforeach; ?></div>
<?php else: ?><div class="cover card" style="height:200px"><?= e($t['region']) ?></div><?php endif; ?>

<div class="two"><div>
  <div class="card"><h2 style="margin-top:0">Тавсиф</h2><p><?= nl2br(e($t['description'])) ?></p></div>
  <div class="card"><h2 style="margin-top:0">Барнома аз рӯи рӯзҳо</h2>
    <?php foreach (preg_split('/\R/', trim($t['itinerary'])) as $line) if (trim($line) !== '') echo '<p style="margin:.4em 0">• ' . e($line) . '</p>'; ?></div>

  <div class="card"><h2 style="margin-top:0">Фикру мулоҳизаҳо</h2>
    <?php if (!$reviews): ?><p class="meta">Ҳанӯз фикр нест.</p><?php endif; ?>
    <?php foreach ($reviews as $r): ?>
      <div style="border-bottom:1px solid var(--b);padding:10px 0"><strong><?= e($r['full_name']) ?></strong> <?= stars((float)$r['rating']) ?>
        <span class="meta"><?= e(substr($r['created_at'], 0, 10)) ?></span><p style="margin:.3em 0"><?= nl2br(e($r['comment'])) ?></p></div>
    <?php endforeach; ?>
    <?php if ($canReview): ?>
      <h3>Фикри худро нависед</h3>
      <form method="post" action="<?= url('review_add.php') ?>"><?= csrf_field() ?>
        <input type="hidden" name="tour_id" value="<?= $id ?>">
        <div class="row"><label>Баҳо</label><select name="rating"><?php for ($i = 5; $i >= 1; $i--) echo "<option value=$i>$i ★</option>"; ?></select></div>
        <div class="row"><label>Фикр</label><textarea name="comment" required></textarea></div>
        <button>Фиристодан</button> <span class="meta">Пас аз модератсия нашр мешавад.</span></form>
    <?php elseif ($myReview): ?><p class="meta">Фикри шумо: <span class="badge <?= e($myReview['status']) ?>"><?= status_label($myReview['status']) ?></span></p>
    <?php elseif ($u && $u['role'] === 'tourist'): ?><p class="meta">Фикр танҳо аз сайёҳоне қабул мешавад, ки дар ин тур буданд (бандаш тасдиқ шуда ва сафар оғоз ёфтааст).</p>
    <?php endif; ?>
  </div>
</div>

<div><div class="card" id="dates"><div class="price"><?= money($t['price']) ?></div><div class="meta">барои як нафар</div>
  <h3>Санаҳои сафар</h3>
  <?php if (!$dates): ?><p class="meta">Санаҳои наздик нестанд.</p><?php endif; ?>
  <?php foreach ($dates as $d): $free = max(0, (int)$d['free_seats']); $open = $d['status'] === 'open' && $free > 0; ?>
    <div style="border-top:1px solid var(--b);padding:10px 0">
      <strong><?= e(date('d.m.Y', strtotime($d['start_date']))) ?></strong>
      — <?= e(date('d.m.Y', strtotime($d['start_date'] . ' +' . ((int)$t['duration_days'] - 1) . ' days'))) ?><br>
      <?php if ($open): ?><span class="<?= $free <= 3 ? 'seats-low' : '' ?>">Ҷойҳои холӣ: <?= $free ?> / <?= (int)$d['total_seats'] ?></span>
      <div class="bar"><i data-w="<?= round(100 * (1 - $free / max(1, (int)$d['total_seats']))) ?>%"></i></div>
      <?php else: ?><span class="badge closed">Ҷой намонд</span><?php endif; ?>
      <?php if ($open): ?>
        <?php if (!$u): ?><div><a class="btn small" href="<?= url('login.php') ?>">Барои банд ворид шавед</a></div>
        <?php elseif ($u['role'] === 'tourist'): ?>
          <form method="post" action="<?= url('book.php') ?>" style="display:flex;gap:6px;margin-top:6px"><?= csrf_field() ?>
            <input type="hidden" name="date_id" value="<?= $d['id'] ?>">
            <input type="number" name="persons" min="1" max="<?= $free ?>" value="1" style="width:70px" title="Шумораи нафар">
            <button>Банд кардан</button></form>
        <?php endif; ?>
      <?php else: ?><div><span class="btn small dis">Банд кардан</span></div><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <p class="meta">Менеҷер: <?= e($t['manager']) ?></p>
</div></div></div>
<?php include __DIR__ . '/inc/footer.php';
