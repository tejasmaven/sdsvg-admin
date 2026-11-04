<?php
/**
 * View: families/show
 * Variables: $group, $primaryMember, $secondaryMembers, $isNew
 */
$ctrl = new BaseController();
function h(mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/families">Families</a></li>
        <li class="breadcrumb-item active">
            <?= $primaryMember ? h($primaryMember['last_name']) . ' Family' : 'Family #' . (int)$group['group_id'] ?>
        </li>
    </ol>
</nav>

<!-- "Add another member?" prompt after family creation -->
<?php if ($isNew): ?>
<div class="alert alert-success d-flex align-items-center gap-3">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>
        Family created! Would you like to add another member now?
        <div class="mt-2 d-flex gap-2">
            <a href="<?= BASE_URL ?>/families/<?= (int)$group['group_id'] ?>/members/add"
               class="btn btn-sm btn-success">
                <i class="bi bi-plus me-1"></i>Yes, Add Member
            </a>
            <a href="<?= BASE_URL ?>/families" class="btn btn-sm btn-outline-secondary">No, Back to list</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- No-head banner -->
<?php if (!$primaryMember): ?>
<div class="sdsvg-no-head rounded p-3 mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    This family has no Head member assigned. Please add or promote a Primary member.
</div>
<?php endif; ?>

<!-- Primary member card -->
<?php if ($primaryMember): ?>
<div class="sdsvg-primary-card p-3 mb-4 rounded">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h5 class="mb-1 fw-bold">
                <?= h(trim(implode(' ', array_filter([
                    $primaryMember['title']       ?? '',
                    $primaryMember['first_name']  ?? '',
                    $primaryMember['middle_name'] ?? '',
                    $primaryMember['last_name']   ?? '',
                ])))) ?>
                <span class="badge bg-primary ms-2" style="font-size:11px">Head</span>
                <?php if ($group['is_in_surat']): ?>
                    <span class="badge bg-success ms-1" style="font-size:11px">In Surat</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark ms-1" style="font-size:11px">Outside Surat</span>
                <?php endif; ?>
            </h5>
            <p class="text-muted mb-1" style="font-size:13px">
                <i class="bi bi-house me-1"></i>
                <?= $group['address'] ? h($group['address']) : '<em class="text-muted">No address</em>' ?>
            </p>
            <?php if (!empty($group['communication_address'])): ?>
            <p class="text-muted mb-0" style="font-size:13px">
                <i class="bi bi-envelope me-1"></i>
                <span class="text-muted">Comm:</span> <?= h($group['communication_address']) ?>
            </p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/members/<?= (int)$primaryMember['member_id'] ?>/edit"
               class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i>Edit Member
            </a>
            <a href="<?= BASE_URL ?>/families/<?= (int)$group['group_id'] ?>/edit"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-geo-alt me-1"></i>Edit Address
            </a>
            <!-- Toggle city -->
            <form action="<?= BASE_URL ?>/families/<?= (int)$group['group_id'] ?>/toggle-city" method="POST">
                <?= $ctrl->csrfField() ?>
                <?php if ($group['is_in_surat']): ?>
                <button type="submit" class="btn btn-sm btn-success" title="Mark as Outside Surat">
                    <i class="bi bi-geo-alt-fill me-1"></i>In Surat
                </button>
                <?php else: ?>
                <button type="submit" class="btn btn-sm btn-warning" title="Mark as In Surat">
                    <i class="bi bi-geo-alt me-1"></i>Outside Surat
                </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="row g-2 mt-2" style="font-size:13px">
        <div class="col-sm-4">
            <span class="text-muted">DOB:</span>
            <?= $primaryMember['dob'] ? date('d M Y', strtotime($primaryMember['dob'])) : '—' ?>
        </div>
        <div class="col-sm-4">
            <span class="text-muted">Gender:</span>
            <?= h($primaryMember['gender_name']) ?>
        </div>
        <div class="col-sm-4">
            <span class="text-muted">Relationship:</span>
            <?= h($primaryMember['relationship_name']) ?>
        </div>
        <div class="col-sm-4">
            <span class="text-muted">Mobile:</span>
            <?= $primaryMember['mobile'] ? h($primaryMember['mobile']) : '—' ?>
        </div>
        <div class="col-sm-4">
            <span class="text-muted">Email:</span>
            <?= $primaryMember['email'] ? '<a href="mailto:' . h($primaryMember['email']) . '">' . h($primaryMember['email']) . '</a>' : '—' ?>
        </div>
        <div class="col-sm-4">
            <span class="text-muted">Education:</span>
            <?= $primaryMember['education'] ? h($primaryMember['education']) : '—' ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- All members table (ordered by display_order) -->
<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Family Members</strong>
        <a href="<?= BASE_URL ?>/families/<?= (int)$group['group_id'] ?>/members/add"
           class="btn btn-sm btn-success">
            <i class="bi bi-plus me-1"></i>Add Member
        </a>
    </div>

    <?php if (empty($members)): ?>
    <div class="card-body text-muted text-center py-4">
        <i class="bi bi-person-plus fs-3 d-block mb-2 text-secondary"></i>
        No members yet. <a href="<?= BASE_URL ?>/families/<?= (int)$group['group_id'] ?>/members/add">Add one?</a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:13px">
            <thead class="table-light">
                <tr>
                    <th style="width:64px" class="text-center">Order</th>
                    <th>Full Name</th>
                    <th style="width:120px">Relationship</th>
                    <th style="width:100px">DOB</th>
                    <th style="width:130px">Mobile</th>
                    <th>Email</th>
                    <th style="width:110px">Education</th>
                    <th style="width:130px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($members as $i => $m):
                $fullName = trim(implode(' ', array_filter([
                    $m['title']       ?? '',
                    $m['first_name']  ?? '',
                    $m['middle_name'] ?? '',
                    $m['last_name']   ?? '',
                ])));
                $isPrimary = ($m['type'] === 'P');
                $isFirst   = ($i === 0);
                $isLast    = ($i === count($members) - 1);
            ?>
            <tr<?= $isPrimary ? ' class="table-primary"' : '' ?>>
                <!-- ↑ / ↓ order controls -->
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <?php if (!$isFirst): ?>
                        <form action="<?= BASE_URL ?>/members/<?= (int)$m['member_id'] ?>/move-up" method="POST">
                            <?= $ctrl->csrfField() ?>
                            <button type="submit" class="btn btn-xs btn-outline-secondary"
                                    style="font-size:11px;padding:1px 6px" title="Move up">
                                <i class="bi bi-chevron-up"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if (!$isLast): ?>
                        <form action="<?= BASE_URL ?>/members/<?= (int)$m['member_id'] ?>/move-down" method="POST">
                            <?= $ctrl->csrfField() ?>
                            <button type="submit" class="btn btn-xs btn-outline-secondary"
                                    style="font-size:11px;padding:1px 6px" title="Move down">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>

                <!-- Full name -->
                <td>
                    <?= h($fullName) ?>
                    <?php if ($isPrimary): ?>
                        <span class="badge bg-primary ms-1" style="font-size:10px">Head</span>
                    <?php endif; ?>
                </td>

                <td><?= h($m['relationship_name']) ?></td>
                <td><?= $m['dob'] ? date('d M Y', strtotime($m['dob'])) : '—' ?></td>
                <td><?= h($m['mobile'] ?? '') ?: '—' ?></td>
                <td><?= $m['email'] ? '<a href="mailto:' . h($m['email']) . '">' . h($m['email']) . '</a>' : '—' ?></td>
                <td><?= h($m['education'] ?? '') ?: '—' ?></td>

                <!-- Actions -->
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="<?= BASE_URL ?>/members/<?= (int)$m['member_id'] ?>/edit"
                           class="btn btn-xs btn-outline-primary" style="font-size:11px;padding:2px 6px"
                           title="Edit member">
                            <i class="bi bi-pencil"></i>
                        </a>

                        <?php if (!$isPrimary): ?>
                        <!-- Promote to head -->
                        <form action="<?= BASE_URL ?>/members/<?= (int)$m['member_id'] ?>/promote"
                              method="POST"
                              <?= $primaryMember ? 'data-has-head="true"' : '' ?>>
                            <?= $ctrl->csrfField() ?>
                            <button type="submit" class="btn btn-xs btn-outline-warning"
                                    style="font-size:11px;padding:2px 6px" title="Promote to Head">
                                <i class="bi bi-arrow-up-circle"></i>
                            </button>
                        </form>

                        <!-- Delete -->
                        <form action="<?= BASE_URL ?>/members/<?= (int)$m['member_id'] ?>/delete"
                              method="POST"
                              data-confirm-delete
                              data-member-name="<?= h($m['first_name'] . ' ' . $m['last_name']) ?>">
                            <?= $ctrl->csrfField() ?>
                            <button type="submit" class="btn btn-xs btn-outline-danger"
                                    style="font-size:11px;padding:2px 6px" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Delete entire family -->
<div class="card border-danger">
    <div class="card-header bg-danger text-white">
        <i class="bi bi-trash3 me-2"></i>Danger Zone
    </div>
    <div class="card-body">
        <p class="mb-2 fw-semibold">Delete Entire Family</p>
        <p class="text-muted mb-3" style="font-size:13px">
            This permanently deletes this family and ALL <?= (int)$group['member_count'] ?> member(s). This cannot be undone.
        </p>
        <form action="<?= BASE_URL ?>/families/<?= (int)$group['group_id'] ?>/delete"
              method="POST"
              data-typed-confirm
              data-confirm-value="<?= h($group['head_last_name'] ?? '') ?>">
            <?= $ctrl->csrfField() ?>
            <div class="input-group" style="max-width:340px">
                <input type="text" name="confirm_name" class="form-control"
                       placeholder="Type the family last name to confirm">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash3 me-1"></i>Delete Family
                </button>
            </div>
            <small class="text-muted d-block mt-1">
                Type <strong><?= h($group['head_last_name'] ?? '(no head)') ?></strong> to confirm.
            </small>
        </form>
    </div>
</div>
