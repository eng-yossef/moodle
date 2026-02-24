<?php
require('../../../config.php');
require_login();

header('Content-Type: application/json');

global $DB, $USER;

// Read incoming JSON
$data = json_decode(file_get_contents("php://input"));

// Validate input
$postid = isset($data->postid) ? (int)$data->postid : 0;
$content = isset($data->content) ? clean_param($data->content, PARAM_RAW) : '';

if ($postid <= 0 || empty($content)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// Build answer record
$answer = new stdClass();
$answer->postid = $postid;
$answer->userid = $USER->id;
$answer->content = $content; // HTML from editor
$answer->timecreated = time();
$answer->votes = 0; // default votes field

// Insert into DB
$DB->insert_record('local_community_answers', $answer);

echo json_encode(['status' => 'ok']);
