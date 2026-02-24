<?php
require('../../../config.php');
require_login();

global $DB;

$postid = required_param('id', PARAM_INT);

$post = $DB->get_record_sql("
SELECT p.*, u.firstname, u.lastname
FROM {local_community_posts} p
JOIN {user} u ON u.id = p.userid
WHERE p.id = ?
", [$postid]);

$answers = $DB->get_records_sql("
SELECT a.*, u.firstname, u.lastname
FROM {local_community_answers} a
JOIN {user} u ON u.id = a.userid
WHERE a.postid = ?
ORDER BY a.timecreated ASC
", [$postid]);

echo json_encode([
    'post' => $post,
    'answers' => array_values($answers)
]);