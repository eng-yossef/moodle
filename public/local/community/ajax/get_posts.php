<?php
require('../../../config.php');
require_login();

global $DB;

$posts = $DB->get_records_sql("
SELECT p.*, u.firstname, u.lastname,
           COALESCE(SUM(v.value), 0) AS votes
FROM {local_community_posts} p
JOIN {user} u ON u.id = p.userid
    LEFT JOIN {local_community_votes} v ON v.postid = p.id
        GROUP BY p.id, p.title, p.content, p.timecreated, u.firstname, u.lastname
ORDER BY p.timecreated DESC
");

echo json_encode(array_values($posts));