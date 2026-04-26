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

namespace local_watermark\observer;

defined('MOODLE_INTERNAL') || die();

class url_rewriter {

    /**
     * Start output buffering to replace pluginfile.php URLs.
     *
     * @param \core\event\before_http_headers $event
     */
    public static function start_rewriting($event) {
        $enabled = get_config('local_watermark', 'enabled');
        if (empty($enabled)) {
            return;
        }

        ob_start(function ($buffer) {
            return preg_replace_callback(
                '#/pluginfile\.php/[^\s"\']+#',
                function ($matches) {
                    $encoded = urlencode($matches[0]);
                    return "/local/watermark/download.php?file={$encoded}";
                },
                $buffer
            );
        });
    }
}