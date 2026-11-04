<?php
/**
 * LoginAttemptModel — brute-force tracking
 */
class LoginAttemptModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Record a failed login attempt for the given IP.
     */
    public function record(string $ip): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO login_attempts (ip_address) VALUES (:ip)"
        );
        $stmt->execute([':ip' => $ip]);
    }

    /**
     * Count failed attempts from an IP within the last $windowMins minutes.
     */
    public function countRecent(string $ip, int $windowMins): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM login_attempts
             WHERE ip_address = :ip
               AND attempted_at > DATE_SUB(NOW(), INTERVAL :mins MINUTE)"
        );
        $stmt->execute([':ip' => $ip, ':mins' => $windowMins]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Check whether the IP is currently blocked.
     */
    public function isBlocked(string $ip): bool
    {
        return $this->countRecent($ip, LOGIN_BLOCK_MINUTES) >= MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Return the datetime when the block will expire.
     */
    public function blockExpiresAt(string $ip): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT MAX(attempted_at)
             FROM login_attempts
             WHERE ip_address = :ip"
        );
        $stmt->execute([':ip' => $ip]);
        $last = $stmt->fetchColumn();
        if (!$last) return null;
        return date('H:i', strtotime($last) + (LOGIN_BLOCK_MINUTES * 60));
    }

    /**
     * Remove all attempt records for an IP (on successful login).
     */
    public function clearForIp(string $ip): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM login_attempts WHERE ip_address = :ip"
        );
        $stmt->execute([':ip' => $ip]);
    }
}
