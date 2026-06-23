<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Get the API base URL for a given interview instance.
 *
 * @param stdClass $interview
 * @return string
 */
function interview_get_apiurl($interview) {
    $apiurl = empty($interview->apiurl)
        ? get_config('mod_interview', 'apiurl')
        : $interview->apiurl;

    return rtrim((string)$apiurl, '/');
}

/**
 * Decode JSON safely.
 *
 * @param string $response
 * @param string $context
 * @return array
 * @throws moodle_exception
 */
function interview_decode_json_response($response, $context) {
    $data = json_decode($response, true);

    if (!is_array($data)) {
        error_log("[interview][$context] Invalid JSON response:");
        error_log(substr((string)$response, 0, 4000));

        throw new moodle_exception(
            'error_api',
            'mod_interview',
            '',
            'Invalid JSON returned by interview service.'
        );
    }

    if (isset($data['detail']) && !isset($data['session_id']) && !isset($data['next_question'])) {
        error_log("[interview][$context] API returned detail:");
        error_log(json_encode($data));

        throw new moodle_exception(
            'error_api',
            'mod_interview',
            '',
            is_string($data['detail']) ? $data['detail'] : 'Interview service returned an error.'
        );
    }

    return $data;
}

/**
 * Start a new interview session for the given user.
 *
 * Expects FastAPI endpoint:
 * POST /interview/start
 * multipart form field name: file
 *
 * @param string $apiurl
 * @param string $filepath
 * @param string $filename
 * @return array
 * @throws moodle_exception
 */
function interview_api_start($apiurl, $filepath, $filename) {
    $url = rtrim($apiurl, '/') . '/interview/start';
    $curl = new curl();

    $mime = 'application/octet-stream';
    if (function_exists('mime_content_type')) {
        $detected = @mime_content_type($filepath);
        if (!empty($detected)) {
            $mime = $detected;
        }
    }

    $postfields = [
        'file' => new CURLFile($filepath, $mime, $filename),
    ];

    error_log('[interview] START request => ' . $url);
    error_log('[interview] START file => ' . $filename . ' | mime=' . $mime);

    $response = $curl->post($url, $postfields);

    if (method_exists($curl, 'get_errno') && $curl->get_errno()) {
        error_log('[interview] START curl errno => ' . $curl->get_errno());
        throw new moodle_exception(
            'error_api',
            'mod_interview',
            '',
            'Error communicating with interview service.'
        );
    }

    $data = interview_decode_json_response($response, 'start');

    if (empty($data['session_id']) || empty($data['first_question'])) {
        error_log('[interview][start] Unexpected response:');
        error_log(json_encode($data));

        throw new moodle_exception(
            'error_api',
            'mod_interview',
            '',
            'Interview service returned an unexpected response.'
        );
    }

    return $data;
}

/**
 * Submit an answer and get next question / final evaluation.
 *
 * Expects FastAPI endpoint:
 * POST /interview/{session_id}/answer
 * JSON body: {"answer": "..."}
 *
 * @param string $apiurl
 * @param string $session_id
 * @param string $answer
 * @return array
 * @throws moodle_exception
 */
function interview_api_answer($apiurl, $session_id, $answer) {
    $url = rtrim($apiurl, '/') . '/interview/' . rawurlencode($session_id) . '/answer';
    $curl = new curl();

    $payload = json_encode([
        'answer' => $answer,
    ], JSON_UNESCAPED_UNICODE);

    error_log('[interview] ANSWER request => ' . $url);
    error_log('[interview] ANSWER payload => ' . $payload);

    $response = $curl->post($url, $payload, [
        'CURLOPT_HTTPHEADER' => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);

    if (method_exists($curl, 'get_errno') && $curl->get_errno()) {
        error_log('[interview] ANSWER curl errno => ' . $curl->get_errno());
        throw new moodle_exception(
            'error_api',
            'mod_interview',
            '',
            'Error communicating with interview service.'
        );
    }

    $data = interview_decode_json_response($response, 'answer');

    return $data;
}

/**
 * End session (cleanup external side).
 *
 * Expects FastAPI endpoint:
 * DELETE /interview/{session_id}
 *
 * @param string $apiurl
 * @param string $session_id
 * @return bool
 * @throws moodle_exception
 */
function interview_api_end($apiurl, $session_id) {
    $url = rtrim($apiurl, '/') . '/interview/' . rawurlencode($session_id);
    $curl = new curl();

    error_log('[interview] END request => ' . $url);

    $response = $curl->delete($url);

    if (method_exists($curl, 'get_errno') && $curl->get_errno()) {
        error_log('[interview] END curl errno => ' . $curl->get_errno());
        throw new moodle_exception(
            'error_api',
            'mod_interview',
            '',
            'Error communicating with interview service.'
        );
    }

    if ($response !== '' && $response !== null) {
        error_log('[interview] END response => ' . substr((string)$response, 0, 2000));
    }

    return true;
}