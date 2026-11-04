<?php
/**
 * View: reviews/index — list of public edit requests.
 * Variables: $requests, $status, $pendingCount
 */
function h(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function statusBadge(string $s): string {
    $map = ['pending'=>'bg-warning text-dark','approved'=>'bg-success','partial'=>'bg-info text-dark','rejected'=>'bg-secondary'];
    $cls = $map[$s] ?? 'bg-secondary';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8') . '</span>';
}
$filters = ['pending'=>'Pending','approved'=>'Approved','partial'=>'Partial','rejected'=>'Rejected','all'=>'All'];
?>

<div class="d-flex gap-2 mb-3 flex-wrap">
    <?php foreach ($filters as $key => $label): ?>
    <a href="<?= BASE_URL ?>/reviews?status=<?= $key ?>"
       class="btn btn-sm <?= $status === $key ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <?= h($label) ?><?php if ($key === 'pending' && $pendingCount > 0): ?> (<?= (int)$pendingCount ?>)<?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Family</th>
                    <th>Target</th>
                    <th>Submitted</th>
                    <th>IP</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No requests found.</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?= (int)$r['request_id'] ?></td>
                    <td><?= h($r['head_last_name'] ?: ('Family #' . $r['group_id'])) ?></td>
                    <td>
                        <?php if ($r['target_type'] === 'member'): ?>
                            <i class="bi bi-person me-1"></i><?= h(trim(($r['target_first_name'] ?? '') . ' ' . ($r['target_last_name'] ?? ''))) ?: 'Member' ?>
                        <?php else: ?>
                            <i class="bi bi-geo-alt me-1"></i>Address
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px"><?= h(date('d M Y, H:i', strtotime($r['created_at']))) ?></td>
                    <td style="font-size:13px"><?= h($r['ip_address'] ?? '') ?></td>
                    <td><?= statusBadge($r['status']) ?></td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/reviews/<?= (int)$r['request_id'] ?>" class="btn btn-sm btn-outline-primary">
                            Review <i class="bi bi-chevron-right"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
