<?php
declare(strict_types=1);

namespace Tourism\Repositories;

use PDO;

final class PhotoRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? \db(); }
    public function forTour(int $tourId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM photos WHERE tour_id=:tour_id ORDER BY is_main DESC,id');
        $stmt->execute(['tour_id' => $tourId]);
        return $stmt->fetchAll();
    }
    public function pathsForTour(int $tourId): array
    {
        $stmt = $this->db->prepare('SELECT file_path FROM photos WHERE tour_id=:tour_id ORDER BY is_main DESC,id');
        $stmt->execute(['tour_id' => $tourId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    public function countForTour(int $tourId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM photos WHERE tour_id=:tour_id');
        $stmt->execute(['tour_id' => $tourId]);
        return (int)$stmt->fetchColumn();
    }
    public function add(int $tourId, string $path, bool $isMain = false): void
    {
        $stmt = $this->db->prepare('INSERT INTO photos (tour_id,file_path,is_main) VALUES (:tour_id,:path,:is_main)');
        $stmt->execute(['tour_id' => $tourId, 'path' => $path, 'is_main' => (int)$isMain]);
    }
    public function findPath(int $id, int $tourId): ?string
    {
        $stmt = $this->db->prepare('SELECT file_path FROM photos WHERE id=:id AND tour_id=:tour_id');
        $stmt->execute(['id' => $id, 'tour_id' => $tourId]);
        $path = $stmt->fetchColumn();
        return $path === false ? null : (string)$path;
    }
    public function delete(int $id, int $tourId): bool
    {
        $this->db->beginTransaction();
        try {
            $find = $this->db->prepare('SELECT is_main FROM photos WHERE id=:id AND tour_id=:tour_id FOR UPDATE');
            $find->execute(['id' => $id, 'tour_id' => $tourId]);
            $photo = $find->fetch();
            if (!$photo) { $this->db->rollBack(); return false; }
            $stmt = $this->db->prepare('DELETE FROM photos WHERE id=:id AND tour_id=:tour_id');
            $stmt->execute(['id' => $id, 'tour_id' => $tourId]);
            if ($photo['is_main']) {
                $next = $this->db->prepare('SELECT id FROM photos WHERE tour_id=:tour_id ORDER BY id LIMIT 1');
                $next->execute(['tour_id' => $tourId]);
                $nextId = $next->fetchColumn();
                if ($nextId !== false) {
                    $promote = $this->db->prepare('UPDATE photos SET is_main=1 WHERE id=:id AND tour_id=:tour_id');
                    $promote->execute(['id' => $nextId, 'tour_id' => $tourId]);
                }
            }
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
    public function setMain(int $id, int $tourId): bool
    {
        $this->db->beginTransaction();
        try {
            $clear = $this->db->prepare('UPDATE photos SET is_main=0 WHERE tour_id=:tour_id');
            $clear->execute(['tour_id' => $tourId]);
            $set = $this->db->prepare('UPDATE photos SET is_main=1 WHERE id=:id AND tour_id=:tour_id');
            $set->execute(['id' => $id, 'tour_id' => $tourId]);
            $changed = $set->rowCount() > 0;
            if ($changed) $this->db->commit(); else $this->db->rollBack();
            return $changed;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
