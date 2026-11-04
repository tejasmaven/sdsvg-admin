<?php
/**
 * View: families/index
 * Variables: $families, $search, $page, $totalPages, $total
 */
?>

<!-- Search + header row -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form action="<?= BASE_URL ?>/families" method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:400px">
        <input type="text" name="search" class="form-control" placeholder="Search by name or address…"
               value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
        <button class="btn btn-outline-secondary" type="submit">
            <i class="bi bi-search"></i>
        </button>
        <?php if ($search): ?>
        <a href="<?= BASE_URL ?>/families" class="btn btn-outline-danger" title="Clear search">
            <i class="bi bi-x-lg"></i>
        </a>
        <?php endif; ?>
    </form>
    <a href="<?= BASE_URL ?>/families/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Add Family
    </a>
</div>

<?php if (empty($families)): ?>
    <div class="alert alert-info">
        <?php if ($search): ?>
            No families found matching <strong><?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?></strong>.
            <a href="<?= BASE_URL ?>/families">Show all families</a>
        <?php else: ?>
            No families yet. <a href="<?= BASE_URL ?>/families/create">Add the first family</a>.
        <?php endif; ?>
    </div>
<?php else: ?>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>
            <?= number_format($total) ?> <?= $total === 1 ? 'family' : 'families' ?>
            <?= $search ? '(filtered)' : '' ?>
        </span>
        <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?></small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:80px">ID</th>
                    <th>Last Name</th>
                    <th>Head Name</th>
                    <th style="width:90px" class="text-center">Members</th>
                    <th style="width:110px">City</th>
                    <th style="width:90px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($families as $f): ?>
            <tr>
                <td><span class="text-muted">#<?= (int)$f['group_id'] ?></span></td>
                <td><?= htmlspecialchars($f['head_last_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?php if ($f['head_first_name']): ?>
                        <?= htmlspecialchars(
                            trim(($f['head_title'] ? $f['head_title'] . ' ' : '') . $f['head_first_name'] . ' ' . $f['head_last_name']),
                            ENT_QUOTES, 'UTF-8'
                        ) ?>
                    <?php else: ?>
                        <span class="badge bg-danger">No Head</span>
                    <?php endif; ?>
                </td>
                <td class="text-center"><?= (int)$f['member_count'] ?></td>
                <td>
                    <?php if ($f['is_in_surat']): ?>
                        <span class="badge bg-success">In Surat</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Outside</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= BASE_URL ?>/families/<?= (int)$f['group_id'] ?>"
                       class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm mb-0">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link"
               href="<?= BASE_URL ?>/families?page=<?= $p ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                <?= $p ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php endif; ?>
