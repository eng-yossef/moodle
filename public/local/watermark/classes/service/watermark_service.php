<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_watermark\service;

defined('MOODLE_INTERNAL') || die();

// Load Composer's autoloader for FPDI and FPDF.
require_once(__DIR__ . '/../../vendor/autoload.php');

use setasign\Fpdi\Fpdi;


// Extend Fpdi to add Rotate functionality (similar to FPDF's Rotate)
class WatermarkPdf extends Fpdi {
    protected $angle = 0;

    function Rotate($angle, $x = -1, $y = -1) {
        if ($x == -1)
            $x = $this->x;
        if ($y == -1)
            $y = $this->y;
        if ($this->angle != 0)
            $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }

    function _endpage() {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    // Optional: add text with rotation at given coordinates
    function RotatedText($x, $y, $txt, $angle) {
        $this->Rotate($angle, $x, $y);
        $this->Text($x, $y, $txt);
        $this->Rotate(0);
    }
}

class watermark_service {

    /**
     * Serve a file – watermarked if PDF, otherwise unchanged.
     *
     * @param \stored_file $file The file to serve.
     * @param \stdClass $user The current user.
     */
    public static function serve($file, $user) {

     $enabled = (int) get_config('local_watermark', 'enabled');

        if ($enabled !== 1) {
            self::serve_original($file);
            return;
        }
        $mimetype = $file->get_mimetype();

        // Only watermark PDFs.
        if ($mimetype !== 'application/pdf') {
            self::serve_original($file);
            return;
        }

        // Try to get from cache.
        // $cached = cache_service::get($file, $user);
        // if ($cached !== false) {
        //     self::output($cached, $file->get_filename());
        //     return;
        // }

        // Generate watermarked PDF.
        $watermarked = self::generate_watermarked_pdf($file, $user);
        if ($watermarked === false) {
            // Fallback: serve original if watermarking fails.
            self::serve_original($file);
            return;
        }

        // Store in cache.
        // cache_service::store($file, $user, $watermarked);

        // Output.
        self::output($watermarked, $file->get_filename());
    }

    /**
     * Generate a watermarked PDF as a string.
     *
     * @param \stored_file $file Original PDF.
     * @param \stdClass $user Current user.
     * @return string|false PDF content or false on error.
     */
    private static function generate_watermarked_pdf($file, $user) {
    try {
        $tempfile = $file->copy_content_to_temp();
        $pdf = new WatermarkPdf();

        $pagecount = $pdf->setSourceFile($tempfile);

        for ($pageno = 1; $pageno <= $pagecount; $pageno++) {
            $template = $pdf->importPage($pageno);
            $size = $pdf->getTemplateSize($template);

            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($template, 0, 0, $size['width'], $size['height']); // ← explicit size

            self::apply_watermark($pdf, $user);
        }

        $output = $pdf->Output('', 'S'); // ← some FPDI versions need empty string as first arg
        
        // Clean up temp file
        @unlink($tempfile);

        return $output;

    } catch (\Exception $e) {
        debugging('Watermark generation failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

    /**
     * Apply watermark text in three corners and a logo in the top‑right.
     *
     * @param WatermarkPdf $pdf PDF instance.
     * @param \stdClass $user Current user.
     */
        /**
     * Apply watermark text in three corners and a logo in the top‑right.
     *
     * @param WatermarkPdf $pdf PDF instance.
     * @param \stdClass $user Current user.
     */
    private static function apply_watermark($pdf, $user) {
    $text = self::build_watermark_text($user);
    $pagew = $pdf->GetPageWidth();
    $pageh = $pdf->GetPageHeight();

    $cfg = function($key, $default) {
        $val = get_config('local_watermark', $key);
        return ($val === false || $val === null) ? $default : $val;
    };

    $hex2rgb = function($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        return array_map('hexdec', str_split($hex, 2));
    };

    // ---------------------------------------------------------------
    // --- Corner text ---
    // ---------------------------------------------------------------
    $cornerEnabled = (bool) $cfg('corner_enabled', true);
    if ($cornerEnabled) {
        $fontSize   = (int) $cfg('corner_fontsize', 10);
        $colorHex   = $cfg('corner_textcolor', '#969696');
        $margin     = (float) $cfg('corner_margin', 10);
        $rgb        = $hex2rgb($colorHex);

        $pdf->SetFont('Helvetica', '', $fontSize);
        $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);

        $textWidth    = $pdf->GetStringWidth($text);
        $fontHeightMm = $fontSize * 0.3528;

        $corners = $cfg('corner_positions', 'top-left,bottom-left,bottom-right');
        if (!is_array($corners)) {
            $corners = explode(',', $corners);
        } else {
            $corners = array_keys(array_filter($corners));
        }

        foreach ($corners as $corner) {
            $corner = trim($corner);
            switch ($corner) {
                case 'top-left':
                    $x = $margin;
                    $y = $margin + $fontHeightMm;
                    break;
                case 'top-right':
                    $x = $pagew - $textWidth - $margin;
                    $y = $margin + $fontHeightMm;
                    break;
                case 'bottom-left':
                    $x = $margin;
                    $y = $pageh - $margin;
                    break;
                case 'bottom-right':
                    $x = $pagew - $textWidth - $margin;
                    $y = $pageh - $margin;
                    break;
                default:
                    continue 2;
            }
            $pdf->Text($x, $y, $text);
        }
    }

    // ---------------------------------------------------------------
    // --- Diagonal watermark ---
    // ---------------------------------------------------------------
    $diagEnabled = (bool) $cfg('diagonal_enabled', true);
    if ($diagEnabled) {
        $diagFontSize = (int)   $cfg('diagonal_fontsize',  25);
        $diagColorHex = (string)$cfg('diagonal_textcolor', '#C8C8C8');
        $diagAngle    = (int)   $cfg('diagonal_angle',     45);
        $diagOffsetX  = (float) $cfg('diagonal_offset_x',  0);
        $diagOffsetY  = (float) $cfg('diagonal_offset_y',  0);

        $rgb = $hex2rgb($diagColorHex);
        $pdf->SetFont('Helvetica', 'B', $diagFontSize);
        $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);

        $diagTextWidth = $pdf->GetStringWidth($text);
        $centerX = ($pagew / 2) + $diagOffsetX;
        $centerY = ($pageh / 2) + $diagOffsetY;

        $pdf->Rotate($diagAngle, $centerX, $centerY);
        $pdf->Text($centerX - ($diagTextWidth / 2), $centerY, $text);
        $pdf->Rotate(0); // reset rotation
    }

    // ---------------------------------------------------------------
    // --- Logo via Moodle File API ---
    // ---------------------------------------------------------------
    $logoEnabled = (bool) $cfg('logo_enabled', true);
    if (!$logoEnabled) {
        return;
    }

    $logoWidth  = (float) $cfg('logo_width',  15);   // mm
    $logoMargin = (float) $cfg('logo_margin', 10);   // mm
    $logoPos    = (string)$cfg('logo_position', 'top-right'); // top-left|top-right|bottom-left|bottom-right

    // Resolve X/Y from position setting.
    // Note: logo height is unknown until after Image() is called (FPDF auto-calculates).
    // We use $logoWidth as a safe approximation for bottom-edge Y offset.
    switch ($logoPos) {
        case 'top-left':
            $xLogo = $logoMargin;
            $yLogo = $logoMargin;
            break;
        case 'bottom-left':
            $xLogo = $logoMargin;
            $yLogo = $pageh - $logoWidth - $logoMargin; // approximate; adjust if needed
            break;
        case 'bottom-right':
            $xLogo = $pagew - $logoWidth - $logoMargin;
            $yLogo = $pageh - $logoWidth - $logoMargin;
            break;
        case 'top-right':
        default:
            $xLogo = $pagew - $logoWidth - $logoMargin;
            $yLogo = $logoMargin;
            break;
    }

    // --- 1. Try Moodle File API first (admin-uploaded logo) ---
    $logoTmpPath = self::get_logo_tmp_path();

    if ($logoTmpPath !== null) {
        try {
            $ext          = strtolower(pathinfo($logoTmpPath, PATHINFO_EXTENSION));
            $fpdfTypeMap  = ['png' => 'PNG', 'jpg' => 'JPEG', 'jpeg' => 'JPEG', 'gif' => 'GIF'];
            $fpdfType     = $fpdfTypeMap[$ext] ?? null;

            if ($fpdfType === null) {
                throw new \RuntimeException("Unsupported logo extension: $ext");
            }

            // Pass explicit $type to bypass FPDF's extension sniffing.
            // h=0 → FPDF auto-calculates height to preserve aspect ratio.
            $pdf->Image($logoTmpPath, $xLogo, $yLogo, $logoWidth, 0, $fpdfType);

        } catch (\Exception $e) {
            // Log but never break the watermark pipeline.
            debugging('local_watermark: logo render failed — ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        // NOTE: Do NOT unlink here. Cleanup is handled by the caller
        // after $pdf->Output() completes (see process_pdf()).
        return;
    }

    // --- 2. Fallback: static pix/logo.png bundled with the plugin ---
    $fallbackPath = __DIR__ . '/../../pix/logo.png';
    if (file_exists($fallbackPath)) {
        try {
            $pdf->Image($fallbackPath, $xLogo, $yLogo, $logoWidth, 0, 'PNG');
        } catch (\Exception $e) {
            debugging('local_watermark: fallback logo render failed — ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}

// ---------------------------------------------------------------
// Helper: extract Moodle stored logo to a temp file WITH extension.
// Returns absolute path on success, null if no logo is stored.
// Caller is responsible for unlinking AFTER $pdf->Output().
// ---------------------------------------------------------------
private static function get_logo_tmp_path(): ?string {
    $fs      = get_file_storage();
    $context = \context_system::instance();

    $files = $fs->get_area_files(
        $context->id,
        'local_watermark',
        'logo',
        0,                             // itemid=0 for committed configstoredfile records
        'itemid, filepath, filename',
        false                          // exclude directories
    );

    foreach ($files as $file) {
        if ($file->get_filename() === '.') {
            continue; // skip directory placeholder
        }

        $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));

        // tempnam() produces a file WITHOUT extension — FPDF requires one.
        // Strategy: create a uniquely-named temp file, then rename with extension.
        $base = tempnam(sys_get_temp_dir(), 'wm_logo_');
        if ($base === false) {
            debugging('local_watermark: could not create temp file', DEBUG_DEVELOPER);
            return null;
        }

        $tmpPath = $base . '.' . $ext;  // e.g. /tmp/wm_logo_A3f9B2.png

        $written = file_put_contents($tmpPath, $file->get_content());

        // Remove the original extensionless file left behind by tempnam().
        @unlink($base);

        if ($written === false || $written === 0) {
            @unlink($tmpPath);
            debugging('local_watermark: failed to write logo temp file', DEBUG_DEVELOPER);
            return null;
        }

        return $tmpPath; // return on first valid file
    }

    return null; // no logo uploaded
}
    /**
     * Build the watermark text from the admin template.
     *
     * @param \stdClass $user User object.
     * @return string
     */
    private static function build_watermark_text($user) {
    $template = get_config('local_watermark', 'template');
    if (empty($template)) {
        $template = 'User: {username} | ID: {userid}';
    }

    // Leading backslash = global Moodle class, not the local namespace.
    $usertimezone  = \core_date::get_user_timezone($user);
    $datetime      = new \DateTime('now', new \DateTimeZone($usertimezone));
    $datetime->modify('+1 hour');
    $formattedtime = $datetime->format('Y-m-d H:i');

    $replacements = [
        '{username}'  => $user->username,
        '{userid}'    => $user->id,
        '{email}'     => $user->email,
        '{firstname}' => $user->firstname,
        '{lastname}'  => $user->lastname,
        '{time}'      => $formattedtime,
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

    /**
     * Output the PDF content to the browser.
     *
     * @param string $content PDF raw data.
     * @param string $originalfilename Original file name (for download).
     */
    private static function output($content, $originalfilename) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="watermarked_' . $originalfilename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    /**
     * Serve the original file without watermarking.
     *
     * @param \stored_file $file
     */
    private static function serve_original($file) {
        send_stored_file($file);
    }
}