<?php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$matchid  = optional_param('matchid', 0, PARAM_INT); // FIXED

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/gamificationarena:play', $context);

// Run queue logic to ensure proper matchmaking
$match_data = \local_gamificationarena\local\match_manager::queue_player($courseid, $USER->id);
$matchid = $match_data['matchid'];

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gamificationarena/match.php', ['courseid' => $courseid, 'matchid' => $matchid]));
$PAGE->set_title(get_string('battle', 'local_gamificationarena'));
$PAGE->set_heading(format_string($course->fullname));

// JS initialization
$PAGE->requires->js_call_amd('local_gamificationarena/battle', 'init', [
    'matchid' => (int)$matchid,
    'courseid' => (int)$courseid,
    'sesskey' => sesskey(),
], true); // last argument = true -> run after DOM ready

$templatecontext = [
    'matchid' => $matchid,
    'courseid' => $courseid,
    'queueing' => get_string('queueing', 'local_gamificationarena'),
    'leaderboardurl' => (new moodle_url('/local/gamificationarena/leaderboard.php', ['courseid' => $courseid]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_gamificationarena/battle', $templatecontext);
echo $OUTPUT->footer();