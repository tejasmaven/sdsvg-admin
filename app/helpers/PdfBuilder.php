<?php
/**
 * PdfBuilder — SDSVG Community Directory PDF
 *
 * Layout  : A4 Landscape, 10 mm margins → 277 mm usable width
 * Columns : No. | Name | Gender | Relation | DOB | Education | Mobile | Email | Address
 * Name    : Last Name  Title  First Name  Middle Name
 * Primary : light-blue fill, dark-blue bold text, sequence number shown
 * Secondary: white fill, normal text, no sequence number
 * Address : spans all rows of a family (rowspan effect via manual Rect + Line drawing)
 * Header  : repeats on every page
 * Dividers: grey letter-group bar between surname initials
 *
 * Requires TCPDF ≥ 6.7 at lib/tcpdf/tcpdf.php
 */
class PdfBuilder
{
    // ── Dimensions (mm) ───────────────────────────────────────────────────────
    private const MARGIN     = 10;    // left / right
    private const MARGIN_TOP = 22;    // space for TCPDF page header
    private const MARGIN_BOT = 18;    // space for TCPDF page footer

    // Landscape A4 usable width = 297 − 2×10 = 277 mm
    private const COLS = [
        'no'        => 10,
        'name'      => 50,
        'gender'    => 16,
        'relation'  => 26,
        'dob'       => 18,
        'education' => 30,
        'mobile'    => 28,
        'email'     => 34,
        'address'   => 65,
    ]; // 10+50+16+26+18+30+28+34+65 = 277

    // ── Typography ────────────────────────────────────────────────────────────
    private const FONT     = 'helvetica';
    private const FONT_SZ  = 10.5;  // pt  — data rows
    private const LINE_H   = 5.8;   // mm  — height of one wrapped line in MultiCell
    private const CELL_PAD = 1.0;   // mm  — horizontal inner padding each side
    private const TOP_PAD  = 0.5;   // mm  — vertical inner padding from top
    private const HDR_H    = 8.0;   // mm  — table header row height
    private const ROW_MIN  = 7.5;   // mm  — minimum data-row height
    private const DIV_H    = 6.0;   // mm  — alphabet-divider row height

    // ── Colours [R, G, B] ─────────────────────────────────────────────────────
    private const C_HDR_FILL = [220, 220, 220];   // table header grey
    private const C_PRI_FILL = [235, 243, 252];   // head-row light blue
    private const C_PRI_TEXT = [ 31,  78, 121];   // head-row dark blue text
    private const C_DIV_FILL = [200, 200, 200];   // divider grey
    private const C_WHITE    = [255, 255, 255];
    private const C_BLACK    = [  0,   0,   0];
    private const C_LINE     = [160, 160, 160];   // grid line colour

    // ═════════════════════════════════════════════════════════════════════════
    //  Public entry-point
    // ═════════════════════════════════════════════════════════════════════════

    public function build(): void
    {
        $tcpdfPath = __DIR__ . '/../../lib/tcpdf/tcpdf.php';
        if (!file_exists($tcpdfPath)) {
            throw new RuntimeException('TCPDF not found at lib/tcpdf/tcpdf.php');
        }
        require_once $tcpdfPath;

        // ── Load data ─────────────────────────────────────────────────────────
        $memberModel  = new MemberModel();
        $settingModel = new SettingModel();
        $allMembers   = $memberModel->getAllForPdf();
        $settings     = $settingModel->getAll();

        $communityName  = $settings['community_full_name']   ?? 'SDSVG';
        $aboutText      = $settings['about_text']            ?? '';
        $committeeText  = $settings['committee_text']        ?? '';
        $coverImgPath   = $settings['cover_image_path']      ?? '';
        $editionDate    = ($settings['edition_date_override'] ?? '') ?: date('M Y');

        // Group members by group_id (order preserved from getAllForPdf query)
        $families = [];
        foreach ($allMembers as $m) {
            $families[$m['group_id']][] = $m;
        }

        // ── TCPDF ─────────────────────────────────────────────────────────────
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false); // L = Landscape
        $pdf->SetCreator('SDSVG Admin Panel');
        $pdf->SetAuthor($communityName);
        $pdf->SetTitle($communityName . ' Community Directory');

        $pdf->SetHeaderData('', 0,
            $communityName . ' — Community Directory',
            'Edition: ' . $editionDate);
        $pdf->setHeaderFont([self::FONT, 'B', 9]);
        $pdf->setFooterFont([self::FONT, '',  8]);

        $pdf->SetMargins(self::MARGIN, self::MARGIN_TOP, self::MARGIN);
        $pdf->SetHeaderMargin(6);
        $pdf->SetFooterMargin(8);
        $pdf->SetAutoPageBreak(false, self::MARGIN_BOT); // manual page breaks
        $pdf->setCellPaddings(0, 0, 0, 0); // we handle padding ourselves

        // ── Cover ─────────────────────────────────────────────────────────────
        $pdf->AddPage();
        $pdf->SetFont(self::FONT, 'B', 28);
        $pdf->Ln(20);
        $pdf->Cell(0, 14, $communityName, 0, 1, 'C');
        $pdf->SetFont(self::FONT, '', 16);
        $pdf->Cell(0, 10, 'Community Directory', 0, 1, 'C');
        $pdf->SetFont(self::FONT, '', 12);
        $pdf->Cell(0, 8, 'Edition: ' . $editionDate, 0, 1, 'C');
        if ($coverImgPath) {
            $abs = __DIR__ . '/../../public/' . ltrim($coverImgPath, '/');
            if (file_exists($abs)) {
                $pdf->Ln(10);
                $pdf->Image($abs, 80, null, 130, 0, '', '', '', false, 150);
            }
        }

        // ── About ─────────────────────────────────────────────────────────────
        $pdf->AddPage();
        $pdf->SetFont(self::FONT, 'B', 14);
        $pdf->Cell(0, 10, 'About ' . $communityName, 0, 1);
        $pdf->SetFont(self::FONT, '', 11);
        $pdf->MultiCell(0, 6, trim($aboutText) ?: '(No about text provided)', 0, 'L');

        // ── Committee ─────────────────────────────────────────────────────────
        $pdf->AddPage();
        $pdf->SetFont(self::FONT, 'B', 14);
        $pdf->Cell(0, 10, 'Committee Members', 0, 1);
        if (trim($committeeText)) {
            $rows = @json_decode($committeeText, true);
            if (is_array($rows) && $rows) {
                $pdf->SetFont(self::FONT, 'B', 10);
                $pdf->SetFillColor(...self::C_HDR_FILL);
                $pdf->Cell(110, 8, 'Post', 1, 0, 'L', true);
                $pdf->Cell(110, 8, 'Name', 1, 1, 'L', true);
                $pdf->SetFont(self::FONT, '', 10);
                foreach ($rows as $r) {
                    $pdf->Cell(110, 7, $r['role'] ?? '', 1, 0);
                    $pdf->Cell(110, 7, $r['name'] ?? '', 1, 1);
                }
            } else {
                $pdf->SetFont(self::FONT, '', 10);
                $pdf->MultiCell(0, 6, $committeeText, 0, 'L');
            }
        } else {
            $pdf->SetFont(self::FONT, '', 10);
            $pdf->Cell(0, 8, '(No committee information provided)', 0, 1);
        }

        // ── TOC ───────────────────────────────────────────────────────────────
        $pdf->addTOCPage();
        $pdf->SetFont(self::FONT, 'B', 14);
        $pdf->Cell(0, 10, 'Table of Contents', 0, 1, 'C');
        $pdf->addTOC(4, self::FONT, '.', 'INDEX', 'B');
        $pdf->endTOCPage();

        // ── Directory body ────────────────────────────────────────────────────
        $pdf->AddPage();
        $topY     = $pdf->GetY();
        $this->drawTableHeader($pdf, $topY);
        $currentY = $topY + self::HDR_H;

        $pageH       = $pdf->getPageHeight();
        $pageBottomY = $pageH - self::MARGIN_BOT;
        // Y where rows begin on a fresh page (after header is drawn)
        $newPageTopY = (float)(self::MARGIN_TOP + self::HDR_H);
        $totalW      = array_sum(self::COLS);
        $addrX       = self::MARGIN + $totalW - self::COLS['address'];
        $seqNum      = 1;
        $curLetter   = '';

        foreach ($families as $members) {

            // Identify head member
            $head = null;
            foreach ($members as $m) {
                if ($m['type'] === 'P') { $head = $m; break; }
            }
            if ($head === null) $head = $members[0];

            $letter  = strtoupper(mb_substr(trim($head['last_name']), 0, 1));
            $address = trim($head['address'] ?? '');

            // Pre-calculate every member's row height
            $rowHeights = [];
            foreach ($members as $idx => $m) {
                $rowHeights[$idx] = $this->calcRowHeight($pdf, $m);
            }

            $divH = ($letter !== $curLetter) ? self::DIV_H : 0.0;

            // If not even the first row fits (with divider), pre-emptively break
            $firstIdx = array_key_first($members);
            if ($currentY + $divH + $rowHeights[$firstIdx] > $pageBottomY) {
                $pdf->AddPage();
                $topY     = $pdf->GetY();
                $this->drawTableHeader($pdf, $topY);
                $currentY = $topY + self::HDR_H;
                $divH     = ($letter !== $curLetter) ? self::DIV_H : 0.0;
            }

            // ── Split member rows into per-page segments ──────────────────────
            // Each segment is a list of $idx values whose total height fits on
            // the available space of one page.
            $segments   = [];
            $currentSeg = [];
            $simY       = $currentY + $divH;   // simulate Y as rows are assigned

            foreach ($members as $idx => $m) {
                $rh = $rowHeights[$idx];

                if ($simY + $rh > $pageBottomY && !empty($currentSeg)) {
                    // Current row doesn't fit on this page — close the segment
                    $segments[] = $currentSeg;
                    $simY       = $newPageTopY; // new page: rows start after header
                    $currentSeg = [];
                }

                $currentSeg[] = $idx;
                $simY        += $rh;
            }
            if (!empty($currentSeg)) {
                $segments[] = $currentSeg;
            }

            // ── Ensure the address fits within the last segment ───────────────
            if ($address !== '' && !empty($segments)) {
                $lastSegIdxs = end($segments);
                reset($segments);

                $pdf->SetFont(self::FONT, '', self::FONT_SZ);
                $addrW    = self::COLS['address'] - 2 * self::CELL_PAD;
                $lines    = $pdf->getNumLines($address, $addrW);
                $addrH    = max(self::ROW_MIN, $lines * self::LINE_H + 2.0);
                $segTotal = array_sum(array_intersect_key($rowHeights,
                                array_flip($lastSegIdxs)));

                if ($addrH > $segTotal) {
                    $lastIdx               = end($lastSegIdxs);
                    reset($lastSegIdxs);
                    $rowHeights[$lastIdx] += ($addrH - $segTotal);
                }
            }

            // ── Draw each segment ─────────────────────────────────────────────
            foreach ($segments as $segN => $segIdxs) {
                $isFirstSeg = ($segN === 0);

                // New page for every continuation segment
                if (!$isFirstSeg) {
                    $pdf->AddPage();
                    $topY     = $pdf->GetY();
                    $this->drawTableHeader($pdf, $topY);
                    $currentY = $topY + self::HDR_H;
                }

                // Letter divider — only on the very first segment of the letter
                if ($isFirstSeg && $letter !== $curLetter) {
                    $curLetter = $letter;
                    $pdf->SetFillColor(...self::C_DIV_FILL);
                    $pdf->SetTextColor(...self::C_BLACK);
                    $pdf->SetFont(self::FONT, 'B', self::FONT_SZ);
                    $pdf->SetXY(self::MARGIN, $currentY);
                    $pdf->Cell($totalW, self::DIV_H, '  ' . $letter, 0, 0, 'L', true);
                    $this->drawBorder($pdf, self::MARGIN, $currentY, $totalW, self::DIV_H, '1');
                    $currentY += self::DIV_H;
                }

                $segStartY    = $currentY;
                $familyY      = $currentY;
                $isFirstInSeg = true;

                foreach ($segIdxs as $idx) {
                    $m         = $members[$idx];
                    $rh        = $rowHeights[$idx];
                    $isPrimary = ($m['type'] === 'P');
                    $rowY      = $familyY;
                    $x         = self::MARGIN;
                    $nameStyle = $isPrimary ? 'B' : '';

                    // Show family number on: the primary row (first segment only),
                    // OR the first row of any continuation segment so readers can
                    // identify which family is being continued.
                    $showNum = ($isFirstSeg && $isPrimary)
                            || (!$isFirstSeg && $isFirstInSeg);

                    // No.
                    $this->tCell($pdf, $x, $rowY, self::COLS['no'], $rh,
                        $showNum ? (string)$seqNum : '',
                        'C', self::C_WHITE, $nameStyle, self::C_BLACK, '1');
                    $x += self::COLS['no'];

                    // Name (Last  Title  First  Middle)
                    $this->tCell($pdf, $x, $rowY, self::COLS['name'], $rh,
                        $this->formatName($m),
                        'L', self::C_WHITE, $nameStyle, self::C_BLACK, '1');
                    $x += self::COLS['name'];

                    // Gender
                    $this->tCell($pdf, $x, $rowY, self::COLS['gender'], $rh,
                        $m['gender_name'] ?? '',
                        'L', self::C_WHITE, '', self::C_BLACK, '1');
                    $x += self::COLS['gender'];

                    // Relation
                    $this->tCell($pdf, $x, $rowY, self::COLS['relation'], $rh,
                        $m['relationship_name'] ?? '',
                        'L', self::C_WHITE, '', self::C_BLACK, '1');
                    $x += self::COLS['relation'];

                    // DOB
                    $this->tCell($pdf, $x, $rowY, self::COLS['dob'], $rh,
                        self::formatDob($m['dob'] ?? null),
                        'L', self::C_WHITE, '', self::C_BLACK, '1');
                    $x += self::COLS['dob'];

                    // Education
                    $this->tCell($pdf, $x, $rowY, self::COLS['education'], $rh,
                        $m['education'] ?? '',
                        'L', self::C_WHITE, '', self::C_BLACK, '1');
                    $x += self::COLS['education'];

                    // Mobile
                    $this->tCell($pdf, $x, $rowY, self::COLS['mobile'], $rh,
                        $m['mobile'] ?? '',
                        'L', self::C_WHITE, '', self::C_BLACK, '1');
                    $x += self::COLS['mobile'];

                    // Email — omit right border; address cell's left border closes it
                    $this->tCell($pdf, $x, $rowY, self::COLS['email'], $rh,
                        $m['email'] ?? '',
                        'L', self::C_WHITE, '', self::C_BLACK, 'LTB');

                    $familyY      += $rh;
                    $isFirstInSeg  = false;
                }

                // ── Address — spans all rows in this segment ──────────────────
                $segH = $familyY - $segStartY;

                $pdf->SetFillColor(...self::C_WHITE);
                $pdf->Rect($addrX, $segStartY, self::COLS['address'], $segH, 'F');
                $pdf->SetFont(self::FONT, '', self::FONT_SZ);
                $pdf->SetTextColor(...self::C_BLACK);
                $pdf->SetXY($addrX + self::CELL_PAD, $segStartY + self::TOP_PAD);
                $pdf->MultiCell(
                    self::COLS['address'] - 2 * self::CELL_PAD,
                    self::LINE_H,
                    $address,
                    0, 'L', false, 1, '', '', true, 0, false, true,
                    $segH - self::TOP_PAD, 'T'
                );
                $this->drawBorder($pdf, $addrX, $segStartY, self::COLS['address'], $segH, '1');

                $currentY = $familyY;
            }

            $seqNum++;
        }

        $pdf->Output('SDSVG_Directory.pdf', 'D');
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  Private helpers
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Draw the table header row at absolute Y position $y.
     */
    private function drawTableHeader(TCPDF $pdf, float $y): void
    {
        $labels = [
            'no'        => 'No.',
            'name'      => 'Name',
            'gender'    => 'Gender',
            'relation'  => 'Relation',
            'dob'       => 'DOB',
            'education' => 'Education',
            'mobile'    => 'Mobile',
            'email'     => 'Email',
            'address'   => 'Address',
        ];

        $pdf->SetFont(self::FONT, 'B', self::FONT_SZ);
        $pdf->SetTextColor(...self::C_BLACK);

        $x = self::MARGIN;
        foreach ($labels as $key => $label) {
            $this->tCell($pdf, $x, $y, self::COLS[$key], self::HDR_H,
                $label, 'C', self::C_HDR_FILL, 'B', self::C_BLACK, '1');
            $x += self::COLS[$key];
        }
    }

    /**
     * Draw one table cell using:
     *   1. Rect fill (full cell dimensions)
     *   2. MultiCell text with LINE_H line-height and maxh cap
     *   3. Explicit border lines
     *
     * This avoids the overlap bug caused by passing total cell height
     * as the per-line height in MultiCell.
     */
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
        // 1. Fill background
        $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
        $pdf->Rect($x, $y, $w, $h, 'F');

        // 2. Text (no border, no fill — handled above/below)
        if (trim($text) !== '') {
            $pdf->SetFont(self::FONT, $fontStyle, self::FONT_SZ);
            $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
            $pdf->SetXY($x + self::CELL_PAD, $y + self::TOP_PAD);
            $pdf->MultiCell(
                $w - 2 * self::CELL_PAD,
                self::LINE_H,
                $text,
                0,
                $align,
                false,
                1, '', '', true, 0, false, true,
                $h - self::TOP_PAD,  // maxh: never overflow the cell
                'T'
            );
        }

        // 3. Border
        $this->drawBorder($pdf, $x, $y, $w, $h, $border);
    }

    /**
     * Draw cell borders using explicit Rect / Line calls so that
     * partial borders (e.g. 'LTB') work correctly.
     */
    private function drawBorder(
        TCPDF  $pdf,
        float  $x,
        float  $y,
        float  $w,
        float  $h,
        string $border
    ): void {
        if ($border === '' || $border === '0') return;

        $pdf->SetDrawColor(...self::C_LINE);
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

    /**
     * Calculate the required row height for one member row.
     * Uses TCPDF's getNumLines() with the same effective text width
     * used inside tCell (column width minus padding).
     */
    private function calcRowHeight(TCPDF $pdf, array $m): float
    {
        $pdf->SetFont(self::FONT, '', self::FONT_SZ);
        $textW = fn(string $col): float => self::COLS[$col] - 2 * self::CELL_PAD;

        $fields = [
            'name'      => $this->formatName($m),
            'gender'    => $m['gender_name']       ?? '',
            'relation'  => $m['relationship_name'] ?? '',
            'dob'       => self::formatDob($m['dob'] ?? null),
            'education' => $m['education']          ?? '',
            'mobile'    => $m['mobile']             ?? '',
            'email'     => $m['email']              ?? '',
        ];

        $maxLines = 1;
        foreach ($fields as $col => $text) {
            if ($text === '') continue;
            $n        = $pdf->getNumLines($text, $textW($col));
            $maxLines = max($maxLines, $n);
        }

        return max(self::ROW_MIN, $maxLines * self::LINE_H + 2.0);
    }

    /**
     * Format member name as: Last Name  Title  First Name  Middle Name
     */
    private function formatName(array $m): string
    {
        $parts = [];
        if (!empty($m['last_name']))   $parts[] = trim($m['last_name']);
        if (!empty($m['title']))       $parts[] = trim($m['title']);
        if (!empty($m['first_name'])) $parts[] = trim($m['first_name']);
        if (!empty($m['middle_name'])) $parts[] = trim($m['middle_name']);
        return implode(' ', $parts);
    }

    /**
     * Format Y-m-d date as d-Mon-YY  (e.g. 5-Jun-54).
     */
    private static function formatDob(?string $dob): string
    {
        if (!$dob) return '';
        $dt = DateTime::createFromFormat('Y-m-d', $dob);
        return $dt ? $dt->format('j-M-Y') : $dob;
    }
}
