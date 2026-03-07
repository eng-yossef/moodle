<?php
// local/gamificationarena/state.php
require_once(__DIR__ . '/../../config.php');

// 1. Get and validate parameters (FIX: 3 args for optional_param)
$courseid = required_param('courseid', PARAM_INT);
$matchid  = optional_param('matchid', 0, PARAM_INT); 

// 2. Basic course/login checks
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/gamificationarena:play', $context);

// 3. If no matchid provided, try to get one from queue (fallback)
if (!$matchid) {
    $match_data = \local_gamificationarena\local\match_manager::queue_player($courseid, $USER->id);
    $matchid = $match_data['matchid'];
}

// 4. 🔐 Security: Verify user is a participant in this match
// Using your actual table name: local_ga_match_players
global $DB;
$is_participant = $DB->record_exists('local_ga_match_players', [
    'matchid' => $matchid,
    'userid' => $USER->id
]);

if (!$is_participant) {
    // Return JSON error (API endpoint)
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid match access']);
    exit;
}

// 5. Return JSON response only (API endpoint - NO HTML)
header('Content-Type: application/json');

try {
    // This method already validates match existence via MUST_EXIST
    $state = \local_gamificationarena\local\match_manager::get_match_state($matchid, $USER->id);
    echo json_encode(['state' => $state]);
} catch (dml_missing_record_exception $e) {
    http_response_code(404);
    echo json_encode(['error' => 'Match not found']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Critical: Stop execution after JSON output
exit;