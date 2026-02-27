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

// Insert into Moodle DB
$DB->insert_record('local_community_posts', $post);

// Notify FastAPI to sync
$ch = curl_init("http://127.0.0.1:8000/sync");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode !== 200) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to sync with external service']);
    exit;
}

echo json_encode(['status' => 'ok']);
