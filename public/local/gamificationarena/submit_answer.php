<?php
/**
 * Ajax endpoint to submit a question answer.
 *
 * @package    local_gamificationarena
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

use local_gamificationarena\local\match_manager;

// 1. Parameter cleaning.
$courseid     = required_param('courseid', PARAM_INT);
$matchid      = required_param('matchid', PARAM_INT);
$slot         = required_param('slot', PARAM_INT);
$answer       = optional_param('answer', '', PARAM_TEXT);
$responsetime = required_param('responsetime', PARAM_INT);

// 2. Security checks.
require_login($courseid);
require_sesskey();

header('Content-Type: application/json');

try {
    // 3. Process the submission via the match manager.
    $result = match_manager::submit_answer(
        $matchid,
        $USER->id,
        $slot,
        $answer,
        $responsetime
    );

    echo json_encode([
        'success' => true,
        'result'  => $result
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}