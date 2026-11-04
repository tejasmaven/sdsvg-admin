<?php
/**
 * View: public/landing — search box + captcha, and (after a search) results.
 * Variables: $error, $q, $results (null = not searched yet)
 */
$ctrl = new BaseController();
function ph(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>

<div class="pub-card">
    <form action="<?= BASE_URL ?>/search" method="POST" novalidate autocomplete="off">
        <?= $ctrl->csrfField() ?>

        <div class="mb-3">
            <label for="q" class="form-label">Search by name or surname</label>
            <input type="text" id="q" name="q" class="form-control"
                   value="<?= ph($q) ?>" placeholder="e.g. Shah" required <?= $results === null ? 'autofocus' : '' ?>>
            <div class="pub-field-note mt-1">Type any family member's first name or surname.</div>
        </div>

        <div class="mb-3">
            <label for="captcha" class="form-label">Enter the code shown below</label>
            <div class="d-flex align-items-center gap-2 mb-2">
                <img id="captcha-img" class="pub-captcha-img"
                     src="<?= BASE_URL ?>/captcha?for=search" alt="Verification code">
                <button type="button" class="btn btn-outline-secondary"
                        onclick="return reloadCaptcha(this,'search')" title="New code">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <input type="text" id="captcha" name="captcha" class="form-control"
                   inputmode="latin" autocapitalize="characters" maxlength="5"
                   placeholder="Type the 5 letters/numbers" required>
        </div>

        <?php if (!empty($error)): ?>
        <div class="pub-err mb-3"><i class="bi bi-exclamation-triangle me-1"></i><?= ph($error) ?></div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary btn-lg-block">
            <i class="bi bi-search me-2"></i>Search
        </button>
    </form>
</div>

<?php if ($results !== null): ?>
<div class="pub-card" id="results">
    <h2 style="font-size:21px;font-weight:800" class="mb-3">
        Results for &ldquo;<?= ph($q) ?>&rdquo;
    </h2>

    <?php if (empty($results)): ?>
        <div class="pub-sub">No families found. Please check the spelling and try again.</div>
    <?php else: ?>
        <?php foreach ($results as $r): ?>
        <div class="pub-result">
            <div class="pub-result-info">
                <div style="font-weight:700;font-size:20px">
                    <?= ph($r['head_last_name'] ?: 'Family') ?>
                    <?php if (!empty($r['head_first_name'])): ?>
                        <span class="pub-sub">(<?= ph($r['head_first_name']) ?>)</span>
                    <?php endif; ?>
                </div>
                <div class="pub-sub"><?= (int)$r['member_count'] ?> member(s)</div>
            </div>
            <a href="<?= BASE_URL ?>/family/<?= (int)$r['group_id'] ?>" class="btn btn-outline-primary pub-result-btn">
                View <i class="bi bi-chevron-right"></i>
            </a>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    // After a search, bring the results into view (mobile-friendly).
    document.addEventListener('DOMContentLoaded', function () {
        var r = document.getElementById('results');
        if (r) { r.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
</script>
<?php endif; ?>
