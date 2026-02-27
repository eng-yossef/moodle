<?php
require('../../../config.php');
require_login();

global $USER;

$posts = $DB->get_records_sql("
SELECT p.id,
       p.userid,
       p.title,
       u.firstname,
       u.lastname,
       COALESCE(SUM(DISTINCT v.value),0) AS votes,
       COUNT(DISTINCT a.id) AS answers
FROM {local_community_posts} p
JOIN {user} u ON u.id = p.userid
LEFT JOIN {local_community_votes} v ON v.postid = p.id
LEFT JOIN {local_community_answers} a ON a.postid = p.id
GROUP BY p.id, p.userid, p.title, u.firstname, u.lastname
ORDER BY p.timecreated DESC
");

foreach ($posts as $p) {
    $p->can_delete =
        ($p->userid == $USER->id) ||
        is_siteadmin();
}

echo json_encode(array_values($posts));