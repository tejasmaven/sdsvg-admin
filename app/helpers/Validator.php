<?php
/**
 * Validator — static field-level validation helpers
 *
 * Each method returns an error string on failure, or '' on pass.
 */
class Validator
{
    /**
     * Value must be non-empty after trimming.
     */
    public static function required(string $value, string $fieldLabel): string
    {
        return (trim($value) === '') ? "{$fieldLabel} is required." : '';
    }

    /**
     * Value must be a valid Y-m-d date and must not be in the future.
     * Empty string is accepted (not required).
     */
    public static function dateFormat(string $value): string
    {
        if (trim($value) === '') {
            return 'Date of birth is required.';
        }
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            return 'Date must be in YYYY-MM-DD format.';
        }
        if ($d > new DateTime('today')) {
            return 'Date of birth cannot be in the future.';
        }
        return '';
    }

    /**
     * Mobile — optional. If provided, may contain multiple numbers separated by commas.
     * Each individual number must be exactly 10 digits.
     * e.g. "9876543210" or "9876543210, 9876543211" are both valid.
     */
    public static function mobile(string $value): string
    {
        $v = trim($value);
        if ($v === '') return '';

        $numbers = array_map('trim', explode(',', $v));
        $invalid = [];
        foreach ($numbers as $num) {
            if ($num === '') continue; // skip trailing comma
            if (!preg_match('/^\d{10}$/', $num)) {
                $invalid[] = $num;
            }
        }

        if (!empty($invalid)) {
            return 'Each mobile number must be exactly 10 digits. Invalid: ' . implode(', ', $invalid) . '.';
        }
        return '';
    }

    /**
     * Email must be valid format if provided.
     */
    public static function email(string $value): string
    {
        $v = trim($value);
        if ($v === '') return '';
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            return 'Email address is not valid.';
        }
        return '';
    }

    /**
     * Value must exist in the provided array of valid IDs.
     */
    public static function inLookup(string $value, array $validIds): string
    {
        if (!in_array((int) $value, array_map('intval', $validIds), true)) {
            return 'Please select a valid option.';
        }
        return '';
    }

    /**
     * String max length check.
     */
    public static function maxLength(string $value, int $max, string $fieldLabel): string
    {
        if (mb_strlen(trim($value)) > $max) {
            return "{$fieldLabel} must not exceed {$max} characters.";
        }
        return '';
    }
}
