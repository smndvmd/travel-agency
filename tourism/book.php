<?php
require __DIR__ . '/config.php';
$u = require_role('tourist');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('');
csrf_check();
$dateId = (int)($_POST['date_id'] ?? 0); $persons = (int)($_POST['persons'] ?? 0);
$pdo = db();
try {
    if ($persons < 1 || $persons > 50) throw new Exception('Шумораи нафарро дуруст ворид кунед.');
    $pdo->beginTransaction();
    // қулф кардани сатри сана, то ду нафар якбора ҷойи охирро банд накунанд
    $st = $pdo->prepare('SELECT d.*, t.price, t.id AS tid, t.is_active FROM tour_dates d JOIN tours t ON t.id=d.tour_id WHERE d.id=? FOR UPDATE');
    $st->execute([$dateId]); $d = $st->fetch();
    if (!$d || !$d['is_active']) throw new Exception('Сана ёфт нашуд.');
    if ($d['status'] !== 'open' || $d['start_date'] < date('Y-m-d')) throw new Exception('Банд кардан барои ин сана баста аст.');
    $b = $pdo->prepare("SELECT COALESCE(SUM(persons),0) FROM bookings WHERE tour_date_id=? AND status IN ('pending','confirmed')");
    $b->execute([$dateId]); $free = (int)$d['total_seats'] - (int)$b->fetchColumn();
    if ($free <= 0) throw new Exception('Афсӯс, ҷойи холӣ намондааст.');
    if ($persons > $free) throw new Exception("Танҳо $free ҷойи холӣ мондааст.");
    $pdo->prepare('INSERT INTO bookings (user_id,tour_date_id,persons,total_price) VALUES (?,?,?,?)')
        ->execute([$u['id'], $dateId, $persons, $persons * $d['price']]);
    $pdo->commit();
    flash('Бандшавӣ қабул шуд. Менеҷер онро тасдиқ мекунад.'); redirect('profile.php');
} catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash($ex->getMessage(), 'err');
    redirect(!empty($d['tid']) ? 'tour.php?id=' . $d['tid'] : '');
}
