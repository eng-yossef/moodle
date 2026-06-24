<?php
require_once(__DIR__ . '/../../config.php');

global $DB, $USER, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT); // course module ID

list($course, $cm) = get_course_and_cm_from_cmid($id, 'interview');
$interview = $DB->get_record('interview', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/interview:view', $context);

$PAGE->set_url('/mod/interview/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($interview->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/mod/interview/styles.css');
echo $OUTPUT->header();

$session = $DB->get_record('interview_sessions', [
    'interviewid' => $interview->id,
    'userid' => $USER->id
]);
if (!$session) {
    $status = 'not_started';
} else if ($session->status === 'completed') {
    $status = 'not_started'; // IMPORTANT FIX
} else {
    $status = 'in_progress';
}

$intro = format_module_intro('interview', $interview, $cm->id);

$data = [
    'cmid' => $cm->id,
    'intro' => $intro,
    'status' => $status,
    'sesskey' => sesskey(),
    'wwwroot' => $CFG->wwwroot
];

echo $OUTPUT->render_from_template('mod_interview/interview_main', $data);

$PAGE->requires->js_call_amd('mod_interview/interview', 'init', [$cm->id]);

echo $OUTPUT->footer();