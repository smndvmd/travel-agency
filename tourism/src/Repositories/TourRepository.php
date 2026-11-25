<?php
declare(strict_types=1);

namespace Tourism\Repositories;

use PDO;

/** Database access for tour catalogue and manager CRUD. */
final class TourRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? \db();
    }

    /** @return array{items:list<array<string,mixed>>,total:int,page:int,pages:int} */
    public function search(array $filters = [], int $page = 1, int $perPage = 9): array
    {
        $perPage = min(100, max(1, $perPage));
        $where = ['t.is_active = 1'];
        $params = [];
        $rawQuery = $filters['q'] ?? '';
        $q = is_scalar($rawQuery) ? trim((string)$rawQuery) : '';
        if ($q !== '') {
            $where[] = '(t.title LIKE :q_title OR t.short_desc LIKE :q_desc)';
            $escaped = '%' . addcslashes($q, '%_\\') . '%';
            $params['q_title'] = $escaped;
            $params['q_desc'] = $escaped;
        }
        $rawRegion = $filters['region'] ?? '';
        $region = is_scalar($rawRegion) ? trim((string)$rawRegion) : '';
        if ($region !== '') {
            $where[] = 't.region = :region';
            $params['region'] = $region;
        }
        $rawDuration = $filters['duration'] ?? '';
        $duration = is_scalar($rawDuration) ? (string)$rawDuration : '';
        if ($duration === '1-3') $where[] = 't.duration_days BETWEEN 1 AND 3';
        elseif ($duration === '4-7') $where[] = 't.duration_days BETWEEN 4 AND 7';
        elseif ($duration === '8+') $where[] = 't.duration_days >= 8';
        foreach (['min' => '>=', 'max' => '<='] as $key => $operator) {
            if (isset($filters[$key]) && $filters[$key] !== '' && is_numeric($filters[$key])) {
                $where[] = 't.price ' . $operator . ' :' . $key;
                $params[$key] = max(0, (float)$filters[$key]);
            }
        }
        $whereSql = implode(' AND ', $where);
        $count = $this->db->prepare('SELECT COUNT(*) FROM tours t WHERE ' . $whereSql);
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $sql = "SELECT t.*,
            (SELECT file_path FROM photos WHERE tour_id=t.id ORDER BY is_main DESC, id LIMIT 1) AS cover,
            (SELECT ROUND(AVG(rating),1) FROM reviews WHERE tour_id=t.id AND status='approved') AS rating,
            (SELECT COUNT(*) FROM reviews WHERE tour_id=t.id AND status='approved') AS rcount,
            (SELECT COALESCE(SUM(free_seats),0) FROM v_date_seats WHERE tour_id=t.id AND status='open' AND start_date>=CURDATE()) AS free_total
            FROM tours t WHERE " . $whereSql . ' ORDER BY t.created_at DESC, t.id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $name => $value) $stmt->bindValue(':' . $name, $value);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();
        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public function findActive(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT t.*, u.full_name AS manager FROM tours t JOIN users u ON u.id=t.manager_id WHERE t.id=:id AND t.is_active=1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findForManager(int $id, int $managerId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tours WHERE id=:id AND manager_id=:manager_id');
        $stmt->execute(['id' => $id, 'manager_id' => $managerId]);
        return $stmt->fetch() ?: null;
    }

    /** @return list<string> */
    public function activeRegions(): array
    {
        return $this->db->query('SELECT DISTINCT region FROM tours WHERE is_active=1 ORDER BY region')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function managerListing(int $managerId): array
    {
        $stmt = $this->db->prepare('SELECT t.*, (SELECT COUNT(*) FROM tour_dates WHERE tour_id=t.id AND start_date>=CURDATE()) AS dcount FROM tours t WHERE manager_id=:manager_id ORDER BY id DESC');
        $stmt->execute(['manager_id' => $managerId]);
        return $stmt->fetchAll();
    }

    public function create(int $managerId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO tours (title,region,short_desc,description,itinerary,duration_days,price,difficulty,manager_id) VALUES (:title,:region,:short_desc,:description,:itinerary,:duration_days,:price,:difficulty,:manager_id)');
        $stmt->execute($this->tourFields($data) + ['manager_id' => $managerId]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $managerId, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE tours SET title=:title,region=:region,short_desc=:short_desc,description=:description,itinerary=:itinerary,duration_days=:duration_days,price=:price,difficulty=:difficulty WHERE id=:id AND manager_id=:manager_id');
        $stmt->execute($this->tourFields($data) + ['id' => $id, 'manager_id' => $managerId]);
        return $stmt->rowCount() > 0;
    }

    public function setActive(int $id, int $managerId, bool $active): bool
    {
        $stmt = $this->db->prepare('UPDATE tours SET is_active=:active WHERE id=:id AND manager_id=:manager_id');
        $stmt->execute(['active' => (int)$active, 'id' => $id, 'manager_id' => $managerId]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $managerId): bool
    {
        $this->db->beginTransaction();
        try {
            $owner = $this->db->prepare('SELECT id FROM tours WHERE id=:id AND manager_id=:manager_id FOR UPDATE');
            $owner->execute(['id' => $id, 'manager_id' => $managerId]);
            if (!$owner->fetch()) throw new \PDOException('Tour not found or not owned by this manager.');
            $bookings = $this->db->prepare('SELECT COUNT(*) FROM bookings b JOIN tour_dates d ON d.id=b.tour_date_id WHERE d.tour_id=:id');
            $bookings->execute(['id' => $id]);
            if ((int)$bookings->fetchColumn() > 0) throw new \PDOException('Tour has bookings and cannot be deleted.');
            $stmt = $this->db->prepare('DELETE FROM tours WHERE id=:id AND manager_id=:manager_id');
            $stmt->execute(['id' => $id, 'manager_id' => $managerId]);
            $deleted = $stmt->rowCount() > 0;
            $this->db->commit();
            return $deleted;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    private function tourFields(array $data): array
    {
        $fields = ['title','region','short_desc','description','itinerary','duration_days','price','difficulty'];
        $safe = [];
        foreach ($fields as $field) $safe[$field] = $data[$field] ?? null;
        return $safe;
    }
}
