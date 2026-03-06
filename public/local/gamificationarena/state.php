<?php
/**
 * AJAX State provider for Gamification Arena.
 *
 * @package    local_gamificationarena
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

header('Content-Type: application/json');

try {

    // -----------------------------
    // 1. Validate parameters
    // -----------------------------
    $matchid  = required_param('matchid', PARAM_INT);
    $courseid = required_param('courseid', PARAM_INT);

    if (!confirm_sesskey(optional_param('sesskey', '', PARAM_ALPHANUM))) {
        throw new moodle_exception('invalidsesskey', 'error');
    }

    require_login($courseid);

    $context = context_course::instance($courseid);
    require_capability('local/gamificationarena:play', $context);

    global $USER;

    // -----------------------------
    // 2. Fetch match state
    // -----------------------------
    $state = \local_gamificationarena\local\match_manager::get_match_state(
        $matchid,
        $USER->id
    );

    if (!$state) {

        echo json_encode([
            'error' => 'Match not found',
            'state' => null
        ]);
        exit;
    }

    // -----------------------------
    // 3. Return state payload
    // -----------------------------
    echo json_encode([
        'state' => $state
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'error' => $e->getMessage(),
        'state' => null
    ]);
}

exit;