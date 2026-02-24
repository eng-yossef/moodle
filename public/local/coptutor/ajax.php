<?php
define('AJAX_SCRIPT', true);

require('../../config.php');
require_login();

header('Content-Type: application/json');

global $USER, $DB;

// Read incoming JSON from JS
$data = json_decode(file_get_contents("php://input"));

$question = $data->message ?? '';
$courseid = $data->courseid ?? 0;

/* 1️⃣ Get user history from DB (ordered oldest → newest) */
$records = $DB->get_records('local_coptutor_qa', [
    'userid' => $USER->id,
    'courseid' => $courseid
], 'timecreated ASC');

/* 2️⃣ Keep only the last 2 Q&A */
$lastqa = array_slice($records, -2, 2, true);

$history = '';
foreach ($lastqa as $r) {
    $history .= "Q: {$r->question}\nA: {$r->answer}\n\n";
}

/* 3️⃣ Get course info for context */
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$contextinfo = "Course: {$course->fullname}\nShortname: {$course->shortname}\nID: {$course->id}\nSummary: " . strip_tags($course->summary);

/* 4️⃣ Prepare POST for FastAPI */
$postdata = http_build_query([
    'question' => $question,
    'context'  => $contextinfo,
    'history'  => $history
]);

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded",
        'content' => $postdata,
        'timeout' => 60
    ]
];

$apiurl = "http://127.0.0.1:8000/ask";
$context  = stream_context_create($options);
$response = file_get_contents($apiurl, false, $context);

/* 5️⃣ Return JSON back to JS */
echo $response;
