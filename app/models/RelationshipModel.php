<?php
/**
 * RelationshipModel — relationship lookup table
 */
class RelationshipModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getActive(): array
    {
        return $this->db
            ->query("SELECT * FROM relationships WHERE is_active = 1 ORDER BY display_order ASC")
            ->fetchAll();
    }

    public function getActiveExcludingSelf(): array
    {
        return $this->db
            ->query("SELECT * FROM relationships WHERE is_active = 1 AND name != 'Self' ORDER BY display_order ASC")
            ->fetchAll();
    }

    public function getAll(): array
    {
        return $this->db
            ->query("SELECT * FROM relationships ORDER BY display_order ASC")
            ->fetchAll();
    }

    public function create(string $name): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO relationships (name, display_order, is_active)
             VALUES (:name, (SELECT COALESCE(MAX(display_order),0)+1 FROM relationships r2), 1)"
        );
        $stmt->execute([':name' => $name]);
        return (int) $this->db->lastInsertId();
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE relationships SET is_active = 0 WHERE relationship_id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    public function isValidId(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM relationships WHERE relationship_id = :id AND is_active = 1"
        );
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function getSelfId(): ?int
    {
        $stmt = $this->db->prepare(
            "SELECT relationship_id FROM relationships WHERE name = 'Self' LIMIT 1"
        );
        $stmt->execute();
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }
}
