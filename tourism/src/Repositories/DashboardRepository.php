<?php
declare(strict_types=1);
namespace Tourism\Repositories;
use PDO;
final class DashboardRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? \db(); }
    public function adminStats(): array
    {
        return $this->db->query("SELECT (SELECT COUNT(*) FROM users WHERE role='tourist') tourists, (SELECT COUNT(*) FROM tours) tours, (SELECT COUNT(*) FROM bookings WHERE status IN ('pending','confirmed')) bookings, (SELECT COUNT(*) FROM reviews WHERE status='pending') pending")->fetch();
    }
    public function homeStats(): array
    {
        return $this->db->query("SELECT (SELECT COUNT(*) FROM tours WHERE is_active=1) t, (SELECT COUNT(DISTINCT region) FROM tours WHERE is_active=1) r, (SELECT COALESCE(SUM(persons),0) FROM bookings WHERE status='confirmed') p")->fetch();
    }
}
