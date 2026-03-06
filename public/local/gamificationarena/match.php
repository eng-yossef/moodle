<?php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$matchid = required_param('matchid', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/gamificationarena:play', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gamificationarena/match.php', ['courseid' => $courseid, 'matchid' => $matchid]));
$PAGE->set_title(get_string('battle', 'local_gamificationarena'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->js_call_amd('local_gamificationarena/battle', 'init', [
    'matchid' => $matchid,
    'courseid' => $courseid,
    'sesskey' => sesskey(),
]);

$templatecontext = [
    'matchid' => $matchid,
    'courseid' => $courseid,
    'queueing' => get_string('queueing', 'local_gamificationarena'),
    'leaderboardurl' => (new moodle_url('/local/gamificationarena/leaderboard.php', ['courseid' => $courseid]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_gamificationarena/battle', $templatecontext);
echo $OUTPUT->footer();
