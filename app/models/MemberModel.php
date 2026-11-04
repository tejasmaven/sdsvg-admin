<?php
/**
 * MemberModel — individual persons
 */
class MemberModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * All members belonging to a group, ordered: Primary first, then by DOB.
     */
    public function findByGroup(int $groupId): array
    {
        $sql = "
            SELECT
                m.*,
                g.name   AS gender_name,
                r.name   AS relationship_name
            FROM members m
            JOIN genders       g ON g.gender_id       = m.gender_id
            JOIN relationships r ON r.relationship_id = m.relationship_id
            WHERE m.group_id = :group_id
            ORDER BY m.display_order ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':group_id' => $groupId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a single member with joined names.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                m.*,
                g.name   AS gender_name,
                r.name   AS relationship_name,
                gr.address
            FROM members m
            JOIN genders       g  ON g.gender_id       = m.gender_id
            JOIN relationships r  ON r.relationship_id = m.relationship_id
            JOIN `groups`      gr ON gr.group_id        = m.group_id
            WHERE m.member_id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Insert a new member, return its ID.
     *
     * @param array $data Keys: title, first_name, middle_name, last_name, dob,
     *                          gender_id, relationship_id, mobile, email, education
     */
    public function create(int $groupId, string $type, array $data): int
    {
        // Auto-assign the next display_order for this group
        $ord = $this->db->prepare(
            "SELECT COALESCE(MAX(display_order), 0) + 1 FROM members WHERE group_id = :gid"
        );
        $ord->execute([':gid' => $groupId]);
        $nextOrder = (int) $ord->fetchColumn();

        $sql = "
            INSERT INTO members
                (group_id, type, title, first_name, middle_name, last_name,
                 dob, gender_id, relationship_id, mobile, email, education, display_order)
            VALUES
                (:group_id, :type, :title, :first_name, :middle_name, :last_name,
                 :dob, :gender_id, :relationship_id, :mobile, :email, :education, :display_order)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':group_id'        => $groupId,
            ':type'            => $type,
            ':title'           => $data['title']           ?? null,
            ':first_name'      => $data['first_name'],
            ':middle_name'     => $data['middle_name']     ?? null,
            ':last_name'       => $data['last_name'],
            ':dob'             => $data['dob'],
            ':gender_id'       => $data['gender_id'],
            ':relationship_id' => $data['relationship_id'],
            ':mobile'          => $data['mobile']          ?? null,
            ':email'           => $data['email']           ?? null,
            ':education'       => $data['education']       ?? null,
            ':display_order'   => $nextOrder,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing member's fields.
     */
    public function update(int $id, array $data): void
    {
        $sql = "
            UPDATE members SET
                title           = :title,
                first_name      = :first_name,
                middle_name     = :middle_name,
                last_name       = :last_name,
                dob             = :dob,
                gender_id       = :gender_id,
                relationship_id = :relationship_id,
                mobile          = :mobile,
                email           = :email,
                education       = :education
            WHERE member_id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':title'           => $data['title']           ?? null,
            ':first_name'      => $data['first_name'],
            ':middle_name'     => $data['middle_name']     ?? null,
            ':last_name'       => $data['last_name'],
            ':dob'             => $data['dob'],
            ':gender_id'       => $data['gender_id'],
            ':relationship_id' => $data['relationship_id'],
            ':mobile'          => $data['mobile']          ?? null,
            ':email'           => $data['email']           ?? null,
            ':education'       => $data['education']       ?? null,
            ':id'              => $id,
        ]);
    }

    /**
     * Delete a member by ID.
     */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM members WHERE member_id = :id");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Promote a member to Head (type='P'):
     * — Demote existing P to S
     * — Set this member to P
     * Runs inside a transaction.
     */
    public function promoteToHead(int $memberId, int $groupId): void
    {
        $this->db->beginTransaction();
        try {
            // Demote current head
            $stmt = $this->db->prepare(
                "UPDATE members SET type='S' WHERE group_id = :gid AND type = 'P'"
            );
            $stmt->execute([':gid' => $groupId]);

            // Promote new head
            $stmt = $this->db->prepare(
                "UPDATE members SET type='P' WHERE member_id = :mid"
            );
            $stmt->execute([':mid' => $memberId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Swap this member's display_order with the one immediately above it in the same group.
     */
    public function moveUp(int $memberId, int $groupId): void
    {
        $curr = $this->db->prepare(
            "SELECT display_order FROM members WHERE member_id = :id AND group_id = :gid"
        );
        $curr->execute([':id' => $memberId, ':gid' => $groupId]);
        $currOrder = (int) $curr->fetchColumn();

        $prev = $this->db->prepare(
            "SELECT member_id, display_order FROM members
             WHERE group_id = :gid AND display_order < :ord
             ORDER BY display_order DESC LIMIT 1"
        );
        $prev->execute([':gid' => $groupId, ':ord' => $currOrder]);
        $prevRow = $prev->fetch();
        if (!$prevRow) return;

        $swap = $this->db->prepare("UPDATE members SET display_order = :ord WHERE member_id = :id");
        $swap->execute([':ord' => $prevRow['display_order'], ':id' => $memberId]);
        $swap->execute([':ord' => $currOrder,               ':id' => $prevRow['member_id']]);
    }

    /**
     * Swap this member's display_order with the one immediately below it in the same group.
     */
    public function moveDown(int $memberId, int $groupId): void
    {
        $curr = $this->db->prepare(
            "SELECT display_order FROM members WHERE member_id = :id AND group_id = :gid"
        );
        $curr->execute([':id' => $memberId, ':gid' => $groupId]);
        $currOrder = (int) $curr->fetchColumn();

        $next = $this->db->prepare(
            "SELECT member_id, display_order FROM members
             WHERE group_id = :gid AND display_order > :ord
             ORDER BY display_order ASC LIMIT 1"
        );
        $next->execute([':gid' => $groupId, ':ord' => $currOrder]);
        $nextRow = $next->fetch();
        if (!$nextRow) return;

        $swap = $this->db->prepare("UPDATE members SET display_order = :ord WHERE member_id = :id");
        $swap->execute([':ord' => $nextRow['display_order'], ':id' => $memberId]);
        $swap->execute([':ord' => $currOrder,               ':id' => $nextRow['member_id']]);
    }

    /**
     * Public portal: names + relationship only for a family (no contact PII),
     * ordered Primary first then by display_order.
     */
    public function namesByGroup(int $groupId): array
    {
        $sql = "
            SELECT
                m.member_id, m.type, m.title, m.first_name, m.middle_name, m.last_name,
                r.name AS relationship_name
            FROM members m
            JOIN relationships r ON r.relationship_id = m.relationship_id
            WHERE m.group_id = :gid
            ORDER BY (m.type = 'P') DESC, m.display_order ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':gid' => $groupId]);
        return $stmt->fetchAll();
    }

    /**
     * All non-empty mobile strings for a family (used by the public verification
     * gate — never shown to the visitor).
     *
     * @return string[]
     */
    public function familyMobiles(int $groupId): array
    {
        $stmt = $this->db->prepare(
            "SELECT mobile FROM members
             WHERE group_id = :gid AND mobile IS NOT NULL AND mobile <> ''"
        );
        $stmt->execute([':gid' => $groupId]);
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * All members for PDF generation, sorted for print order.
     */
    public function getAllForPdf(): array
    {
        $sql = "
            SELECT
                m.*,
                gr.address,
                gr.communication_address,
                gr.is_in_surat,
                g.name  AS gender_name,
                r.name  AS relationship_name
            FROM members m
            JOIN `groups`      gr   ON gr.group_id       = m.group_id
            JOIN genders       g    ON g.gender_id       = m.gender_id
            JOIN relationships r    ON r.relationship_id = m.relationship_id
            LEFT JOIN (
                SELECT group_id,
                       MIN(last_name)  AS last_name,
                       MIN(first_name) AS first_name
                FROM members
                WHERE type = 'P'
                GROUP BY group_id
            ) head ON head.group_id = m.group_id
            ORDER BY
                head.last_name  ASC,
                head.first_name ASC,
                m.display_order ASC
        ";
        return $this->db->query($sql)->fetchAll();
    }
}
