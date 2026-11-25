<?php
require __DIR__ . '/config.php';
$u = require_role('tourist');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('');
csrf_check();

$dateId = (int)($_POST['date_id'] ?? 0);
$persons = (int)($_POST['persons'] ?? 0);
$bookings = new Tourism\Repositories\BookingRepository();
$tourId = $bookings->tourIdForDate($dateId);

try {
    if ($persons < 1 || $persons > 50) throw new RuntimeException('invalid_person_count');
    $bookings->create((int)$u['id'], $dateId, $persons);
    flash('Бронь принята. Менеджер подтвердит её.');
    redirect('profile.php');
} catch (RuntimeException $e) {
    $messages = [
        'invalid_person_count' => 'Укажите число путешественников от 1 до 50.',
        'date_unavailable' => 'Эта дата недоступна для бронирования.',
        'not_enough_seats' => 'На эту дату осталось недостаточно мест.',
    ];
    flash($messages[$e->getMessage()] ?? 'Не удалось создать бронирование.', 'err');
    redirect($tourId ? 'tour.php?id=' . $tourId : '');
} catch (Throwable $e) {
    error_log((string)$e);
    flash('Не удалось создать бронирование. Попробуйте ещё раз.', 'err');
    redirect($tourId ? 'tour.php?id=' . $tourId : '');
}
