<?php
/**
 * GenderModel — gender lookup table
 */
class GenderModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getActive(): array
    {
        return $this->db
            ->query("SELECT * FROM genders WHERE is_active = 1 ORDER BY display_order ASC")
            ->fetchAll();
    }

    public function getAll(): array
    {
        return $this->db
            ->query("SELECT * FROM genders ORDER BY display_order ASC")
            ->fetchAll();
    }

    public function create(string $name): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO genders (name, display_order, is_active)
             VALUES (:name, (SELECT COALESCE(MAX(display_order),0)+1 FROM genders g2), 1)"
        );
        $stmt->execute([':name' => $name]);
        return (int) $this->db->lastInsertId();
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE genders SET is_active = 0 WHERE gender_id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    public function isValidId(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM genders WHERE gender_id = :id AND is_active = 1"
        );
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }
}
