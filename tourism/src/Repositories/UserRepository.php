<?php
declare(strict_types=1);

namespace Tourism\Repositories;

use PDO;

final class UserRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? \db(); }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id,full_name,email,phone,role,is_active FROM users WHERE id=:id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function emailLookup(string $email): \PDOStatement
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email=:email');
        $stmt->execute(['email' => $email]);
        return $stmt;
    }

    public function create(string $name, string $email, string $phone, string $passwordHash): int
    {
        $stmt = $this->db->prepare("INSERT INTO users (full_name,email,phone,password_hash,role) VALUES (:name,:email,:phone,:password_hash,'tourist')");
        $stmt->execute(['name' => $name, 'email' => $email, 'phone' => $phone, 'password_hash' => $passwordHash]);
        return (int)$this->db->lastInsertId();
    }

    public function updateProfile(int $id, string $name, string $phone): \PDOStatement
    {
        $stmt = $this->db->prepare('UPDATE users SET full_name=:name, phone=:phone WHERE id=:id');
        $stmt->execute(['name' => $name, 'phone' => $phone, 'id' => $id]);
        return $stmt;
    }

    public function managers(): array
    {
        return $this->db->query("SELECT u.*, (SELECT COUNT(*) FROM tours WHERE manager_id=u.id) AS tc FROM users u WHERE role='manager' ORDER BY id")->fetchAll();
    }

    public function setManagerActive(int $id, bool $active): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET is_active=:active WHERE id=:id AND role='manager'");
        $stmt->execute(['active' => (int)$active, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function changeManagerPassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password_hash=:hash WHERE id=:id AND role='manager'");
        $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function managerPasswordUpdateStatement(): \PDOStatement
    {
        return $this->db->prepare("UPDATE users SET password_hash=? WHERE id=? AND role='manager'");
    }

    public function createManager(string $name, string $email, string $phone, string $passwordHash): int
    {
        $stmt = $this->db->prepare("INSERT INTO users (full_name,email,phone,password_hash,role) VALUES (:name,:email,:phone,:hash,'manager')");
        $stmt->execute(['name' => $name, 'email' => $email, 'phone' => $phone, 'hash' => $passwordHash]);
        return (int)$this->db->lastInsertId();
    }
}
