<?php
require('../../../config.php');
require_login();

header('Content-Type: application/json');

global $DB;

$postid = required_param('id', PARAM_INT);

$post = $DB->get_record_sql("
    SELECT p.*, u.firstname, u.lastname,
           COALESCE(SUM(v.value), 0) AS votes,
           MAX(CASE WHEN v.userid = ? THEN v.value ELSE NULL END) AS uservote
    FROM {local_community_posts} p
    JOIN {user} u ON u.id = p.userid
    LEFT JOIN {local_community_votes} v ON v.postid = p.id
    WHERE p.id = ?
    GROUP BY p.id, u.firstname, u.lastname
", [$USER->id, $postid]);


if (!$post) {
    echo json_encode(['error' => 'Post not found']);
    exit;
    }
    $answers = $DB->get_records_sql("
        SELECT a.*, u.firstname, u.lastname,
               COALESCE(SUM(v.value), 0) AS votes,
               MAX(CASE WHEN v.userid = ? THEN v.value ELSE NULL END) AS uservote
        FROM {local_community_answers} a
        JOIN {user} u ON u.id = a.userid
        LEFT JOIN {local_community_votes} v ON v.answerid = a.id
        WHERE a.postid = ?
        GROUP BY a.id, u.firstname, u.lastname
        ORDER BY a.timecreated ASC
    ", [$USER->id, $postid]);
    


echo json_encode([
    'post' => $post,
    'answers' => array_values($answers)
]);
