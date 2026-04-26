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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/vendor/autoload.php');
$fileparam = required_param('file', PARAM_RAW);
$originalurl = urldecode($fileparam);

// Parse the original pluginfile.php URL to extract the path.
$path = parse_url($originalurl, PHP_URL_PATH);
if (!$path) {
    print_error('invalidfileurl', 'local_watermark');
}
$args = explode('/', trim($path, '/'));
if (empty($args) || $args[0] !== 'pluginfile.php') {
    print_error('invalidfileurl', 'local_watermark');
}
array_shift($args); // Remove 'pluginfile.php'.

// The first argument after pluginfile.php is the context id.
$contextid = (int)array_shift($args);
if (!$contextid) {
    print_error('invalidcontext', 'local_watermark');
}

$context = context::instance_by_id($contextid, MUST_EXIST);

// Security checks.
require_login();
require_capability('moodle/course:view', $context);

// Build the relative file path (the rest of the arguments).
$relativepath = implode('/', $args);

// Fetch the file from Moodle file storage.
$fs = get_file_storage();
$file = $fs->get_file_by_hash(sha1($relativepath));
if (!$file || $file->is_directory()) {
    send_file_not_found();
}

// Serve the file (watermarked if PDF, otherwise original).
\local_watermark\service\watermark_service::serve($file, $USER);