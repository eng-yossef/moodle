<?php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$period = optional_param('period', 'alltime', PARAM_ALPHA);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/gamificationarena:play', $context);

$periodkey = $period === 'week' ? date('o-W') : 'all';
$records = $DB->get_records('local_ga_leaderboard', ['courseid' => $courseid, 'period' => $period, 'periodkey' => $periodkey], 'xp DESC', '*', 0, 20);

$rows = [];
$rank = 1;
foreach ($records as $record) {
    $rows[] = [
        'rank' => $rank++,
        'name' => fullname(core_user::get_user($record->userid)),
        'xp' => $record->xp,
        'wins' => $record->wins,
        'losses' => $record->losses,
    ];
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gamificationarena/leaderboard.php', ['courseid' => $courseid, 'period' => $period]));
$PAGE->set_title(get_string('leaderboard', 'local_gamificationarena'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_gamificationarena/leaderboard', [
    'rows' => $rows,
    'courseid' => $courseid,
    'alltimeurl' => (new moodle_url('/local/gamificationarena/leaderboard.php', ['courseid' => $courseid, 'period' => 'alltime']))->out(false),
    'weekurl' => (new moodle_url('/local/gamificationarena/leaderboard.php', ['courseid' => $courseid, 'period' => 'week']))->out(false),
]);
echo $OUTPUT->footer();
