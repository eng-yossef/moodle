<?php
/**
 * materials.php  –  Returns a JSON array of course material files.
 *
 * Each item:  { "id": <cmid>, "name": "<display name>" }
 *
 * "id" is the course-module id (cmid) for the resource activity.
 * The JS sends this back as `fileid` in the chat POST request and
 * ajax.php uses get_coursemodule_from_id('resource', $fileid) to
 * retrieve the actual stored file.
 *
 * Usage:  GET /local/coptutor/materials.php?courseid=<id>
 */

define('AJAX_SCRIPT', true);

require('../../config.php');
require_login();

header('Content-Type: application/json');

global $DB;

$courseid = required_param('courseid', PARAM_INT);

// Verify the user has access to this course
$context = context_course::instance($courseid, MUST_EXIST);
require_capability('moodle/course:view', $context);

// Fetch all visible 'resource' (File) modules in the course
$sql = "
    SELECT cm.id        AS cmid,
           r.name       AS name
      FROM {course_modules} cm
      JOIN {modules}         m  ON m.id  = cm.module
      JOIN {resource}        r  ON r.id  = cm.instance
     WHERE cm.course   = :courseid
       AND m.name      = 'resource'
       AND cm.visible  = 1
  ORDER BY r.name ASC
";

$rows = $DB->get_records_sql($sql, ['courseid' => $courseid]);

$materials = [];
foreach ($rows as $row) {
    $materials[] = [
        'id'   => (int)$row->cmid,
        'name' => $row->name,
    ];
}

echo json_encode($materials);