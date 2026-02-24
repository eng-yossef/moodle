<?php
require('../../../config.php');
require_login();

global $DB, $USER;

$data = json_decode(file_get_contents("php://input"));

$vote = new stdClass();
$vote->userid = $USER->id;
$vote->postid = $data->postid ?? null;
$vote->answerid = $data->answerid ?? null;
$vote->value = $data->value;

$DB->insert_record('local_community_votes', $vote);

echo json_encode(['status' => 'ok']);