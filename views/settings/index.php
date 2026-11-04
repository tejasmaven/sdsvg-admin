<?php
/**
 * View: settings/index
 * Variables: $settings, $relationships, $genders
 *
 * Tabs: Cover Image | Introduction | Committee | Relationships | Genders
 * The first three map to the PDF front matter rendered by PdfBuilder2:
 *   Cover Image  → PDF page 1
 *   Introduction → PDF page 2 (title = Community Full Name, + text; Gujarati)
 *   Committee    → committee members page
 */
$ctrl = new BaseController();
function h(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$coverImagePath = $settings['cover_image_path'] ?? '';
$committeeText  = $settings['committee_text']   ?? '';

// Front-matter visibility toggles (default hidden / '0')
$showCover     = ($settings['pdf_show_cover']     ?? '0') === '1';
$showIntro     = ($settings['pdf_show_intro']     ?? '0') === '1';
$showCommittee = ($settings['pdf_show_committee'] ?? '0') === '1';
$committee = [];
if ($committeeText !== '') {
    $decoded = @json_decode($committeeText, true);
    if (is_array($decoded)) $committee = $decoded;
}
?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="settings-tabs">
    <li class="nav-item">
        <button class="nav-link nav-tab" data-tab="cover-image">
            <i class="bi bi-image me-1"></i>Cover Image
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link nav-tab" data-tab="introduction">
            <i class="bi bi-file-earmark-text me-1"></i>Introduction
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link nav-tab" data-tab="committee">
            <i class="bi bi-people me-1"></i>Committee
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link nav-tab" data-tab="relationships">
            <i class="bi bi-diagram-3 me-1"></i>Relationships
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link nav-tab" data-tab="genders">
            <i class="bi bi-gender-ambiguous me-1"></i>Genders
        </button>
    </li>
</ul>

<!-- ══ Tab: Cover Image ═══════════════════════════════════════════════════════ -->
<div id="tab-cover-image" class="tab-pane d-none">
    <div class="card" style="max-width:520px">
        <div class="card-header bg-white"><strong>Cover Image</strong>
            <span class="text-muted ms-2" style="font-size:13px">Shown as page 1 of the generated PDF.</span>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>/settings" method="POST" class="mb-3 pb-3 border-bottom">
                <?= $ctrl->csrfField() ?>
                <input type="hidden" name="pdf_show_cover" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="toggle-cover" name="pdf_show_cover" value="1" <?= $showCover ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="toggle-cover">Include cover page in PDF</label>
                </div>
                <div class="form-text mb-2">Off by default. The page is included only when this is on and an image is uploaded.</div>
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-check2 me-1"></i>Save
                </button>
            </form>
            <?php if ($coverImagePath && file_exists(__DIR__ . '/../../public/' . $coverImagePath)): ?>
            <div class="mb-3">
                <img src="<?= BASE_URL ?>/public/<?= h($coverImagePath) ?>"
                     alt="Cover image preview" class="img-thumbnail" style="max-height:220px">
            </div>
            <?php else: ?>
            <p class="text-muted" style="font-size:13px">No cover image uploaded yet.</p>
            <?php endif; ?>
            <form action="<?= BASE_URL ?>/settings/cover-image" method="POST" enctype="multipart/form-data">
                <?= $ctrl->csrfField() ?>
                <div class="mb-3">
                    <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.gif">
                    <div class="form-text">JPG, PNG or GIF — max 5 MB. Scaled to fit the page, aspect ratio preserved.</div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-upload me-1"></i>Upload Cover Image
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ══ Tab: Introduction ═════════════════════════════════════════════════════ -->
<div id="tab-introduction" class="tab-pane d-none">
    <div class="card" style="max-width:780px">
        <div class="card-header bg-white"><strong>Introduction Page</strong>
            <span class="text-muted ms-2" style="font-size:13px">Shown as page 2 of the generated PDF.</span>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>/settings" method="POST" novalidate>
                <?= $ctrl->csrfField() ?>
                <input type="hidden" name="pdf_show_intro" value="0">

                <div class="form-check form-switch mb-3 pb-3 border-bottom">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="toggle-intro" name="pdf_show_intro" value="1" <?= $showIntro ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="toggle-intro">Include introduction page in PDF</label>
                    <div class="form-text">Off by default. Saved with this form.</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Title / Community Full Name</label>
                        <input type="text" name="community_full_name" class="form-control" lang="gu"
                               value="<?= h($settings['community_full_name'] ?? '') ?>">
                        <div class="form-text">Appears as the heading on the introduction page (Gujarati).</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Edition Date Override</label>
                        <input type="text" name="edition_date_override" class="form-control"
                               placeholder="e.g. Jan 2025"
                               value="<?= h($settings['edition_date_override'] ?? '') ?>">
                        <div class="form-text">Blank = current month &amp; year.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Introduction Text</label>
                        <textarea name="about_text" class="form-control" rows="8" lang="gu"
                                  placeholder="Introduction / about the community (Gujarati)…"><?= h($settings['about_text'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2 me-1"></i>Save Introduction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ Tab: Committee ════════════════════════════════════════════════════════ -->
<div id="tab-committee" class="tab-pane d-none">
    <div class="card" style="max-width:780px">
        <div class="card-header bg-white"><strong>Committee Members</strong>
            <span class="text-muted ms-2" style="font-size:13px">Shown after the introduction page.</span>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>/settings" method="POST">
                <?= $ctrl->csrfField() ?>
                <input type="hidden" name="pdf_show_committee" value="0">

                <div class="form-check form-switch mb-3 pb-3 border-bottom">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="toggle-committee" name="pdf_show_committee" value="1" <?= $showCommittee ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="toggle-committee">Include committee page in PDF</label>
                    <div class="form-text">Off by default. Saved with this form.</div>
                </div>

                <div id="committee-rows">
                    <?php foreach ($committee as $row): ?>
                    <div class="committee-row d-flex gap-2 mb-2">
                        <input type="text" class="form-control" placeholder="Role" lang="gu"
                               value="<?= h($row['role'] ?? '') ?>" data-committee-role>
                        <input type="text" class="form-control" placeholder="Name" lang="gu"
                               value="<?= h($row['name'] ?? '') ?>" data-committee-name>
                        <button type="button" class="btn btn-outline-danger" data-remove-row>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($committee)): ?>
                    <div class="committee-row d-flex gap-2 mb-2">
                        <input type="text" class="form-control" placeholder="Role" lang="gu" data-committee-role>
                        <input type="text" class="form-control" placeholder="Name" lang="gu" data-committee-name>
                        <button type="button" class="btn btn-outline-danger" data-remove-row>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <button type="button" id="add-committee-row" class="btn btn-sm btn-outline-secondary mt-1">
                    <i class="bi bi-plus me-1"></i>Add Row
                </button>
                <input type="hidden" name="committee_json" id="committee-json">

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2 me-1"></i>Save Committee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ Tab: Relationships ════════════════════════════════════════════════════ -->
<div id="tab-relationships" class="tab-pane d-none">
    <div class="card mb-4" style="max-width:480px">
        <div class="card-header bg-white"><strong>Relationships</strong></div>
        <ul class="list-group list-group-flush">
            <?php foreach ($relationships as $r): ?>
            <li class="list-group-item d-flex align-items-center justify-content-between">
                <span>
                    <?= h($r['name']) ?>
                    <?php if (!$r['is_active']): ?>
                    <span class="badge bg-secondary ms-1">Inactive</span>
                    <?php endif; ?>
                </span>
                <?php if ($r['is_active']): ?>
                <form action="<?= BASE_URL ?>/settings/relationships/<?= (int)$r['relationship_id'] ?>/delete"
                      method="POST">
                    <?= $ctrl->csrfField() ?>
                    <button type="submit" class="btn btn-sm btn-outline-warning"
                            onclick="return confirm('Deactivate \'<?= h($r['name']) ?>\'?')">
                        <i class="bi bi-toggle-on me-1"></i>Deactivate
                    </button>
                </form>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="card-footer bg-white">
            <form action="<?= BASE_URL ?>/settings/relationships" method="POST" class="d-flex gap-2">
                <?= $ctrl->csrfField() ?>
                <input type="text" name="name" class="form-control" placeholder="New relationship name…" required>
                <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                    <i class="bi bi-plus me-1"></i>Add
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ══ Tab: Genders ══════════════════════════════════════════════════════════ -->
<div id="tab-genders" class="tab-pane d-none">
    <div class="card mb-4" style="max-width:480px">
        <div class="card-header bg-white"><strong>Genders</strong></div>
        <div class="card-body d-flex flex-wrap gap-2">
            <?php foreach ($genders as $g): ?>
            <div class="d-flex align-items-center gap-1">
                <span class="badge rounded-pill <?= $g['is_active'] ? 'bg-primary' : 'bg-secondary' ?> px-3 py-2" style="font-size:13px">
                    <?= h($g['name']) ?>
                </span>
                <?php if ($g['is_active']): ?>
                <form action="<?= BASE_URL ?>/settings/genders/<?= (int)$g['gender_id'] ?>/delete" method="POST">
                    <?= $ctrl->csrfField() ?>
                    <button type="submit" class="btn btn-xs btn-outline-secondary"
                            style="padding:1px 5px;font-size:11px"
                            onclick="return confirm('Deactivate \'<?= h($g['name']) ?>\'?')">
                        <i class="bi bi-x"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer bg-white">
            <form action="<?= BASE_URL ?>/settings/genders" method="POST" class="d-flex gap-2">
                <?= $ctrl->csrfField() ?>
                <input type="text" name="name" class="form-control" placeholder="New gender name…" required>
                <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                    <i class="bi bi-plus me-1"></i>Add
                </button>
            </form>
        </div>
    </div>
</div>
