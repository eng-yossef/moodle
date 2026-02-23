<?php
define('AJAX_SCRIPT', true);

require('../../config.php');
require_login();

global $DB, $USER;

$courseid = required_param('courseid', PARAM_INT);

$records = $DB->get_records_sql("
    SELECT question, answer
    FROM {local_coptutor_qa}
    WHERE userid = ? AND courseid = ?
    ORDER BY id DESC
    LIMIT 2
", [$USER->id, $courseid]);

$records = array_reverse($records); // show oldest first

echo json_encode(array_values($records));
