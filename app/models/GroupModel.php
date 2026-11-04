<?php
/**
 * GroupModel — families (groups table)
 */
class GroupModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Paginated list with head member info and member count.
     *
     * @return array{rows: array, total: int}
     */
    public function findAll(string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $like   = '%' . $search . '%';

        $where = $search !== ''
            ? "WHERE (pm.last_name LIKE :like OR pm.first_name LIKE :like2 OR g.address LIKE :like3)"
            : "";

        $countSql = "
            SELECT COUNT(DISTINCT g.group_id)
            FROM `groups` g
            LEFT JOIN members pm ON pm.group_id = g.group_id AND pm.type = 'P'
            {$where}
        ";
        $stmt = $this->db->prepare($countSql);
        if ($search !== '') {
            $stmt->bindValue(':like',  $like);
            $stmt->bindValue(':like2', $like);
            $stmt->bindValue(':like3', $like);
        }
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        $sql = "
            SELECT
                g.group_id,
                g.address,
                g.communication_address,
                g.is_in_surat,
                g.created_at,
                pm.member_id   AS head_member_id,
                pm.first_name  AS head_first_name,
                pm.last_name   AS head_last_name,
                pm.title       AS head_title,
                (SELECT COUNT(*) FROM members m2 WHERE m2.group_id = g.group_id) AS member_count
            FROM `groups` g
            LEFT JOIN members pm ON pm.group_id = g.group_id AND pm.type = 'P'
            {$where}
            ORDER BY pm.last_name ASC, pm.first_name ASC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':like',  $like);
            $stmt->bindValue(':like2', $like);
            $stmt->bindValue(':like3', $like);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * Find a single group by ID with head member info and member count.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                g.group_id,
                g.address,
                g.communication_address,
                g.is_in_surat,
                g.created_at,
                g.updated_at,
                pm.member_id  AS head_member_id,
                pm.first_name AS head_first_name,
                pm.last_name  AS head_last_name,
                pm.title      AS head_title,
                (SELECT COUNT(*) FROM members m2 WHERE m2.group_id = g.group_id) AS member_count
            FROM `groups` g
            LEFT JOIN members pm ON pm.group_id = g.group_id AND pm.type = 'P'
            WHERE g.group_id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Insert a new group, return its ID.
     */
    public function create(?string $address, ?string $communicationAddress = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `groups` (address, communication_address) VALUES (:address, :comm_address)"
        );
        $stmt->execute([':address' => $address, ':comm_address' => $communicationAddress]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update address fields of an existing group.
     */
    public function update(int $id, ?string $address, ?string $communicationAddress = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `groups` SET address = :address, communication_address = :comm_address WHERE group_id = :id"
        );
        $stmt->execute([':address' => $address, ':comm_address' => $communicationAddress, ':id' => $id]);
    }

    /**
     * Set is_in_surat to an explicit value (0 or 1).
     */
    public function setInSurat(int $id, int $value): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `groups` SET is_in_surat = :v WHERE group_id = :id"
        );
        $stmt->execute([':v' => $value ? 1 : 0, ':id' => $id]);
    }

    /**
     * Toggle is_in_surat for a group (1→0, 0→1).
     */
    public function toggleCity(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `groups` SET is_in_surat = 1 - is_in_surat WHERE group_id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * Delete a group (CASCADE removes its members).
     */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM `groups` WHERE group_id = :id");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Public portal search: families having any member whose first or last name
     * matches $q. Returns family id + head surname/first name only (no PII).
     * Capped to avoid dumping the whole directory to anonymous visitors.
     */
    public function publicSearch(string $q, int $limit = 50): array
    {
        $like = '%' . $q . '%';
        $sql = "
            SELECT DISTINCT
                g.group_id,
                pm.last_name  AS head_last_name,
                pm.first_name AS head_first_name,
                (SELECT COUNT(*) FROM members mc WHERE mc.group_id = g.group_id) AS member_count
            FROM `groups` g
            LEFT JOIN members pm ON pm.group_id = g.group_id AND pm.type = 'P'
            WHERE EXISTS (
                SELECT 1 FROM members m
                WHERE m.group_id = g.group_id
                  AND (m.first_name LIKE :like1 OR m.last_name LIKE :like2)
            )
            ORDER BY pm.last_name ASC, pm.first_name ASC
            LIMIT :limit
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':like1', $like);
        $stmt->bindValue(':like2', $like);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Return all groups that have no Primary (type='P') member.
     */
    public function getHeadlessFamilies(): array
    {
        $sql = "
            SELECT g.group_id, g.address
            FROM `groups` g
            WHERE NOT EXISTS (
                SELECT 1 FROM members m
                WHERE m.group_id = g.group_id AND m.type = 'P'
            )
            ORDER BY g.group_id ASC
        ";
        return $this->db->query($sql)->fetchAll();
    }
}
