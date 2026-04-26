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

defined('MOODLE_INTERNAL') || die();

/**
 * Core Moodle hook: rewrite all pluginfile.php URLs (relative or absolute).
 * Called automatically by file_rewrite_pluginfile_urls().
 */
function local_watermark_file_rewrite_pluginfile_urls($text, $file = null, $options = null) {
    $enabled = get_config('local_watermark', 'enabled');
    if (empty($enabled)) {
        return $text;
    }

    // Match both relative (/pluginfile.php/...) and absolute (http://.../pluginfile.php/...) URLs.
    return preg_replace_callback(
        '#(?:https?://[^/]+)?/pluginfile\.php/[^\s"\']+#',
        function ($matches) {
            $original = $matches[0];
            $encoded = urlencode($original);
            return "/local/watermark/download.php?file={$encoded}";
        },
        $text
    );
}

/**
 * Output buffer fallback – triggered by the 'before_http_headers' event.
 */
function local_watermark_start_url_rewriting() {
    $enabled = get_config('local_watermark', 'enabled');
    if (empty($enabled)) {
        return;
    }

    ob_start(function ($buffer) {
        return preg_replace_callback(
            '#(?:https?://[^/]+)?/pluginfile\.php/[^\s"\']+#',
            function ($matches) {
                $original = $matches[0];
                $encoded = urlencode($original);
                return "/local/watermark/download.php?file={$encoded}";
            },
            $buffer
        );
    });
}

/**
 * Final safeguard: JavaScript that rewrites any remaining pluginfile.php links
 * after the page is loaded (catches links added dynamically).
 */
function local_watermark_before_http_headers() {
    global $PAGE;
    $enabled = get_config('local_watermark', 'enabled');
    if (empty($enabled)) {
        return;
    }

    $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            $(document).ready(function() {
                $('a[href*=\"/pluginfile.php\"]').each(function() {
                    var original = $(this).attr('href');
                    // Avoid double rewriting.
                    if (original.indexOf('/local/watermark/download.php') === -1) {
                        var encoded = encodeURIComponent(original);
                        $(this).attr('href', '/local/watermark/download.php?file=' + encoded);
                    }
                });
            });
        });
    ");
}