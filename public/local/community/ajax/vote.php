<?php
require('../../../config.php');
require_login();

header('Content-Type: application/json');

global $DB, $USER;

// Read incoming JSON
$data = json_decode(file_get_contents("php://input"));

$postid   = isset($data->postid) ? (int)$data->postid : null;
$answerid = isset($data->answerid) ? (int)$data->answerid : null;
$value    = isset($data->value) ? (int)$data->value : 0;

if (!$value || (!$postid && !$answerid)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid vote data']);
    exit;
}

// Check if user already voted
$conditions = ['userid' => $USER->id];
if ($postid) {
    $conditions['postid'] = $postid;
}
if ($answerid) {
    $conditions['answerid'] = $answerid;
}

$existing = $DB->get_record('local_community_votes', $conditions);

if ($existing) {
    // Update existing vote
    $oldvalue = $existing->value;
    $existing->value = $value;
    $DB->update_record('local_community_votes', $existing);

    // Adjust cached count
    if ($postid) {
        $DB->execute("UPDATE {local_community_posts} SET votes = votes - ? + ? WHERE id = ?", [$oldvalue, $value, $postid]);
    }
    if ($answerid) {
        $DB->execute("UPDATE {local_community_answers} SET votes = votes - ? + ? WHERE id = ?", [$oldvalue, $value, $answerid]);
    }
} else {
    // Insert new vote
    $vote = new stdClass();
    $vote->userid   = $USER->id;
    $vote->postid   = $postid;
    $vote->answerid = $answerid;
    $vote->value    = $value;
    $vote->timecreated = time();
    $DB->insert_record('local_community_votes', $vote);

    // Increment cached count
    if ($postid) {
        $DB->execute("UPDATE {local_community_posts} SET votes = votes + ? WHERE id = ?", [$value, $postid]);
    }
    if ($answerid) {
        $DB->execute("UPDATE {local_community_answers} SET votes = votes + ? WHERE id = ?", [$value, $answerid]);
    }
}

echo json_encode(['status' => 'ok']);
