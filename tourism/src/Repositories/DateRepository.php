<?php
declare(strict_types=1);

namespace Tourism\Repositories;

use PDO;

final class DateRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? \db(); }

    public function forTour(int $tourId, bool $upcomingOnly = false): array
    {
        $sql = 'SELECT * FROM v_date_seats WHERE tour_id=:tour_id' . ($upcomingOnly ? ' AND start_date>=CURDATE()' : '') . ' ORDER BY start_date' . ($upcomingOnly ? '' : ' DESC');
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tour_id' => $tourId]);
        return $stmt->fetchAll();
    }

    public function findForTour(int $id, int $tourId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM v_date_seats WHERE id=:id AND tour_id=:tour_id');
        $stmt->execute(['id' => $id, 'tour_id' => $tourId]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $tourId, string $startDate, int $seats): void
    {
        $stmt = $this->db->prepare('INSERT INTO tour_dates (tour_id,start_date,total_seats) VALUES (:tour_id,:start_date,:seats)');
        $stmt->execute(['tour_id' => $tourId, 'start_date' => $startDate, 'seats' => $seats]);
    }

    public function updateSeats(int $id, int $tourId, int $seats): bool
    {
        $stmt = $this->db->prepare('UPDATE tour_dates SET total_seats=:seats WHERE id=:id AND tour_id=:tour_id');
        $stmt->execute(['seats' => $seats, 'id' => $id, 'tour_id' => $tourId]);
        return $stmt->rowCount() > 0;
    }

    public function toggle(int $id, int $tourId): bool
    {
        $stmt = $this->db->prepare("UPDATE tour_dates SET status=IF(status='open','closed','open') WHERE id=:id AND tour_id=:tour_id");
        $stmt->execute(['id' => $id, 'tour_id' => $tourId]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $tourId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM tour_dates WHERE id=:id AND tour_id=:tour_id');
        $stmt->execute(['id' => $id, 'tour_id' => $tourId]);
        return $stmt->rowCount() > 0;
    }
}
