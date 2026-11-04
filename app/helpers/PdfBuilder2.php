<?php
/**
 * PdfBuilder2 — SDSVG Community Directory PDF (Card / Block Style)
 *
 * Layout  : A4 Landscape
 * Design  : One card per family
 *           – Family number badge (B-001, B-002 …) top-right of card
 *           – Orange-brown table header row
 *           – Member rows (primary member name bold)
 *           – Full-width address row at the bottom of each card
 * Packing : Max 3 families per page.
 *           If a family's block does not fit in the remaining space it is
 *           always started on a fresh page (never split mid-family).
 *
 * Requires TCPDF ≥ 6.7 at lib/tcpdf/tcpdf.php
 */
class PdfBuilder2
{
    // ── Dimensions (mm) ───────────────────────────────────────────────────────
    private const MARGIN     = 10;
    private const MARGIN_TOP = 10;   // header removed — content starts at top margin
    private const MARGIN_BOT = 10;   // footer removed — only a small bottom gutter
    private const PAGE_H     = 210;  // A4 landscape height
    private const USABLE_W   = 277;  // 297 − 2×10

    // Column widths — must sum to USABLE_W (277 mm)
    private const COLS = [
        'no'        => 10,
        'name'      => 65,
        'gender'    => 18,
        'relation'  => 28,
        'dob'       => 22,
        'education' => 32,
        'mobile'    => 38,
        'email'     => 64,
    ]; // 10+65+18+28+22+32+38+64 = 277 ✓

    // ── Typography ────────────────────────────────────────────────────────────
    private const FONT     = 'helvetica';
    // Gujarati Unicode font (Noto Sans Gujarati), installed into lib/tcpdf/fonts/
    // via: php lib/tcpdf/tools/tcpdf_addfont.php -b -t TrueTypeUnicode -i NotoSansGujarati-*.ttf
    private const GFONT    = 'notosansgujarati';   // regular
    private const GFONT_B  = 'notosansgujaratib';  // bold
    private const FONT_SZ  = 8.5;  // pt — body
    private const LINE_H   = 4.8;  // mm — one line height in MultiCell
    private const CELL_PAD = 1.0;  // mm — horizontal inner padding per side
    private const TOP_PAD  = 0.8;  // mm — vertical inner padding from top
    private const HDR_H    = 7.5;  // mm — table header row height
    private const ROW_MIN  = 6.5;  // mm — minimum data-row height
    private const ADDR_MIN = 7.0;  // mm — minimum address row height
    private const NUM_H    = 6.5;  // mm — family-number bar height
    private const FAM_GAP  = 5.0;  // mm — vertical gap between family blocks

    // ── Colours [R, G, B] ─────────────────────────────────────────────────────
    private const C_HDR_FILL  = [180,  90,  25];  // brownish-orange header fill
    private const C_HDR_TEXT  = [255, 255, 255];  // header text — white
    private const C_NUM_FILL  = [245, 245, 245];  // family-number bar fill
    private const C_NUM_TEXT  = [ 60,  60,  60];  // family-number bar text
    private const C_ADDR_FILL = [242, 242, 242];  // address row fill
    private const C_BLOCK_BDR = [130, 130, 130];  // outer block border
    private const C_GRID      = [190, 190, 190];  // inner grid lines
    private const C_WHITE     = [255, 255, 255];
    private const C_BLACK     = [  0,   0,   0];

    // ═════════════════════════════════════════════════════════════════════════
    //  Public entry-point
    // ═════════════════════════════════════════════════════════════════════════

    public function build(): void
    {
        $tcpdfPath = __DIR__ . '/../../lib/tcpdf/tcpdf.php';
        if (!file_exists($tcpdfPath)) {
            throw new RuntimeException('TCPDF not found at lib/tcpdf/tcpdf.php');
        }

        // TCPDF 6.7 declares a class-constant array referencing CURLOPT_* / CURLPROTO_*
        // constants, so it fatally fails to load when the PHP cURL extension is
        // disabled (common on WAMP Apache). We never fetch remote assets — all
        // images are local files — so define harmless placeholders when cURL is
        // absent to let TCPDF load. (Best fix is still to enable php_curl.)
        if (!extension_loaded('curl')) {
            $curlConstants = [
                'CURLOPT_CONNECTTIMEOUT' => 5,
                'CURLOPT_MAXREDIRS'      => 5,
                'CURLOPT_PROTOCOLS'      => 0,
                'CURLPROTO_HTTP'         => 1,
                'CURLPROTO_HTTPS'        => 2,
                'CURLPROTO_FTP'          => 4,
                'CURLPROTO_FTPS'         => 8,
                'CURLOPT_SSL_VERIFYHOST' => 81,
                'CURLOPT_SSL_VERIFYPEER' => 64,
                'CURLOPT_TIMEOUT'        => 13,
                'CURLOPT_USERAGENT'      => 10018,
                'CURLOPT_FAILONERROR'    => 45,
                'CURLOPT_RETURNTRANSFER' => 19913,
            ];
            foreach ($curlConstants as $name => $value) {
                if (!defined($name)) {
                    define($name, $value);
                }
            }
        }

        require_once $tcpdfPath;

        // ── Load data ─────────────────────────────────────────────────────────
        $memberModel  = new MemberModel();
        $settingModel = new SettingModel();
        $allMembers   = $memberModel->getAllForPdf();
        $settings     = $settingModel->getAll();

        $communityName = $settings['community_full_name']   ?? 'SDSVG';
        $editionDate   = ($settings['edition_date_override'] ?? '') ?: date('M Y');

        // Group members by family (order preserved from query)
        $families = [];
        foreach ($allMembers as $m) {
            $families[$m['group_id']][] = $m;
        }

        // ── TCPDF setup ───────────────────────────────────────────────────────
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('SDSVG Admin Panel');
        $pdf->SetAuthor($communityName);
        $pdf->SetTitle($communityName . ' Community Directory');

        // Disable both the built-in header and footer (no page numbers)
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetMargins(self::MARGIN, self::MARGIN_TOP, self::MARGIN);
        $pdf->SetAutoPageBreak(false, self::MARGIN_BOT);
        $pdf->setCellPaddings(0, 0, 0, 0);

        // ── Front matter: cover image, introduction, committee ──────────────────
        // Each page is gated by its own admin toggle (default OFF/hidden) AND by
        // the presence of its data. Toggles: pdf_show_cover / pdf_show_intro /
        // pdf_show_committee — stored '1' = show, anything else = hidden.
        $showCover     = ($settings['pdf_show_cover']     ?? '0') === '1';
        $showIntro     = ($settings['pdf_show_intro']     ?? '0') === '1';
        $showCommittee = ($settings['pdf_show_committee'] ?? '0') === '1';

        // 1. Cover image → first page
        $coverRel = trim($settings['cover_image_path'] ?? '');
        $coverAbs = $coverRel !== '' ? __DIR__ . '/../../public/' . $coverRel : '';
        if ($showCover && $coverAbs !== '' && is_file($coverAbs)) {
            $this->drawCoverPage($pdf, $coverAbs);
        }

        // 2. Introduction → second page (title + text, Gujarati)
        $introTitle = trim($communityName);
        $introText  = trim($settings['about_text'] ?? '');
        if ($showIntro && ($introTitle !== '' || $introText !== '')) {
            $this->drawIntroPage($pdf, $introTitle, $introText);
        }

        // 3. Committee members
        $committee = [];
        $committeeJson = trim($settings['committee_text'] ?? '');
        if ($committeeJson !== '') {
            $decoded = json_decode($committeeJson, true);
            if (is_array($decoded)) {
                $committee = array_values(array_filter(
                    $decoded,
                    fn($r) => trim(($r['role'] ?? '') . ($r['name'] ?? '')) !== ''
                ));
            }
        }
        if ($showCommittee && !empty($committee)) {
            $this->drawCommitteePage($pdf, $committee);
        }

        // ── Directory pages ───────────────────────────────────────────────────
        $pdf->AddPage();

        $pageBottomY    = self::PAGE_H - self::MARGIN_BOT;
        $currentY       = (float)self::MARGIN_TOP;
        $familiesOnPage = 0;
        $letterCounters = [];   // tracks per-letter family number, e.g. ['B'=>2,'D'=>1]

        foreach ($families as $members) {

            // Identify head member
            $head = null;
            foreach ($members as $m) {
                if ($m['type'] === 'P') { $head = $m; break; }
            }
            if ($head === null) $head = $members[0];

            $address     = trim($head['address'] ?? '');
            $commAddress = trim($head['communication_address'] ?? '');

            // Derive badge label from surname initial (B-001, D-001 …)
            $letter = strtoupper(mb_substr(trim($head['last_name']), 0, 1)) ?: 'X';
            $letterCounters[$letter] = ($letterCounters[$letter] ?? 0) + 1;
            $badgeLabel = $letter . '-' . str_pad($letterCounters[$letter], 3, '0', STR_PAD_LEFT);

            // ── Pre-calculate heights ──────────────────────────────────────────
            $pdf->SetFont(self::FONT, '', self::FONT_SZ);
            $rowHeights = [];
            foreach ($members as $idx => $m) {
                $rowHeights[$idx] = $this->calcRowHeight($pdf, $m);
            }
            $addrH     = $address     !== '' ? $this->calcAddrHeight($pdf, 'Address: ',       $address)     : 0.0;
            $commAddrH = $commAddress !== '' ? $this->calcAddrHeight($pdf, 'Comm. Address: ', $commAddress) : 0.0;
            // If both empty, reserve one placeholder row
            $addrSectionH = ($addrH + $commAddrH > 0) ? ($addrH + $commAddrH) : self::ADDR_MIN;
            $blockH       = self::NUM_H + self::HDR_H + array_sum($rowHeights) + $addrSectionH;

            // ── Page-break logic ──────────────────────────────────────────────
            // Start a new page if:
            //   (a) already 3 families on this page, OR
            //   (b) the block doesn't fit in remaining space (and at least one
            //       family has already been drawn — avoid infinite loop)
            $needsNewPage = ($familiesOnPage >= 3)
                         || ($familiesOnPage > 0 && $currentY + $blockH > $pageBottomY);

            if ($needsNewPage) {
                $pdf->AddPage();
                $currentY       = (float)self::MARGIN_TOP;
                $familiesOnPage = 0;
            }

            // ── Draw the family block ─────────────────────────────────────────
            $this->drawFamilyBlock(
                $pdf, $currentY, $members, $address, $commAddress,
                $rowHeights, $addrH, $commAddrH, $addrSectionH, $badgeLabel
            );

            $currentY       += $blockH + self::FAM_GAP;
            $familiesOnPage++;
        }

        $pdf->Output('SDSVG_Directory2.pdf', 'D');
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  Front matter (cover / introduction / committee)
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Page 1 — cover image, scaled to fit the page while preserving aspect ratio
     * and centred.
     */
    private function drawCoverPage(TCPDF $pdf, string $imgAbsPath): void
    {
        $pdf->AddPage();

        $usableW = 297 - 2 * self::MARGIN;  // landscape width  − margins
        $usableH = self::PAGE_H - 2 * self::MARGIN;

        $size = @getimagesize($imgAbsPath);
        if ($size === false || $size[0] <= 0 || $size[1] <= 0) {
            return; // not a usable image
        }
        $ratio    = $size[0] / $size[1];
        $boxRatio = $usableW / $usableH;

        if ($ratio > $boxRatio) {
            $dw = $usableW;
            $dh = $usableW / $ratio;
        } else {
            $dh = $usableH;
            $dw = $usableH * $ratio;
        }
        $x = (297 - $dw) / 2;
        $y = (self::PAGE_H - $dh) / 2;

        $pdf->Image($imgAbsPath, $x, $y, $dw, $dh, '', '', '', true, 300);
    }

    /**
     * Page 2 — introduction: title (Community Full Name) + body text, both
     * rendered with the Gujarati Unicode font.
     */
    private function drawIntroPage(TCPDF $pdf, string $title, string $text): void
    {
        $pdf->AddPage();
        $usableW = 297 - 2 * self::MARGIN;
        $pdf->SetTextColor(...self::C_BLACK);

        $pdf->SetY(28);
        if ($title !== '') {
            $pdf->SetFont(self::GFONT_B, '', 22);
            $pdf->SetX(self::MARGIN);
            $pdf->MultiCell($usableW, 12, $title, 0, 'C', false, 1);
            $pdf->Ln(8);
        }
        if ($text !== '') {
            $pdf->SetFont(self::GFONT, '', 13);
            $pdf->SetX(self::MARGIN);
            $pdf->MultiCell($usableW, 8, $text, 0, 'L', false, 1);
        }
    }

    /**
     * Committee members page — heading + role/name table (Gujarati font).
     *
     * @param array $committee List of ['role' => string, 'name' => string]
     */
    private function drawCommitteePage(TCPDF $pdf, array $committee): void
    {
        $pdf->AddPage();
        $usableW = 297 - 2 * self::MARGIN;
        $pdf->SetTextColor(...self::C_BLACK);

        // Heading (English — committee content is English)
        $pdf->SetY(22);
        $pdf->SetX(self::MARGIN);
        $pdf->SetFont(self::FONT, 'B', 18);
        $pdf->MultiCell($usableW, 10, 'Committee Members', 0, 'C', false, 1);
        $pdf->Ln(6);

        // Table — Role | Name
        $roleW = $usableW * 0.40;
        $nameW = $usableW * 0.60;
        $rowH  = 9.0;

        foreach ($committee as $row) {
            $role = trim($row['role'] ?? '');
            $name = trim($row['name'] ?? '');

            $y = $pdf->GetY();
            if ($y + $rowH > self::PAGE_H - self::MARGIN_BOT) {
                $pdf->AddPage();
                $pdf->SetY(self::MARGIN_TOP);
            }

            $pdf->SetX(self::MARGIN);
            $pdf->SetFont(self::FONT, 'B', 12);
            $pdf->Cell($roleW, $rowH, $role, 1, 0, 'L');
            $pdf->SetFont(self::FONT, '', 12);
            $pdf->Cell($nameW, $rowH, $name, 1, 1, 'L');
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  Drawing
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Draw one complete family card at $startY.
     */
    private function drawFamilyBlock(
        TCPDF  $pdf,
        float  $startY,
        array  $members,
        string $address,
        string $commAddress,   // communication address (may be empty)
        array  $rowHeights,
        float  $addrH,         // 0 when home address is empty
        float  $commAddrH,     // 0 when comm address is empty
        float  $addrSectionH,  // total address section height (≥ ADDR_MIN)
        string $badgeLabel     // e.g. "B-001", "D-003"
    ): void {
        $x        = self::MARGIN;
        $w        = self::USABLE_W;
        $totalH   = self::NUM_H + self::HDR_H + array_sum($rowHeights) + $addrSectionH;

        // ── 1. Family-number bar ──────────────────────────────────────────────
        $pdf->SetFillColor(...self::C_NUM_FILL);
        $pdf->Rect($x, $startY, $w, self::NUM_H, 'F');
        $pdf->SetFont(self::FONT, 'B', self::FONT_SZ + 1.5);
        $pdf->SetTextColor(...self::C_NUM_TEXT);
        $pdf->SetXY($x + 2, $startY + 0.8);
        $pdf->Cell($w - 4, self::NUM_H - 1.6, $badgeLabel, 0, 0, 'R');

        $tableY = $startY + self::NUM_H;

        // ── 2. Table header row ───────────────────────────────────────────────
        $labels = [
            'no'        => 'No.',
            'name'      => 'Name',
            'gender'    => 'Gender',
            'relation'  => 'Relation',
            'dob'       => 'DOB',
            'education' => 'Education',
            'mobile'    => 'Mobile',
            'email'     => 'Email',
        ];

        $hx = $x;
        foreach ($labels as $key => $label) {
            $cw = self::COLS[$key];
            $pdf->SetFillColor(...self::C_HDR_FILL);
            $pdf->Rect($hx, $tableY, $cw, self::HDR_H, 'F');
            $pdf->SetFont(self::FONT, 'B', self::FONT_SZ - 0.5);
            $pdf->SetTextColor(...self::C_HDR_TEXT);
            $pdf->SetXY($hx + self::CELL_PAD, $tableY + self::TOP_PAD);
            $pdf->MultiCell(
                $cw - 2 * self::CELL_PAD,
                self::LINE_H, $label,
                0, 'C', false, 1, '', '', true, 0, false, true,
                self::HDR_H - self::TOP_PAD, 'T'
            );
            $this->drawBorder($pdf, $hx, $tableY, $cw, self::HDR_H, '1');
            $hx += $cw;
        }

        // ── 3. Member rows ────────────────────────────────────────────────────
        $rowY   = $tableY + self::HDR_H;
        $rowNum = 1;

        foreach ($members as $idx => $m) {
            $rh        = $rowHeights[$idx];
            $isPrimary = ($m['type'] === 'P');
            $style     = $isPrimary ? 'B' : '';
            $rx        = $x;

            // No.
            $this->tCell($pdf, $rx, $rowY, self::COLS['no'], $rh,
                (string)$rowNum, 'C',
                self::C_WHITE, $style, self::C_BLACK, '1');
            $rx += self::COLS['no'];

            // Name
            $this->tCell($pdf, $rx, $rowY, self::COLS['name'], $rh,
                $this->formatName($m), 'L',
                self::C_WHITE, $style, self::C_BLACK, '1');
            $rx += self::COLS['name'];

            // Gender
            $this->tCell($pdf, $rx, $rowY, self::COLS['gender'], $rh,
                $m['gender_name'] ?? '', 'C',
                self::C_WHITE, '', self::C_BLACK, '1');
            $rx += self::COLS['gender'];

            // Relation
            $this->tCell($pdf, $rx, $rowY, self::COLS['relation'], $rh,
                $m['relationship_name'] ?? '', 'L',
                self::C_WHITE, '', self::C_BLACK, '1');
            $rx += self::COLS['relation'];

            // DOB
            $this->tCell($pdf, $rx, $rowY, self::COLS['dob'], $rh,
                self::formatDob($m['dob'] ?? null), 'C',
                self::C_WHITE, '', self::C_BLACK, '1');
            $rx += self::COLS['dob'];

            // Education
            $this->tCell($pdf, $rx, $rowY, self::COLS['education'], $rh,
                $m['education'] ?? '', 'L',
                self::C_WHITE, '', self::C_BLACK, '1');
            $rx += self::COLS['education'];

            // Mobile
            $this->tCell($pdf, $rx, $rowY, self::COLS['mobile'], $rh,
                $m['mobile'] ?? '', 'L',
                self::C_WHITE, '', self::C_BLACK, '1');
            $rx += self::COLS['mobile'];

            // Email
            $this->tCell($pdf, $rx, $rowY, self::COLS['email'], $rh,
                $m['email'] ?? '', 'L',
                self::C_WHITE, '', self::C_BLACK, '1');

            $rowY  += $rh;
            $rowNum++;
        }

        // ── 4. Home address row (only if address is set) ─────────────────────
        if ($address !== '' && $addrH > 0.0) {
            $pdf->SetFillColor(...self::C_ADDR_FILL);
            $pdf->Rect($x, $rowY, $w, $addrH, 'F');
            $pdf->SetFont(self::FONT, '', self::FONT_SZ - 0.5);
            $pdf->SetTextColor(...self::C_BLACK);
            $pdf->SetXY($x + self::CELL_PAD, $rowY + self::TOP_PAD);
            $pdf->MultiCell(
                $w - 2 * self::CELL_PAD, self::LINE_H,
                'Address: ' . $address,
                0, 'L', false, 1, '', '', true, 0, false, true,
                $addrH - self::TOP_PAD, 'T'
            );
            $this->drawBorder($pdf, $x, $rowY, $w, $addrH, '1');
            $rowY += $addrH;
        }

        // ── 5. Communication address row (only if comm address is set) ────────
        if ($commAddress !== '' && $commAddrH > 0.0) {
            $pdf->SetFillColor(...self::C_ADDR_FILL);
            $pdf->Rect($x, $rowY, $w, $commAddrH, 'F');
            $pdf->SetFont(self::FONT, '', self::FONT_SZ - 0.5);
            $pdf->SetTextColor(...self::C_BLACK);
            $pdf->SetXY($x + self::CELL_PAD, $rowY + self::TOP_PAD);
            $pdf->MultiCell(
                $w - 2 * self::CELL_PAD, self::LINE_H,
                'Comm. Address: ' . $commAddress,
                0, 'L', false, 1, '', '', true, 0, false, true,
                $commAddrH - self::TOP_PAD, 'T'
            );
            $this->drawBorder($pdf, $x, $rowY, $w, $commAddrH, '1');
            $rowY += $commAddrH;
        }

        // ── 5b. Placeholder row when both addresses are empty ─────────────────
        if ($address === '' && $commAddress === '') {
            $ph = $addrSectionH; // already ≥ ADDR_MIN
            $pdf->SetFillColor(...self::C_ADDR_FILL);
            $pdf->Rect($x, $rowY, $w, $ph, 'F');
            $pdf->SetFont(self::FONT, '', self::FONT_SZ - 0.5);
            $pdf->SetTextColor(160, 160, 160);
            $pdf->SetXY($x + self::CELL_PAD, $rowY + self::TOP_PAD);
            $pdf->MultiCell(
                $w - 2 * self::CELL_PAD, self::LINE_H, 'Address: —',
                0, 'L', false, 1, '', '', true, 0, false, true,
                $ph - self::TOP_PAD, 'T'
            );
            $this->drawBorder($pdf, $x, $rowY, $w, $ph, '1');
        }

        // ── 6. Outer block border (drawn last to overlay inner grid) ──────────
        $pdf->SetDrawColor(...self::C_BLOCK_BDR);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($x, $startY, $w, $totalH, 'D');
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  Height calculators
    // ═════════════════════════════════════════════════════════════════════════

    private function calcRowHeight(TCPDF $pdf, array $m): float
    {
        $pdf->SetFont(self::FONT, '', self::FONT_SZ);
        $textW = fn(string $col): float => self::COLS[$col] - 2 * self::CELL_PAD;

        $fields = [
            'name'      => $this->formatName($m),
            'gender'    => $m['gender_name']        ?? '',
            'relation'  => $m['relationship_name']  ?? '',
            'dob'       => self::formatDob($m['dob'] ?? null),
            'education' => $m['education']           ?? '',
            'mobile'    => $m['mobile']              ?? '',
            'email'     => $m['email']               ?? '',
        ];

        $maxLines = 1;
        foreach ($fields as $col => $text) {
            if ($text === '') continue;
            $maxLines = max($maxLines, $pdf->getNumLines($text, $textW($col)));
        }

        return max(self::ROW_MIN, $maxLines * self::LINE_H + 2.0);
    }

    private function calcAddrHeight(TCPDF $pdf, string $label, string $text): float
    {
        if ($text === '') return 0.0;

        $pdf->SetFont(self::FONT, '', self::FONT_SZ - 0.5);
        $textW = self::USABLE_W - 2 * self::CELL_PAD;
        $lines = $pdf->getNumLines($label . $text, $textW);

        return max(self::ADDR_MIN, $lines * self::LINE_H + 2.0);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  Cell drawing helpers (same pattern as PdfBuilder)
    // ═════════════════════════════════════════════════════════════════════════

    private function tCell(
        TCPDF  $pdf,
        float  $x,
        float  $y,
        float  $w,
        float  $h,
        string $text,
        string $align,
        array  $fillColor,
        string $fontStyle,
        array  $textColor,
        string $border
    ): void {
        // 1. Background fill
        $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
        $pdf->Rect($x, $y, $w, $h, 'F');

        // 2. Text
        if (trim($text) !== '') {
            $pdf->SetFont(self::FONT, $fontStyle, self::FONT_SZ);
            $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
            $pdf->SetXY($x + self::CELL_PAD, $y + self::TOP_PAD);
            $pdf->MultiCell(
                $w - 2 * self::CELL_PAD, self::LINE_H, $text,
                0, $align, false, 1, '', '', true, 0, false, true,
                $h - self::TOP_PAD, 'T'
            );
        }

        // 3. Border
        $this->drawBorder($pdf, $x, $y, $w, $h, $border);
    }

    private function drawBorder(
        TCPDF  $pdf,
        float  $x,
        float  $y,
        float  $w,
        float  $h,
        string $border
    ): void {
        if ($border === '' || $border === '0') return;

        $pdf->SetDrawColor(...self::C_GRID);
        $pdf->SetLineWidth(0.1);

        if ($border === '1') {
            $pdf->Rect($x, $y, $w, $h, 'D');
            return;
        }

        if (str_contains($border, 'L')) $pdf->Line($x,       $y,       $x,       $y + $h);
        if (str_contains($border, 'R')) $pdf->Line($x + $w,  $y,       $x + $w,  $y + $h);
        if (str_contains($border, 'T')) $pdf->Line($x,       $y,       $x + $w,  $y);
        if (str_contains($border, 'B')) $pdf->Line($x,       $y + $h,  $x + $w,  $y + $h);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  Formatters
    // ═════════════════════════════════════════════════════════════════════════

    private function formatName(array $m): string
    {
        $parts = [];
        if (!empty($m['last_name']))   $parts[] = trim($m['last_name']);
        if (!empty($m['title']))       $parts[] = trim($m['title']);
        if (!empty($m['first_name'])) $parts[] = trim($m['first_name']);
        if (!empty($m['middle_name'])) $parts[] = trim($m['middle_name']);
        return implode(' ', $parts);
    }

    private static function formatDob(?string $dob): string
    {
        if (!$dob) return '';
        $dt = DateTime::createFromFormat('Y-m-d', $dob);
        return $dt ? $dt->format('j-M-Y') : $dob;
    }
}
