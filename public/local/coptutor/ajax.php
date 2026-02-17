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

/* 1️⃣ Get user history from DB */
$records = $DB->get_records('local_coptutor_qa', [
    'userid' => $USER->id,
    'courseid' => $courseid
], 'timecreated ASC'); // order by time

$history = '';
foreach ($records as $r) {
    $history .= "Q: {$r->question}\nA: {$r->answer}\n\n";
}

/* 2️⃣ Prepare POST for FastAPI */
$postdata = http_build_query([
    'question' => $question,
    'context'  => "Course ID: " . $courseid,
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
$response = @file_get_contents($apiurl, false, stream_context_create($options));

/* 3️⃣ Handle API failure */
if ($response === false) {
    echo json_encode(["reply" => "⚠️ FastAPI service is offline"]);
    exit;
}

/* 4️⃣ Decode response */
$result = json_decode($response, true);
$reply = $result['reply'] ?? 'No reply from AI';

/* 5️⃣ Save QA into Moodle DB */
$record = new stdClass();
$record->userid = $USER->id;
$record->courseid = $courseid;
$record->question = $question;
$record->answer = $reply;
$record->timecreated = time();

$DB->insert_record('local_coptutor_qa', $record);

/* 6️⃣ Return to JS */
echo json_encode(["reply" => $reply]);
