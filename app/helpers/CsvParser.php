<?php
/**
 * CsvParser — parses the SDSVG import CSV file
 *
 * Expected header row (0-indexed):
 *   0:Group  1:P/S  2:Title  3:First Name  4:Middle Name  5:Last Name
 *   6:Gender 7:Relationship  8:DOB  9:Education  10:Mobile  11:Email  12:Address
 */
class CsvParser
{
    private const EXPECTED_HEADERS = [
        'Group', 'P/S', 'Title', 'First Name', 'Middle Name', 'Last Name',
        'Gender', 'Relationship', 'DOB', 'Education', 'Mobile', 'Email', 'Address',
    ];

    /**
     * Parse a CSV file and validate each row.
     *
     * @return array{valid: array, errors: array, warnings: array, total: int}
     */
    public static function parse(string $filePath): array
    {
        $valid    = [];
        $errors   = [];
        $warnings = [];
        $total    = 0;

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return ['valid' => [], 'errors' => [['row' => 0, 'message' => 'Could not open file.']], 'warnings' => [], 'total' => 0];
        }

        // ── Header row ────────────────────────────────────────────────────────
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return ['valid' => [], 'errors' => [['row' => 0, 'message' => 'Empty CSV file.']], 'warnings' => [], 'total' => 0];
        }

        // Normalise BOM if present
        if (isset($header[0])) {
            $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
        }

        // Validate header
        foreach (self::EXPECTED_HEADERS as $i => $expectedCol) {
            $actual = isset($header[$i]) ? trim($header[$i]) : '';
            if (strcasecmp($actual, $expectedCol) !== 0) {
                fclose($handle);
                return [
                    'valid'    => [],
                    'errors'   => [['row' => 1, 'message' => "Header column {$i} should be '{$expectedCol}', got '{$actual}'."]],
                    'warnings' => [],
                    'total'    => 0,
                ];
            }
        }

        // Load lookup tables for validation
        $db = Database::getInstance();
        $genders       = self::loadLookup($db, 'genders',       'name', 'gender_id');
        $relationships = self::loadLookup($db, 'relationships',  'name', 'relationship_id');

        $seenMobilesInFile = []; // unused but kept for future use

        // ── Data rows ─────────────────────────────────────────────────────────
        $rowNumber = 1; // after header
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $total++;

            // Skip completely empty rows
            if (count(array_filter(array_map('trim', $row))) === 0) {
                continue;
            }

            // Pad row to expected length
            while (count($row) < 13) $row[] = '';

            // Detect encoding on first data row
            if ($rowNumber === 2) {
                $enc = mb_detect_encoding(implode('', $row), ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                if ($enc !== 'UTF-8' && $enc !== false) {
                    $row = array_map(fn($v) => mb_convert_encoding($v, 'UTF-8', $enc), $row);
                }
            }

            $rowErrors = [];

            // Map columns
            $groupId      = trim($row[0]);
            $type         = strtoupper(trim($row[1]));
            $title        = trim($row[2]);
            $firstName    = trim($row[3]);
            $middleName   = trim($row[4]);
            $lastName     = trim($row[5]);
            $genderName   = trim($row[6]);
            $relName      = trim($row[7]);
            $dob          = trim($row[8]);
            $education    = trim($row[9]);
            // Normalise mobile: strip spaces around commas, keep the commas
            $mobile       = trim($row[10]);
            $mobile       = preg_replace('/\s*,\s*/', ', ', $mobile); // "1234, 5678" canonical form
            $email        = trim($row[11]);
            $address      = trim($row[12]);

            // Required fields
            if ($firstName === '') $rowErrors[] = 'First Name is required';
            if ($lastName  === '') $rowErrors[] = 'Last Name is required';
            if ($groupId   === '') $rowErrors[] = 'Group ID is required';

            // Type must be P or S
            if (!in_array($type, ['P', 'S'])) {
                $rowErrors[] = "P/S must be 'P' or 'S', got '{$type}'";
            }

            // DOB validation — optional field; validate format only if a value is provided
            if ($dob !== '') {
                // Accept DD/MM/YYYY, YYYY-MM-DD, or DD-MM-YYYY
                $parsedDob = null;
                foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $fmt) {
                    $dt = DateTime::createFromFormat($fmt, $dob);
                    if ($dt && $dt->format($fmt === 'Y-m-d' ? 'Y-m-d' : $fmt) === $dob) {
                        $parsedDob = $dt->format('Y-m-d');
                        break;
                    }
                }
                if ($parsedDob === null) {
                    $rowErrors[] = "DOB '{$dob}' is not a recognised date (use YYYY-MM-DD or DD/MM/YYYY)";
                } else {
                    $dob = $parsedDob;
                    if ($dob > date('Y-m-d')) {
                        $rowErrors[] = 'DOB cannot be in the future';
                    }
                }
            } else {
                $dob = null; // DOB is optional — store NULL in the database
            }

            // Gender lookup
            $genderIdResolved = null;
            if ($genderName !== '') {
                $genderIdResolved = $genders[strtolower($genderName)] ?? null;
                if ($genderIdResolved === null) {
                    $rowErrors[] = "Gender '{$genderName}' not found";
                }
            } else {
                $rowErrors[] = 'Gender is required';
            }

            // Relationship lookup
            $relIdResolved = null;
            if ($relName !== '') {
                $relIdResolved = $relationships[strtolower($relName)] ?? null;
                if ($relIdResolved === null) {
                    $rowErrors[] = "Relationship '{$relName}' not found";
                }
            } else {
                $rowErrors[] = 'Relationship is required';
            }

            // Mobile and Email are accepted as-is during import — no format validation

            // Build parsed row
            $parsed = [
                'row_number'     => $rowNumber,
                'group_id'       => $groupId,
                'type'           => $type,
                'title'          => $title,
                'first_name'     => $firstName,
                'middle_name'    => $middleName,
                'last_name'      => $lastName,
                'gender_id'      => $genderIdResolved,
                'relationship_id'=> $relIdResolved,
                'dob'            => $dob,
                'education'      => $education,
                'mobile'         => $mobile,
                'email'          => $email,
                'address'        => $address,
            ];

            if (!empty($rowErrors)) {
                $errors[] = array_merge($parsed, ['errors' => $rowErrors]);
                continue;
            }

            $valid[] = $parsed;
        }

        fclose($handle);

        // Flag groups that have no P row
        $groupTypes = [];
        foreach ($valid as $r) {
            $groupTypes[$r['group_id']][] = $r['type'];
        }
        foreach ($valid as &$r) {
            if (!in_array('P', $groupTypes[$r['group_id']] ?? [])) {
                $r['_warnings'][] = "Group {$r['group_id']} has no Primary (P) member";
                $warnings[] = array_merge($r, ['warnings' => ["Group {$r['group_id']} has no Primary member"]]);
            }
        }
        unset($r);

        return [
            'valid'    => $valid,
            'errors'   => $errors,
            'warnings' => $warnings,
            'total'    => $total,
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private static function loadLookup(PDO $db, string $table, string $nameCol, string $idCol): array
    {
        $rows = $db->query("SELECT {$idCol}, {$nameCol} FROM {$table} WHERE is_active = 1")->fetchAll();
        $map  = [];
        foreach ($rows as $row) {
            $map[strtolower($row[$nameCol])] = (int) $row[$idCol];
        }
        return $map;
    }

}
