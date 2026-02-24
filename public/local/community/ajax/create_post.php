<?php
require('../../../config.php');
require_login();

global $DB, $USER;

$data = json_decode(file_get_contents("php://input"));

$post = new stdClass();
$post->userid = $USER->id;
$post->title = $data->title;
$post->content = $data->content;
$post->posttype = $data->posttype;
$post->timecreated = time();

$DB->insert_record('local_community_posts', $post);

echo json_encode(['status' => 'ok']);