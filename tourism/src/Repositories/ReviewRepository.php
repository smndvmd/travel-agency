<?php
declare(strict_types=1);

namespace Tourism\Repositories;

use PDO;

final class ReviewRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? \db(); }
    public function forTour(int $tourId, bool $approvedOnly = true): array
    {
        $stmt = $this->db->prepare('SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.tour_id=:tour_id' . ($approvedOnly ? " AND r.status='approved'" : '') . ' ORDER BY r.created_at DESC');
        $stmt->execute(['tour_id' => $tourId]);
        return $stmt->fetchAll();
    }
    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT r.*, t.title FROM reviews r JOIN tours t ON t.id=r.tour_id WHERE r.user_id=:user_id ORDER BY r.created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
    public function forModeration(?string $status = null): array
    {
        $sql = 'SELECT r.*, u.full_name, t.title FROM reviews r JOIN users u ON u.id=r.user_id JOIN tours t ON t.id=r.tour_id';
        $params = [];
        if (in_array($status, ['pending','approved','rejected'], true)) { $sql .= ' WHERE r.status=:status'; $params['status'] = $status; }
        $stmt = $this->db->prepare($sql . ' ORDER BY r.created_at DESC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function findByUserAndTour(int $userId, int $tourId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM reviews WHERE user_id=:user_id AND tour_id=:tour_id');
        $stmt->execute(['user_id' => $userId, 'tour_id' => $tourId]);
        return $stmt->fetch() ?: null;
    }
    public function create(int $userId, int $tourId, int $bookingId, int $rating, string $comment): void
    {
        $stmt = $this->db->prepare('INSERT INTO reviews (user_id,tour_id,booking_id,rating,comment) VALUES (:user_id,:tour_id,:booking_id,:rating,:comment)');
        $stmt->execute(['user_id' => $userId, 'tour_id' => $tourId, 'booking_id' => $bookingId, 'rating' => $rating, 'comment' => $comment]);
    }
    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) throw new \InvalidArgumentException('Unsupported review status.');
        $stmt = $this->db->prepare('UPDATE reviews SET status=:status WHERE id=:id');
        $stmt->execute(['status' => $status, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM reviews WHERE id=:id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
