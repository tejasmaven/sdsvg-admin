<?php
/**
 * View: pdf/preview2
 * Variables: $families (grouped by group_id), $settings
 */
$ctrl = new BaseController();
function h2(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$communityName = $settings['community_full_name'] ?? 'SDSVG';
$editionDate   = ($settings['edition_date_override'] ?? '') ?: date('M Y');

// Count families per city type for button labels
$totalAll  = count($families);
$totalIn   = 0;
$totalOut  = 0;
foreach ($families as $members) {
    $head = null;
    foreach ($members as $m) { if ($m['type'] === 'P') { $head = $m; break; } }
    if ($head === null) $head = $members[0] ?? null;
    if ($head !== null && $head['is_in_surat']) $totalIn++; else $totalOut++;
}

// Build per-letter counters for badge labels (same logic as PdfBuilder2)
$letterCounters = [];
?>

<!-- ── Top action bar ──────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0 fw-semibold"><?= h2($communityName) ?> — Community Directory</h5>
        <small class="text-muted">Edition: <?= h2($editionDate) ?></small>
    </div>
    <form action="<?= BASE_URL ?>/pdf/generate2" method="POST">
        <?= $ctrl->csrfField() ?>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-file-earmark-arrow-down me-2"></i>Download PDF
        </button>
    </form>
</div>

<!-- ── City filter + family count ─────────────────────────────────────────── -->
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <div class="btn-group" role="group" aria-label="City filter">
        <button type="button" class="btn btn-outline-secondary active" id="filter-all"
                onclick="setFilter('all')">
            All <span class="badge bg-secondary ms-1" id="count-all"><?= $totalAll ?></span>
        </button>
        <button type="button" class="btn btn-outline-success" id="filter-in"
                onclick="setFilter('in')">
            <i class="bi bi-geo-alt-fill me-1"></i>In Surat
            <span class="badge bg-success ms-1" id="count-in"><?= $totalIn ?></span>
        </button>
        <button type="button" class="btn btn-outline-warning" id="filter-out"
                onclick="setFilter('out')">
            <i class="bi bi-geo-alt me-1"></i>Outside Surat
            <span class="badge bg-warning text-dark ms-1" id="count-out"><?= $totalOut ?></span>
        </button>
    </div>
    <span class="text-muted" style="font-size:13px" id="filter-label">
        Showing all <?= $totalAll ?> families
    </span>
</div>

<?php if (empty($families)): ?>
<div class="alert alert-info">No family data found. Please add families before generating the PDF.</div>
<?php else: ?>

<!-- ── Family cards ────────────────────────────────────────────────────────── -->
<?php foreach ($families as $groupId => $members):
    // Identify head
    $head = null;
    foreach ($members as $m) {
        if ($m['type'] === 'P') { $head = $m; break; }
    }
    if ($head === null) $head = $members[0];

    $letter = strtoupper(mb_substr(trim($head['last_name']), 0, 1)) ?: 'X';
    $letterCounters[$letter] = ($letterCounters[$letter] ?? 0) + 1;
    $badge = $letter . '-' . str_pad($letterCounters[$letter], 3, '0', STR_PAD_LEFT);

    $address     = trim($head['address'] ?? '');
    $commAddress = trim($head['communication_address'] ?? '');
    $isInSurat   = (bool)($head['is_in_surat'] ?? 1);
    $cityAttr    = $isInSurat ? 'in' : 'out';
?>
<div class="card mb-3 shadow-sm family-card" data-city="<?= $cityAttr ?>" style="font-size:13px">

    <!-- Family badge bar -->
    <div class="card-header d-flex justify-content-between align-items-center py-1 px-3"
         style="background:#f5f5f5">
        <div class="d-flex align-items-center gap-2">
            <?php if ($isInSurat): ?>
                <span class="badge bg-success" style="font-size:11px">
                    <i class="bi bi-geo-alt-fill me-1"></i>In Surat
                </span>
            <?php else: ?>
                <span class="badge bg-warning text-dark" style="font-size:11px">
                    <i class="bi bi-geo-alt me-1"></i>Outside Surat
                </span>
            <?php endif; ?>

            <!-- Toggle city button -->
            <form action="<?= BASE_URL ?>/families/<?= (int)$groupId ?>/toggle-city" method="POST" class="d-inline">
                <?= $ctrl->csrfField() ?>
                <input type="hidden" name="redirect" value="/pdf/preview2">
                <?php if ($isInSurat): ?>
                <button type="submit" class="btn btn-sm btn-outline-warning" style="font-size:11px;padding:1px 8px">
                    <i class="bi bi-arrow-left-right me-1"></i>Move Outside Surat
                </button>
                <?php else: ?>
                <button type="submit" class="btn btn-sm btn-outline-success" style="font-size:11px;padding:1px 8px">
                    <i class="bi bi-arrow-left-right me-1"></i>Move In Surat
                </button>
                <?php endif; ?>
            </form>
        </div>
        <strong style="color:#555"><?= h2($badge) ?></strong>
    </div>

    <!-- Members table -->
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0" style="font-size:12px">
            <thead style="background:#b45a19;color:#fff">
                <tr>
                    <th style="width:32px">No.</th>
                    <th>Name</th>
                    <th style="width:60px">Gender</th>
                    <th style="width:80px">Relation</th>
                    <th style="width:82px">DOB</th>
                    <th style="width:90px">Education</th>
                    <th style="width:100px">Mobile</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
            <?php $rowNum = 1; foreach ($members as $m):
                $isPrimary = ($m['type'] === 'P');
                $dob = $m['dob'] ? date('j-M-Y', strtotime($m['dob'])) : '—';
                $name = implode(' ', array_filter([
                    $m['last_name'], $m['title'], $m['first_name'], $m['middle_name']
                ]));
            ?>
            <tr<?= $isPrimary ? ' class="table-light fw-semibold"' : '' ?>>
                <td class="text-center"><?= $rowNum++ ?></td>
                <td><?= h2($name) ?></td>
                <td><?= h2($m['gender_name'] ?? '') ?></td>
                <td><?= h2($m['relationship_name'] ?? '') ?></td>
                <td><?= h2($dob) ?></td>
                <td><?= h2($m['education'] ?? '') ?></td>
                <td><?= h2($m['mobile'] ?? '') ?></td>
                <td><?= h2($m['email'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Address rows -->
    <div style="background:#f9f9f9;border-top:1px solid #dee2e6;font-size:12px">
        <?php if ($address): ?>
        <div class="px-3 py-1">
            <span class="text-muted fw-semibold">Address:</span>
            <?= h2($address) ?>
        </div>
        <?php endif; ?>

        <?php if ($commAddress): ?>
        <div class="px-3 py-1" style="<?= $address ? 'border-top:1px solid #eee' : '' ?>">
            <span class="text-muted fw-semibold">Comm. Address:</span>
            <?= h2($commAddress) ?>
        </div>
        <?php endif; ?>

        <?php if (!$address && !$commAddress): ?>
        <div class="px-3 py-1 text-muted">
            <span class="fw-semibold">Address:</span> <em>—</em>
        </div>
        <?php endif; ?>
    </div>

</div>
<?php endforeach; ?>

<?php endif; ?>

<script>
(function () {
    var current = 'all';
    var labels  = {
        all: 'Showing all <?= $totalAll ?> families',
        in:  'Showing <?= $totalIn ?> In Surat families',
        out: 'Showing <?= $totalOut ?> Outside Surat families'
    };

    window.setFilter = function (value) {
        current = value;
        var cards = document.querySelectorAll('.family-card');
        cards.forEach(function (card) {
            var city = card.getAttribute('data-city');
            card.style.display = (value === 'all' || city === value) ? '' : 'none';
        });

        // Update button active states
        ['all', 'in', 'out'].forEach(function (v) {
            var btn = document.getElementById('filter-' + v);
            btn.classList.toggle('active', v === value);
        });

        document.getElementById('filter-label').textContent = labels[value];
    };
})();
</script>
