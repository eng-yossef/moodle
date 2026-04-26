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
        $mimetype = $file->get_mimetype();

        // Only watermark PDFs.
        if ($mimetype !== 'application/pdf') {
            self::serve_original($file);
            return;
        }

        // Try to get from cache.
        $cached = cache_service::get($file, $user);
        if ($cached !== false) {
            self::output($cached, $file->get_filename());
            return;
        }

        // Generate watermarked PDF.
        $watermarked = self::generate_watermarked_pdf($file, $user);
        if ($watermarked === false) {
            // Fallback: serve original if watermarking fails.
            self::serve_original($file);
            return;
        }

        // Store in cache.
        cache_service::store($file, $user, $watermarked);

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

            // Get number of pages.
            $pagecount = $pdf->setSourceFile($tempfile);

            for ($pageno = 1; $pageno <= $pagecount; $pageno++) {
                $template = $pdf->importPage($pageno);
                $size = $pdf->getTemplateSize($template);

                // Add a page with same orientation and dimensions.
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($template);

                // Place watermark.
                self::apply_watermark($pdf, $user);
            }

            // Return PDF as string.
            return $pdf->Output('S');
        } catch (\Exception $e) {
            debugging('Watermark generation failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Apply the watermark text on the current PDF page.
     *
     * @param WatermarkPdf $pdf PDF instance.
     * @param \stdClass $user Current user.
     */
    private static function apply_watermark($pdf, $user) {
        $text = self::build_watermark_text($user);

        $pdf->SetFont('helvetica', 'B', 30);
        $pdf->SetTextColor(180, 180, 180); // Light gray.

        // Diagonal watermark across the page - rotate 45 degrees, place at center.
        $pagew = $pdf->GetPageWidth();
        $pageh = $pdf->GetPageHeight();
        $centerX = $pagew / 2;
        $centerY = $pageh / 2;

        $pdf->Rotate(45, $centerX, $centerY);
        $pdf->SetXY($centerX - 80, $centerY - 10);
        $pdf->Cell(0, 10, $text, 0, 0, 'C');
        $pdf->Rotate(0, $centerX, $centerY);
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

        $replacements = [
            '{username}'  => $user->username,
            '{userid}'    => $user->id,
            '{email}'     => $user->email,
            '{firstname}' => $user->firstname,
            '{lastname}'  => $user->lastname,
            '{time}'      => userdate(time()),
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