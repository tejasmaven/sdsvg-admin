<?php
/**
 * View: reviews/show — field-level diff with tick-to-accept.
 * Variables: $req (with ['original'],['payload']), $fields, $genders, $relationships
 */
$ctrl = new BaseController();
function h(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$labels = [
    'title'=>'Title','first_name'=>'First Name','middle_name'=>'Middle Name','last_name'=>'Last Name',
    'dob'=>'Date of Birth','gender_id'=>'Gender','relationship_id'=>'Relationship',
    'mobile'=>'Mobile','email'=>'Email','education'=>'Education',
    'address'=>'Address','communication_address'=>'Communication Address','is_in_surat'=>'Lives in Surat',
];

/** Human-readable value for a field. */
$disp = function(string $field, string $val) use ($genders, $relationships): string {
    if ($val === '') return '—';
    if ($field === 'gender_id')       return $genders[$val]       ?? $val;
    if ($field === 'relationship_id') return $relationships[$val] ?? $val;
    if ($field === 'is_in_surat')     return $val === '1' ? 'Yes' : 'No';
    return $val;
};

$id       = (int)$req['request_id'];
$isMember = $req['target_type'] === 'member';
$pending  = $req['status'] === 'pending';
?>

<div class="mb-3">
    <a href="<?= BASE_URL ?>/reviews" class="text-muted"><i class="bi bi-arrow-left me-1"></i>Back to requests</a>
</div>

<div class="row g-3">
    <!-- Metadata -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><strong>Request details</strong></div>
            <div class="card-body" style="font-size:14px">
                <p class="mb-1"><strong>Type:</strong> <?= $isMember ? 'Member' : 'Family address' ?></p>
                <p class="mb-1"><strong>Status:</strong> <?= h(ucfirst($req['status'])) ?></p>
                <p class="mb-1"><strong>Submitted:</strong> <?= h(date('d M Y, H:i:s', strtotime($req['created_at']))) ?></p>
                <hr>
                <p class="mb-1"><strong>IP address:</strong> <?= h($req['ip_address'] ?? '—') ?></p>
                <p class="mb-1"><strong>Language:</strong> <?= h($req['accept_language'] ?? '—') ?></p>
                <p class="mb-1"><strong>Referer:</strong> <?= h($req['referer'] ?: '—') ?></p>
                <p class="mb-0"><strong>Browser (UA):</strong><br>
                    <span class="text-muted" style="word-break:break-all"><?= h($req['user_agent'] ?? '—') ?></span></p>
                <?php if (!empty($req['review_note'])): ?>
                <hr><p class="mb-0"><strong>Review note:</strong> <?= h($req['review_note']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Diff + actions -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Proposed changes</strong>
                <span class="text-muted" style="font-size:13px">Tick the fields to accept</span>
            </div>
            <div class="card-body">
                <?php if (!$pending): ?>
                <div class="alert alert-info">This request was already reviewed (<?= h($req['status']) ?>). It is read-only.</div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/reviews/<?= $id ?>/approve" method="POST">
                    <?= $ctrl->csrfField() ?>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:42px"></th>
                                    <th>Field</th>
                                    <th>Current</th>
                                    <th>Requested</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($fields as $f):
                                $old = (string)($req['original'][$f] ?? '');
                                $new = (string)($req['payload'][$f] ?? '');
                                $changed = ($old !== $new);
                            ?>
                                <tr class="<?= $changed ? 'table-warning' : '' ?>">
                                    <td class="text-center">
                                        <?php if ($changed && $pending): ?>
                                        <input class="form-check-input" type="checkbox"
                                               name="accept[]" value="<?= h($f) ?>" checked>
                                        <?php elseif ($changed): ?>
                                        <i class="bi bi-dot"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= h($labels[$f] ?? $f) ?></strong></td>
                                    <td class="text-muted"><?= h($disp($f, $old)) ?></td>
                                    <td class="<?= $changed ? 'fw-semibold' : 'text-muted' ?>">
                                        <?= h($disp($f, $new)) ?>
                                        <?php if (!$changed): ?><span class="badge bg-light text-muted ms-1">no change</span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($pending): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Review note (optional)</label>
                        <input type="text" name="note" class="form-control" placeholder="Why approved / rejected…">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success"
                                formaction="<?= BASE_URL ?>/reviews/<?= $id ?>/approve">
                            <i class="bi bi-check2 me-1"></i>Apply selected
                        </button>
                        <button type="submit" class="btn btn-outline-danger"
                                formaction="<?= BASE_URL ?>/reviews/<?= $id ?>/reject"
                                onclick="return confirm('Reject this request without changes?')">
                            <i class="bi bi-x-lg me-1"></i>Reject
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
