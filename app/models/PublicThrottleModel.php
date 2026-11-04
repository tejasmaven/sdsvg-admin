<?php
/**
 * PublicThrottleModel — simple per-IP rate limiting for the public portal.
 *
 * Backs the family-verification gate (brute-force protection on the knowledge
 * challenge) and caps the number of edit submissions from a single IP.
 * One row per attempt; callers count recent rows within a time window.
 */
class PublicThrottleModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Count attempts for an IP + action within the last $minutes. */
    public function countRecent(string $ip, string $action, int $minutes): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM public_throttle
             WHERE ip_address = :ip AND action = :action
               AND attempted_at >= (NOW() - INTERVAL :mins MINUTE)"
        );
        $stmt->bindValue(':ip', $ip);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':mins', $minutes, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** Record one attempt. */
    public function record(string $ip, string $action): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO public_throttle (ip_address, action) VALUES (:ip, :action)"
        );
        $stmt->execute([':ip' => $ip, ':action' => $action]);
    }

    /** Clear all attempts for an IP + action (e.g. after a successful verify). */
    public function clear(string $ip, string $action): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM public_throttle WHERE ip_address = :ip AND action = :action"
        );
        $stmt->execute([':ip' => $ip, ':action' => $action]);
    }
}
