<?php
/**
 * View: dashboard/index
 * Variables: $totalFamilies, $totalMembers, $totalMale, $totalFemale,
 *            $maleSenior, $femaleSenior, $kids, $noDob, $noEducation, $noMobile,
 *            $noEmail, $noAddress, $inSurat, $outsideSurat, $pendingReviews,
 *            $headlessFamilies, $seniorAge, $kidAge
 */

$memberTotal = max(1, (int)$totalMembers); // guard against divide-by-zero

/** Percent of total members, as a short string e.g. "48% of members". */
function pctOfMembers(int $value, int $base): string {
    if ($base <= 0) return '';
    return round($value / $base * 100) . '% of members';
}

/** Render one stat card (always coloured; optional sub-line and link). */
function statCard(array $c): string {
    $num   = number_format((int)($c['value'] ?? 0));
    $label = htmlspecialchars($c['label'] ?? '', ENT_QUOTES, 'UTF-8');
    $icon  = htmlspecialchars($c['icon']  ?? 'bi-dot', ENT_QUOTES, 'UTF-8');
    $class = htmlspecialchars($c['class'] ?? 'sdsvg-stat-slate', ENT_QUOTES, 'UTF-8');
    $sub   = isset($c['sub']) && $c['sub'] !== ''
        ? '<div style="font-size:12px;opacity:.85;margin-top:2px">' . htmlspecialchars($c['sub'], ENT_QUOTES, 'UTF-8') . '</div>'
        : '';
    $body  = '<div class="card ' . $class . ' border-0 rounded-3 h-100">'
           . '<div class="card-body d-flex align-items-center gap-3">'
           . '<i class="bi ' . $icon . '" style="font-size:2.2rem;opacity:.85"></i>'
           . '<div><div style="font-size:1.9rem;font-weight:700;line-height:1">' . $num . '</div>'
           . '<div style="font-size:13px;opacity:.9">' . $label . '</div>' . $sub . '</div>'
           . '</div></div>';
    if (!empty($c['href'])) {
        $href = htmlspecialchars($c['href'], ENT_QUOTES, 'UTF-8');
        return '<a href="' . $href . '" class="text-decoration-none">' . $body . '</a>';
    }
    return $body;
}

/** Render a row of cards with a shared column class. */
function statRow(array $cards, string $colClass): void {
    echo '<div class="row g-3 mb-3">';
    foreach ($cards as $c) {
        echo '<div class="' . $colClass . '">' . statCard($c) . '</div>';
    }
    echo '</div>';
}
?>

<?php
// Line 1 — Families / Members
statRow([
    ['label' => 'Total Families', 'value' => $totalFamilies, 'icon' => 'bi-house-heart', 'class' => 'sdsvg-stat-blue'],
    ['label' => 'Total Members',  'value' => $totalMembers,  'icon' => 'bi-people',       'class' => 'sdsvg-stat-green'],
], 'col-6 col-md-6');

// Line 2 — Male / Female (% of members)
statRow([
    ['label' => 'Total Male',   'value' => $totalMale,   'icon' => 'bi-gender-male',   'class' => 'sdsvg-stat-teal', 'sub' => pctOfMembers($totalMale, $memberTotal)],
    ['label' => 'Total Female', 'value' => $totalFemale, 'icon' => 'bi-gender-female', 'class' => 'sdsvg-stat-pink', 'sub' => pctOfMembers($totalFemale, $memberTotal)],
], 'col-6 col-md-6');

// Line 3 — Seniors / Kids (% of members)
statRow([
    ['label' => "Male above {$seniorAge} yrs",   'value' => $maleSenior,   'icon' => 'bi-person',      'class' => 'sdsvg-stat-slate',  'sub' => pctOfMembers($maleSenior, $memberTotal)],
    ['label' => "Female above {$seniorAge} yrs", 'value' => $femaleSenior, 'icon' => 'bi-person',      'class' => 'sdsvg-stat-purple', 'sub' => pctOfMembers($femaleSenior, $memberTotal)],
    ['label' => "Kids below {$kidAge} yrs",      'value' => $kids,         'icon' => 'bi-emoji-smile', 'class' => 'sdsvg-stat-orange', 'sub' => pctOfMembers($kids, $memberTotal)],
], 'col-6 col-md-4');

// Line 4 — City split
statRow([
    ['label' => 'Families in Surat',      'value' => $inSurat,      'icon' => 'bi-geo-alt',    'class' => 'sdsvg-stat-blue'],
    ['label' => 'Families outside Surat', 'value' => $outsideSurat, 'icon' => 'bi-signpost-2', 'class' => 'sdsvg-stat-slate'],
], 'col-6 col-md-6');

// Line 5 — Data completeness (coloured by whether anything is missing)
$flag = fn(int $n): string => $n > 0 ? 'sdsvg-stat-red' : 'sdsvg-stat-green';
statRow([
    ['label' => 'Families without Address', 'value' => $noAddress,   'icon' => 'bi-house-slash', 'class' => $flag($noAddress)],
    ['label' => 'Members without DOB',      'value' => $noDob,       'icon' => 'bi-calendar-x',  'class' => $flag($noDob)],
    ['label' => 'Members without Education','value' => $noEducation, 'icon' => 'bi-mortarboard', 'class' => $flag($noEducation)],
    ['label' => 'Members without Mobile',   'value' => $noMobile,    'icon' => 'bi-phone-vibrate','class' => $flag($noMobile)],
    ['label' => 'Members without Email',    'value' => $noEmail,     'icon' => 'bi-envelope-x',  'class' => $flag($noEmail)],
], 'col-6 col-md-4 col-xl');
?>

<?php if ($pendingReviews > 0): ?>
<a href="<?= BASE_URL ?>/reviews" class="alert alert-warning d-flex align-items-center gap-2 text-decoration-none" role="alert">
    <i class="bi bi-inbox-fill"></i>
    <span><strong><?= number_format($pendingReviews) ?></strong> pending edit request<?= $pendingReviews === 1 ? '' : 's' ?> awaiting review.</span>
</a>
<?php endif; ?>

<?php if ($noDob > 0): ?>
<div class="text-muted mb-3" style="font-size:13px">
    <i class="bi bi-info-circle me-1"></i>
    Age-based counts (seniors, kids) exclude the <?= number_format($noDob) ?>
    member<?= $noDob === 1 ? '' : 's' ?> with no date of birth recorded.
</div>
<?php endif; ?>

<!-- Headless families alert -->
<?php if (!empty($headlessFamilies)): ?>
<div class="alert alert-danger">
    <h6 class="alert-heading">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= count($headlessFamilies) ?> <?= count($headlessFamilies) === 1 ? 'family has' : 'families have' ?> no Head member assigned
    </h6>
    <ul class="mb-0 mt-2">
        <?php foreach ($headlessFamilies as $f): ?>
        <li>
            <a href="<?= BASE_URL ?>/families/<?= (int)$f['group_id'] ?>">
                Family #<?= (int)$f['group_id'] ?>
            </a>
            <?php if ($f['address']): ?>
             — <?= htmlspecialchars(substr($f['address'], 0, 60), ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
