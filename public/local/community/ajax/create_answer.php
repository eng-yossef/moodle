<?php
require('../../../config.php');
require_login();

global $DB, $USER;

$data = json_decode(file_get_contents("php://input"));

$answer = new stdClass();
$answer->postid = $data->postid;
$answer->userid = $USER->id;
$answer->content = $data->content;
$answer->timecreated = time();

$DB->insert_record('local_community_answers', $answer);

echo json_encode(['status' => 'ok']);