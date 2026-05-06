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

require_once(__DIR__ . '/../../vendor/autoload.php');

use setasign\Fpdi\Fpdi;

// ---------------------------------------------------------------------------
// WatermarkPdf — extends FPDI with rotation + alpha (transparency) support.
// ---------------------------------------------------------------------------
class WatermarkPdf extends Fpdi {

    // Tracks current rotation angle so _endpage() can close the q/Q block.
    protected $angle = 0;

    // Use a custom name to prevent overriding FPDF's native $extgstates
    // (which FPDF relies on to process PNG alpha channels like the logo).
    protected $watermark_extgstates = [];

    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4') {
        parent::__construct($orientation, $unit, $size);
        if (version_compare($this->PDFVersion, '1.4', '<')) {
            $this->PDFVersion = '1.4';
        }
    }

    // ── Alpha / transparency ────────────────────────────────────────────────

    public function SetAlpha(float $alpha): void {
        $alpha  = max(0.0, min(1.0, $alpha));
        $gsName = 'GS_A' . str_replace('.', 'p', number_format($alpha, 2));

        if (!array_key_exists($gsName, $this->watermark_extgstates)) {
            $this->watermark_extgstates[$gsName] = [
                'n' => 0,
                'alpha' => $alpha
            ];
        }

        $this->_out('/' . $gsName . ' gs');
    }

    protected function _put_watermark_extgstates(): void {
        foreach ($this->watermark_extgstates as $gsName => &$gs) {
            $this->_newobj();
            $gs['n'] = $this->n;
            $this->_put('<</Type /ExtGState');
            $this->_put(sprintf('/ca %.4F', $gs['alpha'])); // Non-stroking
            $this->_put(sprintf('/CA %.4F', $gs['alpha'])); // Stroking
            $this->_put('/BM /Normal');
            $this->_put('>>');
            $this->_put('endobj');
        }
    }

    protected function _putresourcedict(): void {
        // Track the buffer length so we can securely modify the dictionary
        $startLen = strlen($this->buffer);
        parent::_putresourcedict();

        if (!empty($this->watermark_extgstates)) {
            $myExtGState = '';
            foreach ($this->watermark_extgstates as $gsName => $gs) {
                $myExtGState .= '/' . $gsName . ' ' . $gs['n'] . ' 0 R ';
            }

            $dictChunk = substr($this->buffer, $startLen);

            // Safely inject our custom alpha states to avoid duplicate ExtGState keys
            // which causes Acrobat/PDF viewers to drop images.
            if (strpos($dictChunk, '/ExtGState <<') !== false) {
                $dictChunk = str_replace('/ExtGState <<', '/ExtGState << ' . $myExtGState, $dictChunk);
                $this->buffer = substr($this->buffer, 0, $startLen) . $dictChunk;
            } else {
                $this->_put('/ExtGState << ' . $myExtGState . '>>');
            }
        }
    }

    protected function _putresources(): void {
        $this->_put_watermark_extgstates();
        parent::_putresources();
    }

    // ── Rotation ────────────────────────────────────────────────────────────

    public function Rotate(float $angle, float $x = -1, float $y = -1): void {
        if ($x == -1) $x = $this->x;
        if ($y == -1) $y = $this->y;

        if ($this->angle != 0) {
            $this->_out('Q');
        }

        $this->angle = $angle;

        if ($angle != 0) {
            $rad = $angle * M_PI / 180;
            $c   = cos($rad);
            $s   = sin($rad);
            $cx  = $x * $this->k;
            $cy  = ($this->h - $y) * $this->k;
            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy
            ));
        }
    }

    public function _endpage(): void {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
}

// ---------------------------------------------------------------------------
// watermark_service
// ---------------------------------------------------------------------------
class watermark_service {

    public static function serve(\stored_file $file, \stdClass $user): void {

        if (!get_config('local_watermark', 'enabled')) {
            self::serve_original($file);
            return;
        }

        if ($file->get_mimetype() !== 'application/pdf') {
            self::serve_original($file);
            return;
        }

        $watermarked = self::generate_watermarked_pdf($file, $user);

        if ($watermarked === false) {
            self::serve_original($file);
            return;
        }

        self::output_pdf($watermarked, $file->get_filename());
    }

    // ── PDF generation ──────────────────────────────────────────────────────

    private static function generate_watermarked_pdf(\stored_file $file, \stdClass $user) {
        $tempfile = null;
        try {
            $tempfile  = $file->copy_content_to_temp();
            $pdf       = new WatermarkPdf();

            $pagecount = $pdf->setSourceFile($tempfile);

            for ($pageno = 1; $pageno <= $pagecount; $pageno++) {
                $tpl  = $pdf->importPage($pageno);
                $size = $pdf->getTemplateSize($tpl);

                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);

                $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
                self::apply_watermark($pdf, $user);
            }

            return $pdf->Output('', 'S');

        } catch (\Throwable $e) {
            error_log('local_watermark | generate_watermarked_pdf failed: ' . $e->getMessage());
            return false;
        } finally {
            if ($tempfile && file_exists($tempfile)) {
                @unlink($tempfile);
            }
        }
    }

    // ── Watermark rendering ─────────────────────────────────────────────────
/**
     * Draw all enabled watermark layers (Background, Corner Text, Diagonal Text, and Logo).
     */
    private static function apply_watermark(WatermarkPdf $pdf, \stdClass $user): void {

        $text  = self::build_watermark_text($user);
        $pagew = $pdf->GetPageWidth();
        $pageh = $pdf->GetPageHeight();

        // Helper: read config with fallback default.
        $cfg = static function (string $key, $default) {
            $val = get_config('local_watermark', $key);
            return ($val === false || $val === null || $val === '') ? $default : $val;
        };

        // Helper: #rrggbb → [r, g, b].
        $hex2rgb = static function (string $hex): array {
            $hex = ltrim($hex, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            return array_map('hexdec', str_split($hex, 2));
        };

        // ── 1. Full-page background overlay (Dynamic) ────────────────────────
        if ((bool) $cfg('background_enabled', false)) {
            // Try admin-uploaded background first, then fallback to bundled pix.
            $bgPath = self::get_background_tmp_path() ?? (__DIR__ . '/../../pix/background.png');
            
            if ($bgPath && file_exists($bgPath)) {
                try {
                    $bgAlpha = (float) $cfg('background_alpha', 0.15);
                    $pdf->_out('q'); // Start graphics state isolation
                    $pdf->SetAlpha($bgAlpha);

                    // Detect image type (Required because Moodle temp files lack extensions)
                    $bgType = null;
                    $bgInfo = @getimagesize($bgPath);
                    if ($bgInfo) {
                        $typeMap = [IMAGETYPE_PNG => 'PNG', IMAGETYPE_JPEG => 'JPEG', IMAGETYPE_GIF => 'GIF'];
                        $bgType = $typeMap[$bgInfo[2]] ?? null;
                    }

                    $pdf->Image($bgPath, 0, 0, $pagew, $pageh, $bgType);
                    $pdf->_out('Q'); // Restore graphics state
                    $pdf->SetAlpha(1.0); // Reset tracking
                } catch (\Throwable $e) {
                    $pdf->_out('Q');
                    $pdf->SetAlpha(1.0);
                    error_log('local_watermark | background overlay failed: ' . $e->getMessage());
                }
            }
        }

        // ── 2. Corner text ────────────────────────────────────────────────────
        if ((bool) $cfg('corner_enabled', true)) {
            $fontSize = (int)   $cfg('corner_fontsize',  10);
            $colorHex = (string)$cfg('corner_textcolor', '#969696');
            $margin   = (float) $cfg('corner_margin',    10);
            $rgb      = $hex2rgb($colorHex);

            $pdf->SetFont('Helvetica', '', $fontSize);
            $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
            $pdf->SetAlpha(1.0);

            $textWidth    = $pdf->GetStringWidth($text);
            $fontHeightMm = $fontSize * 0.3528; // pt to mm

            $rawCorners = $cfg('corner_positions', 'top-left,bottom-left,bottom-right,top-right');
            $corners    = is_array($rawCorners)
                ? array_keys(array_filter($rawCorners))
                : array_map('trim', explode(',', $rawCorners));

            foreach ($corners as $corner) {
                switch ($corner) {
                    case 'top-left':     $x = $margin; $y = $margin + $fontHeightMm; break;
                    case 'top-right':    $x = $pagew - $textWidth - $margin; $y = $margin + $fontHeightMm; break;
                    case 'bottom-left':  $x = $margin; $y = $pageh - $margin; break;
                    case 'bottom-right': $x = $pagew - $textWidth - $margin; $y = $pageh - $margin; break;
                    default: continue 2;
                }
                $pdf->Text($x, $y, $text);
            }
        }

        // ── 3. Diagonal watermark ─────────────────────────────────────────────
        if ((bool) $cfg('diagonal_enabled', true)) {
            $fontSize = (int)   $cfg('diagonal_fontsize',  25);
            $colorHex = (string)$cfg('diagonal_textcolor', '#C8C8C8');
            $angle    = (int)   $cfg('diagonal_angle',     45);
            $offsetX  = (float) $cfg('diagonal_offset_x',  0);
            $offsetY  = (float) $cfg('diagonal_offset_y',  0);
            $rgb      = $hex2rgb($colorHex);

            $pdf->SetFont('Helvetica', 'B', $fontSize);
            $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
            $pdf->SetAlpha(1.0);

            $textWidth = $pdf->GetStringWidth($text);
            $centerX   = ($pagew / 2) + $offsetX;
            $centerY   = ($pageh / 2) + $offsetY;

            $pdf->Rotate($angle, $centerX, $centerY);
            $pdf->Text($centerX - ($textWidth / 2), $centerY, $text);
            $pdf->Rotate(0, $centerX, $centerY); // Reset rotation matrix
        }

        // ── 4. Logo ───────────────────────────────────────────────────────────
        if ((bool) $cfg('logo_enabled', false)) {
            $logoWidth  = (float)  $cfg('logo_width',    15);
            $logoMargin = (float)  $cfg('logo_margin',   10);
            $logoPos    = (string) $cfg('logo_position', 'top-right');

            switch ($logoPos) {
                case 'top-left':     $xLogo = $logoMargin; $yLogo = $logoMargin; break;
                case 'bottom-left':  $xLogo = $logoMargin; $yLogo = $pageh - $logoWidth - $logoMargin; break;
                case 'bottom-right': $xLogo = $pagew - $logoWidth - $logoMargin; $yLogo = $pageh - $logoWidth - $logoMargin; break;
                case 'top-right':
                default:             $xLogo = $pagew - $logoWidth - $logoMargin; $yLogo = $logoMargin; break;
            }

            $pdf->SetAlpha(1.0);
            $logoPath = self::get_logo_tmp_path() ?? (__DIR__ . '/../../pix/logo.png');

            if ($logoPath && file_exists($logoPath)) {
                try {
                    $logoType = null;
                    $logoInfo = @getimagesize($logoPath);
                    if ($logoInfo) {
                        $typeMap = [IMAGETYPE_PNG => 'PNG', IMAGETYPE_JPEG => 'JPEG', IMAGETYPE_GIF => 'GIF'];
                        $logoType = $typeMap[$logoInfo[2]] ?? null;
                    }

                    if ($logoType !== null) {
                        $pdf->Image($logoPath, $xLogo, $yLogo, $logoWidth, 0, $logoType);
                    }
                } catch (\Throwable $e) {
                    error_log('local_watermark | logo render failed: ' . $e->getMessage());
                }
            }
        }
    }
    
    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Copy the admin-uploaded background to a temp file.
     */
    private static function get_background_tmp_path(): ?string {
        $fs = get_file_storage();
        $context = \context_system::instance();
        $files = $fs->get_area_files($context->id, 'local_watermark', 'background', 0, 'id DESC', false);

        foreach ($files as $f) {
            return $f->copy_content_to_temp();
        }
        return null;
    }

    private static function build_watermark_text(\stdClass $user): string {
        $template = get_config('local_watermark', 'template');
        if (empty($template)) {
            $template = 'User: {username} | ID: {userid}';
        }

        $usertimezone  = \core_date::get_user_timezone($user);
        $datetime      = new \DateTime('now', new \DateTimeZone($usertimezone));
        $formattedtime = $datetime->format('Y-m-d H:i');

        $replacements = [
            '{username}'  => (string) ($user->username  ?? ''),
            '{userid}'    => (string) ($user->id        ?? ''),
            '{email}'     => (string) ($user->email     ?? ''),
            '{firstname}' => (string) ($user->firstname ?? ''),
            '{lastname}'  => (string) ($user->lastname  ?? ''),
            '{time}'      => $formattedtime,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private static function get_logo_tmp_path(): ?string {
        $fs      = get_file_storage();
        $context = \context_system::instance();
        $files   = $fs->get_area_files(
            $context->id,
            'local_watermark',
            'logo',
            0,
            'id DESC',
            false
        );

        foreach ($files as $f) {
            return $f->copy_content_to_temp();
        }

        return null;
    }

    private static function output_pdf(string $content, string $originalfilename): void {
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="watermarked_' . rawurlencode($originalfilename) . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $content;
        exit;
    }

    private static function serve_original(\stored_file $file): void {
        send_stored_file($file, 0, 0, true);
    }
}