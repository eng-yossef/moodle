<?php
require('../../../config.php');
require_login();

global $DB;

$posts = $DB->get_records_sql("
SELECT p.*, u.firstname, u.lastname
FROM {local_community_posts} p
JOIN {user} u ON u.id = p.userid
ORDER BY p.timecreated DESC
");

echo json_encode(array_values($posts));