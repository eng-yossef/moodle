<?php
define('AJAX_SCRIPT', true);
require('../../config.php');
require_login();

header('Content-Type: application/json');

$raw = file_get_contents("php://input");
$data = json_decode($raw);

$question = isset($data->message) ? $data->message : '';
$courseid = isset($data->courseid) ? (int)$data->courseid : 0;

// Call FastAPI
$apiurl = "http://127.0.0.1:8000/ask?q=" . urlencode($question);
$response = @file_get_contents($apiurl);

if ($response === false) {
    echo json_encode(["reply" => "Error: AI service is offline."]);
    exit;
}

$decoded = json_decode($response, true);

// CHECK: If FastAPI returned a dictionary with 'reply', use it.
// If it returned a plain string, use the string itself.
if (is_array($decoded) && isset($decoded['reply'])) {
    $final_reply = $decoded['reply'];
} else {
    // This handles the case where FastAPI returns a raw string
    $final_reply = is_string($decoded) ? $decoded : $response;
}

echo json_encode(["reply" => $final_reply]);