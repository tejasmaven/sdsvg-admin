<?php
/**
 * Captcha — self-contained, no third-party service.
 *
 * Generates a short, high-contrast, easy-to-read code and renders it as a PNG
 * via GD. Designed for senior citizens: large glyphs, generous spacing, no
 * confusing characters (0/O, 1/I/L removed), minimal distortion.
 *
 * The expected code lives in $_SESSION under a per-purpose key so the search
 * and edit flows can't be replayed against each other.
 */
class Captcha
{
    /** Characters used — ambiguous ones (0 O 1 I L) deliberately excluded. */
    private const CHARS  = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const LENGTH = 5;

    /** Session key prefix. */
    private const SKEY = 'captcha_';

    /**
     * Create a fresh code for a purpose ('search' | 'edit') and store it.
     * Returns the generated code (caller normally does not need it).
     */
    public static function generate(string $purpose): string
    {
        $chars = self::CHARS;
        $max   = strlen($chars) - 1;
        $code  = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= $chars[random_int(0, $max)];
        }
        $_SESSION[self::SKEY . $purpose] = $code;
        return $code;
    }

    /**
     * Validate user input for a purpose (case-insensitive). The stored code is
     * cleared after one check so each code is single-use.
     */
    public static function check(string $purpose, string $input): bool
    {
        $key      = self::SKEY . $purpose;
        $expected = $_SESSION[$key] ?? '';
        unset($_SESSION[$key]); // single use — always clear
        if ($expected === '' || $input === '') {
            return false;
        }
        return strcasecmp(trim($input), $expected) === 0;
    }

    /**
     * Stream a PNG of the current code for a purpose. If none exists yet, one is
     * generated. Caller must not output anything else.
     */
    public static function render(string $purpose): void
    {
        if (empty($_SESSION[self::SKEY . $purpose])) {
            self::generate($purpose);
        }
        $code = $_SESSION[self::SKEY . $purpose];

        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if (!function_exists('imagecreatetruecolor')) {
            // GD not available — render a tiny transparent px so the page still works.
            // (The field will accept the text shown via the fallback in the view.)
            http_response_code(500);
            return;
        }

        $w = 220; $h = 80;
        $img = imagecreatetruecolor($w, $h);

        // High-contrast: near-white background, dark navy text
        $bg   = imagecolorallocate($img, 245, 247, 250);
        $text = imagecolorallocate($img, 20, 40, 90);
        $line = imagecolorallocate($img, 170, 185, 210);
        imagefilledrectangle($img, 0, 0, $w, $h, $bg);

        // A few light guide lines — gentle, so it stays readable
        for ($i = 0; $i < 4; $i++) {
            imageline($img, random_int(0, $w), 0, random_int(0, $w), $h, $line);
        }

        // Draw each character large, with slight vertical jitter only
        $len    = strlen($code);
        $slotW  = (int) (($w - 30) / $len);
        $fontSz = 5; // largest built-in GD font
        $gx = imagefontwidth($fontSz);
        $gy = imagefontheight($fontSz);
        // Scale the built-in glyph up by rendering to a small image then copy-resize
        for ($i = 0; $i < $len; $i++) {
            $ch = $code[$i];
            $tmp = imagecreatetruecolor($gx, $gy);
            $tbg = imagecolorallocate($tmp, 245, 247, 250);
            imagefilledrectangle($tmp, 0, 0, $gx, $gy, $tbg);
            imagestring($tmp, $fontSz, 0, 0, $ch, $text);

            $scale = 3.4;
            $dw = (int) ($gx * $scale);
            $dh = (int) ($gy * $scale);
            $dx = 18 + $i * $slotW;
            $dy = (int) (($h - $dh) / 2) + random_int(-6, 6);
            imagecopyresampled($img, $tmp, $dx, $dy, 0, 0, $dw, $dh, $gx, $gy);
            imagedestroy($tmp);
        }

        imagepng($img);
        imagedestroy($img);
    }
}
