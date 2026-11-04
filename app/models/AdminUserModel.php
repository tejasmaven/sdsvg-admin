<?php
/**
 * AdminUserModel — admin_users table
 */
class AdminUserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find an admin by username (case-insensitive).
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM admin_users WHERE username = :username LIMIT 1"
        );
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Update the last_login timestamp to now.
     */
    public function updateLastLogin(int $adminId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE admin_users SET last_login = NOW() WHERE admin_id = :id"
        );
        $stmt->execute([':id' => $adminId]);
    }
}
