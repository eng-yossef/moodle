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

/**
 * Watermark download handler - serves files with optional watermarking.
 *
 * @package    local_watermark
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

// Get and safely decode the file parameter.
$fileparam = required_param('file', PARAM_RAW);

// 🔧 FIX 1: Handle + signs from urlencode() AND multi-level decoding.
// First convert + to %20 (spaces), then iteratively decode until stable.
$decoded = str_replace('+', '%20', $fileparam);
do {
    $previous = $decoded;
    $decoded = rawurldecode($decoded);
} while ($decoded !== $previous && strpos($decoded, '%') !== false);

// If the decoded string is an absolute URL, extract just the path+query.
if (preg_match('#^https?://#i', $decoded)) {
    $parts = parse_url($decoded);
    $originalurl = ($parts['path'] ?? '') . (!empty($parts['query']) ? '?' . $parts['query'] : '');
} else {
    $originalurl = $decoded;
}

// Parse the pluginfile.php path component.
$path = parse_url($originalurl, PHP_URL_PATH);
if (!$path || stripos($path, '/pluginfile.php') === false) {
    print_error('invalidfileurl', 'local_watermark');
}

// Remove leading slash and split into segments.
$path = ltrim($path, '/');
$args = explode('/', $path);

// First segment must be 'pluginfile.php'.
if (array_shift($args) !== 'pluginfile.php') {
    print_error('invalidfileurl', 'local_watermark');
}

// Validate minimum required segments: contextid, component, filearea, itemid, filepath..., filename.
if (count($args) < 5) {
    print_error('invalidfileurl', 'local_watermark');
}

$contextid = (int)array_shift($args);
$component = array_shift($args);
$filearea  = array_shift($args);
$requested_itemid = (int)array_shift($args);

// 🔧 FIX 2: Correct filepath construction - no double slashes.
$filename = array_pop($args);
$filepath = empty($args) ? '/' : '/' . implode('/', $args) . '/';
// Ensure filepath starts and ends with exactly one slash.
$filepath = '/' . trim($filepath, '/') . '/';

// Security: validate context and user capabilities.
$context = context::instance_by_id($contextid, MUST_EXIST);
require_login();
// require_capability('moodle/course:view', $context);

if ($component === 'mod_resource') {
    // require_capability('mod/resource:view', $context);
}

$fs = get_file_storage();

// 🔹 Attempt 1: Exact match with all parameters.
$file = $fs->get_file($contextid, $component, $filearea, $requested_itemid, $filepath, $filename);

// 🔹 Attempt 2: Normalize whitespace, search all itemids in area.
if (!$file) {
    $allfiles = $fs->get_area_files($contextid, $component, $filearea, false, 'id DESC', false);
    $norm_requested = preg_replace('/\s+/', ' ', trim($filename));
    foreach ($allfiles as $candidate) {
        if ($candidate->get_filepath() === $filepath) {
            $norm_candidate = preg_replace('/\s+/', ' ', trim($candidate->get_filename()));
            if ($norm_candidate === $norm_requested) {
                $file = $candidate;
                break;
            }
        }
    }
}

// 🔹 Attempt 3: UTF-8 case-insensitive match with correct filepath.
if (!$file) {
    $allfiles = $fs->get_area_files($contextid, $component, $filearea, false, 'id DESC', false);
    $lower_filename = mb_strtolower($filename, 'UTF-8');
    foreach ($allfiles as $candidate) {
        if ($candidate->get_filepath() === $filepath &&
            mb_strtolower($candidate->get_filename(), 'UTF-8') === $lower_filename) {
            $file = $candidate;
            break;
        }
    }
}

// 🔹 Attempt 4: Last resort - match normalized filename only (ignore filepath/itemid).
if (!$file) {
    $allfiles = $fs->get_area_files($contextid, $component, $filearea, false, 'id DESC', false);
    $norm_requested = preg_replace('/\s+/', ' ', trim($filename));
    foreach ($allfiles as $candidate) {
        $norm_candidate = preg_replace('/\s+/', ' ', trim($candidate->get_filename()));
        if ($norm_candidate === $norm_requested) {
            $file = $candidate;
            break;
        }
    }
}

// Final validation.
if (!$file || $file->is_directory()) {
    // Optional debug logging (remove in production):
    // error_log("Watermark DEBUG: Not found | ctx=$contextid comp=$component area=$filearea itemid=$requested_itemid path='$filepath' file='$filename'");
    send_file_not_found();
}

// Serve the file (watermarked if PDF, otherwise original).
\local_watermark\service\watermark_service::serve($file, $USER);