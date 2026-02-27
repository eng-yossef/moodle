<?php
require('../../../config.php');
require_login();
require_sesskey();

header('Content-Type: application/json');

global $DB, $USER;

$answerid = required_param('answerid', PARAM_INT);

$answer = $DB->get_record('local_community_answers', [
    'id' => $answerid,
    'userid' => $USER->id
]);

if (!$answer) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Permission denied'
    ]);
    die();
}

$transaction = $DB->start_delegated_transaction();

$DB->delete_records('local_community_votes', ['answerid' => $answerid]);
$DB->delete_records('local_community_answers', ['id' => $answerid]);

$transaction->allow_commit();

echo json_encode(['status' => 'success']);
die();