<?php
require('../../../config.php');
require_login();
global $DB;

header('Content-Type: application/json');

$period = optional_param('period', 'all', PARAM_ALPHANUMEXT);
$timefilter = '';
$params = [];

if ($period === '30d') {
    $timefilter = 'WHERE rl.timecreated >= :mintime';
    $params['mintime'] = time() - (30 * DAYSECS);
} else if ($period === '7d') {
    $timefilter = 'WHERE rl.timecreated >= :mintime';
    $params['mintime'] = time() - (7 * DAYSECS);
}

$sql = "
SELECT
    u.id,
    u.firstname,
    u.lastname,
    COALESCE(SUM(rl.points), 0) AS points,
    COUNT(DISTINCT p.id) AS posts,
    COUNT(DISTINCT a.id) AS answers
FROM {user} u
LEFT JOIN {local_community_rep_log} rl ON rl.userid = u.id
LEFT JOIN {local_community_posts} p ON p.userid = u.id
LEFT JOIN {local_community_answers} a ON a.userid = u.id
{$timefilter}
GROUP BY u.id, u.firstname, u.lastname
HAVING COALESCE(SUM(rl.points), 0) > 0
ORDER BY points DESC, answers DESC, posts DESC
";

$leaders = $DB->get_records_sql($sql, $params, 0, 10);

echo json_encode(array_values($leaders));
