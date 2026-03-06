<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

$matchid = required_param('matchid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$slot = required_param('slot', PARAM_INT);
$answer = required_param('answer', PARAM_RAW_TRIMMED);
$responsetime = required_param('responsetime', PARAM_INT);
require_sesskey();
require_login($courseid);

$context = context_course::instance($courseid);
require_capability('local/gamificationarena:play', $context);

if ($responsetime < 0 || $responsetime > 30) {
    throw new moodle_exception('Invalid response time.');
}

$result = \local_gamificationarena\local\match_manager::submit_answer($matchid, $USER->id, $slot, $answer, $responsetime);

header('Content-Type: application/json');
echo json_encode($result);
