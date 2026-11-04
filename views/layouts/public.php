<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Keep search engines / crawlers / AI bots out -->
    <meta name="robots" content="noindex, nofollow, noarchive, noimageindex, nosnippet">
    <meta name="referrer" content="no-referrer">
    <title><?= htmlspecialchars($pageTitle ?? 'Community Directory', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ── Senior-friendly, mobile-first ───────────────────────────────── */
        :root { --pub-blue:#2c6cb0; --pub-ink:#1b1b1b; }
        html, body { background:#eef2f6; color:var(--pub-ink); }
        body { font-size:19px; line-height:1.6; }
        .pub-wrap { max-width:640px; margin:0 auto; padding:16px; }
        .pub-card { background:#fff; border:1px solid #d7dee7; border-radius:12px;
                    padding:22px; margin-bottom:18px; box-shadow:0 1px 4px rgba(0,0,0,.05); }
        .pub-title { font-size:26px; font-weight:800; color:var(--pub-blue); margin:0; }
        .pub-sub { font-size:16px; color:#5a6775; }
        .form-label { font-size:18px; font-weight:700; margin-bottom:6px; }
        .form-control, .form-select { font-size:20px; padding:12px 14px; border-radius:10px;
                    border:2px solid #c3ccd7; }
        .form-control:focus, .form-select:focus { border-color:var(--pub-blue);
                    box-shadow:0 0 0 .2rem rgba(44,108,176,.2); }
        .btn { font-size:19px; padding:12px 20px; border-radius:10px; font-weight:700; }
        .btn-lg-block { width:100%; }
        .pub-captcha-img { height:80px; border:2px solid #c3ccd7; border-radius:10px; background:#fff; }
        .pub-field-note { font-size:15px; color:#6b7684; }
        .pub-result { border:1px solid #d7dee7; border-radius:10px; padding:14px 16px;
                    margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; gap:12px; }
        .pub-result:hover { border-color:var(--pub-blue); }
        /* Let the name wrap without pushing the button; keep the button one fixed size */
        .pub-result > .pub-result-info { flex:1 1 auto; min-width:0; }
        .pub-result > .pub-result-info > div { word-break:break-word; }
        .pub-result .pub-result-btn { flex:0 0 auto; align-self:center; white-space:nowrap;
                    min-width:104px; padding:10px 16px; display:inline-flex;
                    align-items:center; justify-content:center; gap:4px; }
        .pub-member { border:1px solid #d7dee7; border-radius:10px; padding:14px 16px; margin-bottom:14px; background:#fbfcfe; }
        .pub-member:last-child { margin-bottom:0; }
        .pub-badge-head { background:#2c6cb0; color:#fff; font-size:13px; padding:3px 8px; border-radius:6px; }
        /* Label/value detail grid */
        .pub-details { display:grid; grid-template-columns:130px 1fr; row-gap:6px; column-gap:12px;
                    font-size:17px; margin:0; }
        .pub-details dt { font-weight:700; color:#5a6775; }
        .pub-details dd { margin:0; word-break:break-word; }
        a { color:var(--pub-blue); }
        .pub-err { color:#b02a37; font-size:16px; font-weight:600; }
        .invalid-feedback.d-block { font-size:15px; }
    </style>
</head>
<body>
<div class="pub-wrap">

    <!-- Header -->
    <div class="text-center mb-3 pt-2">
        <h1 class="pub-title">SDSVG Community Directory</h1>
        <div class="pub-sub">Find your family and request corrections</div>
    </div>

    <?= $content ?>

    <div class="text-center pub-sub mt-3 mb-4">
        Changes you submit are reviewed by the committee before they appear.
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Reload the captcha image on demand (cache-busting)
    function reloadCaptcha(el, purpose) {
        var img = document.getElementById('captcha-img');
        if (img) img.src = img.src.split('?')[0] + '?for=' + purpose + '&t=' + Date.now();
        return false;
    }
</script>
</body>
</html>
