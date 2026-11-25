<?php
require __DIR__ . '/config.php';
$u = require_role('tourist');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('');
csrf_check();
$tourId = (int)($_POST['tour_id'] ?? 0); $rating = (int)($_POST['rating'] ?? 0); $comment = trim($_POST['comment'] ?? '');
// фикр танҳо аз касе, ки дар ҳамин тур буд (банди тасдиқшуда ва санаи сафар оғоз ёфтааст)
$bid = (new Tourism\Repositories\BookingRepository())->findReviewable((int)$u['id'], $tourId) ?? 0;
if (!$bid) flash('Шумо дар ин тур набудаед, фикр гузошта наметавонед.', 'err');
elseif ($rating < 1 || $rating > 5 || $comment === '') flash('Баҳо ва матни фикрро пур кунед.', 'err');
else {
    try {
        (new Tourism\Repositories\ReviewRepository())->create((int)$u['id'], $tourId, $bid, $rating, $comment);
        flash('Раҳмат! Фикр пас аз модератсия нашр мешавад.');
    } catch (PDOException $e) { flash('Шумо аллакай фикр гузоштаед.', 'err'); }
}
redirect('tour.php?id=' . $tourId);
