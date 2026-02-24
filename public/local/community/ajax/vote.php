<?php
require('../../../config.php');
require_login();

header('Content-Type: application/json');

global $DB, $USER;

// Read incoming JSON
$data = json_decode(file_get_contents("php://input"));

// Validate input
$postid   = isset($data->postid) ? (int)$data->postid : null;
$answerid = isset($data->answerid) ? (int)$data->answerid : null;
$value    = isset($data->value) ? (int)$data->value : 0;

if (!$value || (!$postid && !$answerid)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid vote data']);
    exit;
}

// Build vote record
$vote = new stdClass();
$vote->userid   = $USER->id;
$vote->postid   = $postid;
$vote->answerid = $answerid;
$vote->value    = $value;
$vote->timecreated = time();

// Insert vote record
$DB->insert_record('local_community_votes', $vote);

// Update cached vote count in posts/answers table
if ($postid) {
    $DB->execute("UPDATE {local_community_posts} SET votes = votes + ? WHERE id = ?", [$value, $postid]);
}
if ($answerid) {
    $DB->execute("UPDATE {local_community_answers} SET votes = votes + ? WHERE id = ?", [$value, $answerid]);
}

echo json_encode(['status' => 'ok']);
