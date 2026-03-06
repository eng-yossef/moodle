<?php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
require_sesskey();
require_login($courseid);

$context = context_course::instance($courseid);
require_capability('local/gamificationarena:play', $context);

$result = \local_gamificationarena\local\match_manager::queue_player($courseid, $USER->id);

echo $OUTPUT->header();
redirect(new moodle_url('/local/gamificationarena/match.php', ['courseid' => $courseid, 'matchid' => $result['matchid']]));
echo $OUTPUT->footer();
