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
 * Resource module view – CUSTOM version that forces watermarking.
 *
 * @package    mod_resource
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/resource/lib.php');
require_once($CFG->dirroot.'/mod/resource/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT); // Course Module ID.
$r        = optional_param('r', 0, PARAM_INT);  // Resource instance ID.
$redirect = optional_param('redirect', 0, PARAM_BOOL);
$forceview = optional_param('forceview', 0, PARAM_BOOL);

// Load resource and course module.
if ($r) {
    if (!$resource = $DB->get_record('resource', ['id' => $r])) {
        resource_redirect_if_migrated($r, 0);
        throw new \moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('resource', $resource->id, $resource->course, false, MUST_EXIST);
} else {
    if (!$cm = get_coursemodule_from_id('resource', $id)) {
        resource_redirect_if_migrated(0, $id);
        throw new \moodle_exception('invalidcoursemodule');
    }
    $resource = $DB->get_record('resource', ['id' => $cm->instance], '*', MUST_EXIST);
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/resource:view', $context);

// Trigger view event and handle completion.
resource_view($resource, $course, $cm, $context);

$PAGE->set_url('/mod/resource/view.php', ['id' => $cm->id]);

// Handle migrated resources.
if ($resource->tobemigrated) {
    resource_print_tobemigrated($resource, $cm, $course);
    exit;
}

// Get the main file for this resource.
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
if (count($files) < 1) {
    resource_print_filenotfound($resource, $cm, $course);
    exit;
}
$file = reset($files);
unset($files);

$resource->mainfile = $file->get_filename();
$displaytype = resource_get_final_display_type($resource);

// Determine if we should redirect (open/download display types).
if ($displaytype == RESOURCELIB_DISPLAY_OPEN || $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD) {
    $redirect = true;
}

// Don't redirect teachers who need to access settings.
if ($redirect && !course_get_format($course)->has_view_page() &&
        (has_capability('moodle/course:manageactivities', $context) ||
        has_capability('moodle/course:update', context_course::instance($course->id)))) {
    $redirect = false;
}

// ========== WATERMARK FORCE ==========
// 🔧 FIX 1: Use $file->get_itemid() for accurate file lookup (not $resource->revision).
// 🔧 FIX 2: Build path correctly - filepath already ends with '/', filename has no leading slash.
$itemid = $file->get_itemid();
$filepath = $file->get_filepath(); // e.g., '/subfolder/'
$filename = $file->get_filename(); // e.g., 'file.pdf'
$path = '/' . $context->id . '/mod_resource/content/' . $itemid . $filepath . $filename;

// Create the original pluginfile.php URL.
$originalurl = moodle_url::make_file_url('/pluginfile.php', $path, $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD);
$originalurl = $originalurl->out(false); // Get absolute URL string.

// 🔧 FIX 3: Use rawurlencode() to ensure spaces become %20 (not +), matching download.php decoder.
$watermarkedurl = new moodle_url('/local/watermark/download.php', ['file' => rawurlencode($originalurl)]);
$watermarkedurl = $watermarkedurl->out(false);
// =====================================

// Optional debug logging (remove in production):
// error_log("Watermark: original=$originalurl");
// error_log("Watermark: watermarked=$watermarkedurl");

if ($redirect && !$forceview) {
    // Redirect to watermarked URL instead of direct pluginfile.php.
    redirect($watermarkedurl);
}

// Render based on display type, using watermarked URL.
switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        $fullurl = $watermarkedurl;
        $options = ['embed' => true, 'mod_resource_data' => $resource];
        echo $OUTPUT->header();
        echo '<div class="resourcecontent resourcepdf">';
        echo \core\output\file_embed::embed($fullurl, $resource->name, $options);
        echo '</div>';
        echo $OUTPUT->footer();
        break;

    case RESOURCELIB_DISPLAY_FRAME:
        $frameurl = $watermarkedurl;
        $title = format_string($resource->name);
        $PAGE->set_title($title);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo '<div class="resourceworkaround">';
        echo '<iframe id="resourceframe" src="' . s($frameurl) . '" width="100%" height="600" title="' . s($title) . '" loading="lazy"></iframe>';
        echo '</div>';
        echo $OUTPUT->footer();
        break;

    default:
        // Fallback: redirect to watermarked download URL.
        redirect($watermarkedurl);
        break;
}