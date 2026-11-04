<?php
/**
 * EditRequestModel — public-portal change requests awaiting admin review.
 */
class EditRequestModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Insert a new pending request.
     *
     * @param array $data Keys: target_type, group_id, member_id(null),
     *                    original (array), payload (array), ip_address,
     *                    user_agent, accept_language, referer
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO edit_requests
                (target_type, group_id, member_id, original_json, payload_json,
                 status, ip_address, user_agent, accept_language, referer)
            VALUES
                (:target_type, :group_id, :member_id, :original_json, :payload_json,
                 'pending', :ip, :ua, :lang, :referer)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':target_type'  => $data['target_type'],
            ':group_id'     => $data['group_id'],
            ':member_id'    => $data['member_id'] ?? null,
            ':original_json'=> json_encode($data['original'] ?? [], JSON_UNESCAPED_UNICODE),
            ':payload_json' => json_encode($data['payload'] ?? [], JSON_UNESCAPED_UNICODE),
            ':ip'           => $data['ip_address']      ?? null,
            ':ua'           => mb_substr((string)($data['user_agent'] ?? ''), 0, 255),
            ':lang'         => mb_substr((string)($data['accept_language'] ?? ''), 0, 100),
            ':referer'      => mb_substr((string)($data['referer'] ?? ''), 0, 255),
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Count pending requests (for the sidebar badge). */
    public function countPending(): int
    {
        return (int) $this->db
            ->query("SELECT COUNT(*) FROM edit_requests WHERE status = 'pending'")
            ->fetchColumn();
    }

    /**
     * List requests, newest first, optionally filtered by status.
     * Joins the head member's surname for a readable family label.
     */
    public function findAll(string $status = 'pending'): array
    {
        $sql = "
            SELECT
                er.*,
                pm.last_name  AS head_last_name,
                pm.first_name AS head_first_name,
                tm.first_name AS target_first_name,
                tm.last_name  AS target_last_name
            FROM edit_requests er
            LEFT JOIN members pm ON pm.group_id = er.group_id AND pm.type = 'P'
            LEFT JOIN members tm ON tm.member_id = er.member_id
            " . ($status !== 'all' ? "WHERE er.status = :status" : "") . "
            ORDER BY er.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        if ($status !== 'all') {
            $stmt->bindValue(':status', $status);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Single request with decoded JSON payload/original. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM edit_requests WHERE request_id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['original'] = json_decode($row['original_json'] ?? '[]', true) ?: [];
        $row['payload']  = json_decode($row['payload_json']  ?? '[]', true) ?: [];
        return $row;
    }

    /** Mark a request reviewed with a final status and optional note. */
    public function setStatus(int $id, string $status, string $note = ''): void
    {
        $stmt = $this->db->prepare(
            "UPDATE edit_requests
             SET status = :status, review_note = :note, reviewed_at = NOW()
             WHERE request_id = :id"
        );
        $stmt->execute([':status' => $status, ':note' => $note, ':id' => $id]);
    }
}
