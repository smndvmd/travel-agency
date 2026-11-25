<?php
declare(strict_types=1);

namespace Tourism\Repositories;

use PDO;
use RuntimeException;

final class BookingRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? \db(); }

    /** Atomically verifies capacity and creates a booking. */
    public function create(int $userId, int $dateId, int $persons): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT d.total_seats,d.status,d.start_date,t.price,t.is_active FROM tour_dates d JOIN tours t ON t.id=d.tour_id WHERE d.id=:id FOR UPDATE');
            $stmt->execute(['id' => $dateId]);
            $date = $stmt->fetch();
            if (!$date || !$date['is_active'] || $date['status'] !== 'open' || $date['start_date'] < date('Y-m-d')) throw new RuntimeException('date_unavailable');
            $used = $this->db->prepare("SELECT COALESCE(SUM(persons),0) FROM bookings WHERE tour_date_id=:id AND status IN ('pending','confirmed')");
            $used->execute(['id' => $dateId]);
            if ($persons < 1 || $persons > (int)$date['total_seats'] - (int)$used->fetchColumn()) throw new RuntimeException('not_enough_seats');
            $insert = $this->db->prepare('INSERT INTO bookings (user_id,tour_date_id,persons,total_price) VALUES (:user_id,:date_id,:persons,:total)');
            $insert->execute(['user_id' => $userId, 'date_id' => $dateId, 'persons' => $persons, 'total' => $persons * (float)$date['price']]);
            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function tourIdForDate(int $dateId): ?int
    {
        $stmt = $this->db->prepare('SELECT tour_id FROM tour_dates WHERE id=:id');
        $stmt->execute(['id' => $dateId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int)$id;
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT b.*, d.start_date, t.id AS tid, t.title, t.duration_days FROM bookings b JOIN tour_dates d ON d.id=b.tour_date_id JOIN tours t ON t.id=d.tour_id WHERE b.user_id=:user_id ORDER BY b.created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function cancelForUser(int $bookingId, int $userId): \PDOStatement
    {
        $stmt = $this->db->prepare("UPDATE bookings b JOIN tour_dates d ON d.id=b.tour_date_id SET b.status='cancelled' WHERE b.id=:id AND b.user_id=:user_id AND b.status IN ('pending','confirmed') AND d.start_date>CURDATE()");
        $stmt->execute(['id' => $bookingId, 'user_id' => $userId]);
        return $stmt;
    }

    public function findReviewable(int $userId, int $tourId): ?int
    {
        $stmt = $this->db->prepare("SELECT b.id FROM bookings b JOIN tour_dates d ON d.id=b.tour_date_id WHERE b.user_id=:user_id AND d.tour_id=:tour_id AND b.status='confirmed' AND d.start_date<=CURDATE() ORDER BY d.start_date DESC LIMIT 1");
        $stmt->execute(['user_id' => $userId, 'tour_id' => $tourId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int)$id;
    }

    public function setStatusForManager(int $bookingId, int $managerId, string $status): \PDOStatement
    {
        if (!in_array($status, ['confirmed', 'rejected'], true)) throw new \InvalidArgumentException('Unsupported booking status.');
        $stmt = $this->db->prepare("UPDATE bookings b JOIN tour_dates d ON d.id=b.tour_date_id JOIN tours t ON t.id=d.tour_id SET b.status=:status WHERE b.id=:id AND t.manager_id=:manager_id AND b.status='pending'");
        $stmt->execute(['status' => $status, 'id' => $bookingId, 'manager_id' => $managerId]);
        return $stmt;
    }

    public function forManager(int $managerId, ?string $status = null): array
    {
        $sql = 'SELECT b.*, d.start_date, t.title, us.full_name, us.phone, us.email FROM bookings b JOIN tour_dates d ON d.id=b.tour_date_id JOIN tours t ON t.id=d.tour_id JOIN users us ON us.id=b.user_id WHERE t.manager_id=:manager_id';
        $params = ['manager_id' => $managerId];
        if (in_array($status, ['pending','confirmed','rejected','cancelled'], true)) { $sql .= ' AND b.status=:status'; $params['status'] = $status; }
        $stmt = $this->db->prepare($sql . ' ORDER BY b.created_at DESC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
