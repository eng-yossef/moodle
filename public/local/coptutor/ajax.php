<?php
define('AJAX_SCRIPT', true);

require('../../config.php');
require_login();

header('Content-Type: application/json');

global $USER, $DB;

// ── 1. Read and validate incoming JSON ──────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw);

if ($data === null) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$question = isset($data->message)  ? trim($data->message) : '';
$courseid = isset($data->courseid) ? (int)$data->courseid  : 0;
$fileid   = isset($data->fileid)   ? (int)$data->fileid    : 0;

if ($question === '' || $courseid === 0) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// ── 2. Course access check ──────────────────────────────────────────────────
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$coursecontext = context_course::instance($courseid);
require_capability('moodle/course:view', $coursecontext);

// ── 3. Conversation history (last 2 Q&A) ────────────────────────────────────
$records = $DB->get_records(
    'local_coptutor_qa',
    ['userid' => $USER->id, 'courseid' => $courseid],
    'timecreated ASC'
);

$lastqa  = array_slice($records, -2, 2, true);
$history = '';
foreach ($lastqa as $r) {
    $history .= "Q: {$r->question}\nA: {$r->answer}\n\n";
}

// ── 4. Course context (metadata) ────────────────────────────────────────────
$contextinfo = "Course: {$course->fullname}\n"
             . "Shortname: {$course->shortname}\n"
             . "Summary: " . strip_tags($course->summary);

// ── 5. Extract material content (resource or page) ──────────────────────────
$file_text     = '';      // textual representation sent to the AI
$file_name     = '';
$file_mimetype = '';
$file_bytes    = null;    // raw binary (used only for non‑text uploads)

if ($fileid > 0) {
    // Get the course module without filtering by type
    $cm = get_coursemodule_from_id('', $fileid, 0, false, MUST_EXIST);
    // Verify the module belongs to the requested course
    if ($cm->course != $courseid) {
        echo json_encode(['error' => 'Module does not belong to this course']);
        exit;
    }
    // Check user can view the module
    $modcontext = context_module::instance($cm->id);
    require_capability('moodle/course:view', $modcontext);

    $file_name = $cm->name;   // human‑readable name

    if ($cm->modname === 'resource') {
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $modcontext->id,
            'mod_resource',
            'content',
            false,
            'filename',
            false
        );

        foreach ($files as $storedfile) {
            if ($storedfile->get_filesize() === 0) {
                continue;
            }
            $file_name     = $storedfile->get_filename();
            $file_mimetype = $storedfile->get_mimetype();

            if (strpos($file_mimetype, 'text/') === 0) {
                // Plain‑text file: read and use as text context
                $file_text = $storedfile->get_content();
                $file_bytes = null;   // no need for binary upload
            } else {
                // Non‑text file: keep the binary for upload, and optionally add a textual note
                $file_bytes = $storedfile->get_content();
                $file_text  = "[Binary file: {$file_name} (type: {$file_mimetype})]";
            }
            break;
        }
    } elseif ($cm->modname === 'page') {
        // Page module: extract HTML content and strip tags
        $page = $DB->get_record('page', ['id' => $cm->instance], 'content', MUST_EXIST);
        $file_text = strip_tags($page->content);
        $file_mimetype = 'text/plain';   // we only send text to the AI
    } else {
        // Unexpected module type – gracefully fail
        echo json_encode(['error' => 'Unsupported module type']);
        exit;
    }

    // Append the material text to the main context (AI sees this)
    if (!empty($file_text)) {
        $contextinfo .= "\n\n--- Material: {$file_name} ---\n" . mb_substr($file_text, 0, 5000);
    }
}

// ── 6. Send request to FastAPI ──────────────────────────────────────────────
$apiurl = 'http://127.0.0.1:8000/ask';
$ch = curl_init($apiurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_POST, true);

// Decide how to send the file (only if the AI endpoint expects a raw file)
if ($file_bytes !== null) {
    // Binary file: send via multipart upload
    $tmppath = tempnam(sys_get_temp_dir(), 'moodle_coptutor_');
    file_put_contents($tmppath, $file_bytes);

    $postfields = [
        'question' => $question,
        'context'  => $contextinfo,
        'history'  => $history,
        'file'     => new CURLFile($tmppath, $file_mimetype, $file_name),
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    // curl sets multipart/form-data automatically
} else {
    // Text‑only or no file: standard URL‑encoded POST
    $postfields = http_build_query([
        'question' => $question,
        'context'  => $contextinfo,
        'history'  => $history,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
}

$response = curl_exec($ch);
$curl_err = curl_error($ch);
curl_close($ch);

if (isset($tmppath) && file_exists($tmppath)) {
    unlink($tmppath);
}

if ($response === false || $curl_err) {
    error_log('local_coptutor: cURL error: ' . $curl_err);
    echo json_encode(['error' => 'Failed to connect to AI service']);
    exit;
}

// ── 7. Decode and store Q&A ─────────────────────────────────────────────────
$response_data = json_decode($response, true);
if ($response_data && isset($response_data['reply'])) {
    $record = new stdClass();
    $record->userid      = $USER->id;
    $record->courseid    = $courseid;
    $record->question    = $question;
    $record->answer      = $response_data['reply'];
    $record->timecreated = time();
    $DB->insert_record('local_coptutor_qa', $record);
} else {
    error_log('local_coptutor: Invalid API response: ' . $response);
}

// ── 8. Return the AI reply to the frontend ──────────────────────────────────
echo $response;