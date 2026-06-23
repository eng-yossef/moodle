<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/interview/locallib.php');

header('Content-Type: application/json; charset=utf-8');

global $DB, $USER;

try {
    $action = required_param('action', PARAM_ALPHANUMEXT);
    $cmid   = required_param('cmid', PARAM_INT);

    $context = context_module::instance($cmid);
    require_login();
    require_capability('mod/interview:view', $context);
    require_sesskey();

    $cm = get_coursemodule_from_id('interview', $cmid, 0, false, MUST_EXIST);
    $interview = $DB->get_record('interview', ['id' => $cm->instance], '*', MUST_EXIST);

    $apiurl = interview_get_apiurl($interview);

    if ($action === 'start') {
        if (empty($_FILES['cvfile']) || $_FILES['cvfile']['error'] !== UPLOAD_ERR_OK) {
            throw new moodle_exception('nocvfile', 'mod_interview');
        }

        $file = $_FILES['cvfile'];

        $result = interview_api_start($apiurl, $file['tmp_name'], $file['name']);

        $record = new stdClass();
        $record->interviewid = $interview->id;
        $record->userid = $USER->id;
        $record->session_id = $result['session_id'];
        $record->status = 'in_progress';
        $record->timecreated = time();
        $record->timemodified = time();

        $DB->insert_record('interview_sessions', $record);

        echo json_encode([
            'status' => 'in_progress',
            'question' => $result['first_question'] ?? ''
        ]);
        exit;
    }

    if ($action === 'answer') {
        $answer = required_param('answer', PARAM_TEXT);

        $session = $DB->get_record('interview_sessions', [
            'interviewid' => $interview->id,
            'userid' => $USER->id
        ]);

        if (!$session) {
            throw new moodle_exception('nosession', 'mod_interview');
        }

        if ($session->status !== 'in_progress') {
            throw new moodle_exception('nosession', 'mod_interview');
        }

        $data = interview_api_answer($apiurl, $session->session_id, $answer);

       if (!empty($data['is_finished'])) {

    $response['status'] = 'completed';
    $response['evaluation'] = $data['final_evaluation'] ?? null;

    // 1. END SESSION IN FASTAPI (IMPORTANT FIX)
    try {
        interview_api_end($apiurl, $session->session_id);
    } catch (Exception $e) {
        error_log("Failed to end FastAPI session: " . $e->getMessage());
    }

    // 2. UPDATE OR DELETE LOCAL SESSION
    $DB->set_field(
        'interview_sessions',
        'status',
        'completed',
        ['id' => $session->id]
    );

    $DB->set_field(
        'interview_sessions',
        'timemodified',
        time(),
        ['id' => $session->id]
    );
}

        echo json_encode([
            'status' => 'in_progress',
            'question' => $data['next_question'] ?? ''
        ]);
        exit;
    }

    if ($action === 'state') {
        $session = $DB->get_record('interview_sessions', [
            'interviewid' => $interview->id,
            'userid' => $USER->id
        ]);

        if (!$session) {
            echo json_encode(['status' => 'not_started']);
            exit;
        }

        echo json_encode(['status' => $session->status]);
        exit;
    }

    if ($action === 'end') {
        $session = $DB->get_record('interview_sessions', [
            'interviewid' => $interview->id,
            'userid' => $USER->id
        ]);

        if ($session) {
            interview_api_end($apiurl, $session->session_id);
            $DB->delete_records('interview_sessions', ['id' => $session->id]);
        }

        echo json_encode(['status' => 'ended']);
        exit;
    }

    throw new moodle_exception('invalidaction', 'mod_interview');

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
    exit;
}