<?php
/**
 * View: public/family — full member details + address, with edit links.
 * Variables: $group, $members
 */
function ph(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function pub_name(array $m): string {
    $parts = array_filter([$m['last_name'] ?? '', $m['title'] ?? '', $m['first_name'] ?? '', $m['middle_name'] ?? '']);
    return implode(' ', array_map('trim', $parts));
}

/** Format a DOB (stored Y-m-d) for display, or em-dash when blank. */
function pub_dob(?string $dob): string {
    if (empty($dob) || $dob === '0000-00-00') return '—';
    $ts = strtotime($dob);
    return $ts ? date('j M Y', $ts) : '—';
}
?>

<div class="pub-card">
    <a href="<?= BASE_URL ?>/" class="pub-sub"><i class="bi bi-arrow-left me-1"></i>Back to search</a>

    <h2 style="font-size:22px;font-weight:800" class="mt-2 mb-1">
        <?= ph($group['head_last_name'] ?? '') ?> Family
    </h2>
    <?php if (isset($_GET['submitted'])): ?>
    <div class="alert alert-success d-flex align-items-start gap-2" style="font-size:17px" role="alert">
        <i class="bi bi-check-circle-fill" style="font-size:22px;color:#2e8b57"></i>
        <div>Thank you! Your correction has been received and will be reviewed by the committee before it appears.
            You can request corrections for other members below.</div>
    </div>
    <?php endif; ?>

    <div class="pub-sub mb-3">Please check the details below. Tap &ldquo;Request correction&rdquo; on any person or on the address to ask the committee to fix something.</div>

    <?php foreach ($members as $m): ?>
    <div class="pub-member">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div style="font-weight:700;font-size:19px">
                <?= ph(pub_name($m)) ?>
                <?php if (($m['type'] ?? '') === 'P'): ?>
                    <span class="pub-badge-head ms-1">Head</span>
                <?php endif; ?>
            </div>
            <a href="<?= BASE_URL ?>/member/<?= (int)$m['member_id'] ?>/edit"
               class="btn btn-outline-primary btn-sm" style="font-size:16px;white-space:nowrap">
                <i class="bi bi-pencil me-1"></i>Request correction
            </a>
        </div>

        <dl class="pub-details mb-0">
            <dt>Relationship</dt><dd><?= ph($m['relationship_name'] ?? '—') ?></dd>
            <dt>Gender</dt><dd><?= ph($m['gender_name'] ?? '—') ?></dd>
            <dt>Date of Birth</dt><dd><?= ph(pub_dob($m['dob'] ?? null)) ?></dd>
            <dt>Education</dt><dd><?= ph(($m['education'] ?? '') !== '' ? $m['education'] : '—') ?></dd>
            <dt>Mobile</dt><dd><?= ph(($m['mobile'] ?? '') !== '' ? $m['mobile'] : '—') ?></dd>
            <dt>Email</dt><dd><?= ph(($m['email'] ?? '') !== '' ? $m['email'] : '—') ?></dd>
        </dl>
    </div>
    <?php endforeach; ?>

    <div class="pub-member">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div style="font-weight:700;font-size:19px">
                <i class="bi bi-geo-alt me-1"></i>Address
            </div>
            <a href="<?= BASE_URL ?>/family/<?= (int)$group['group_id'] ?>/edit-address"
               class="btn btn-outline-primary btn-sm" style="font-size:16px;white-space:nowrap">
                <i class="bi bi-pencil me-1"></i>Request correction
            </a>
        </div>

        <dl class="pub-details mb-0">
            <dt>Address</dt><dd><?= nl2br(ph(($group['address'] ?? '') !== '' ? $group['address'] : '—')) ?></dd>
            <dt>Communication</dt><dd><?= nl2br(ph(($group['communication_address'] ?? '') !== '' ? $group['communication_address'] : '—')) ?></dd>
            <dt>Lives in Surat</dt><dd><?= ((string)($group['is_in_surat'] ?? '1') === '1') ? 'Yes' : 'No' ?></dd>
        </dl>
    </div>
</div>
