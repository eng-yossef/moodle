<?php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/gamificationarena:play', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gamificationarena/index.php', ['courseid' => $courseid]));
$PAGE->set_title(get_string('pluginname', 'local_gamificationarena'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->js_call_amd('local_gamificationarena/lobby', 'init', ['courseid' => $courseid]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_gamificationarena/lobby', (new \local_gamificationarena\output\lobby_page($courseid))->export_for_template($OUTPUT));
echo $OUTPUT->footer();
