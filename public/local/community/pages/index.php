<?php
require('../../../config.php');
require_login();

$PAGE->set_url('/local/community/pages/index.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Global Community');
$PAGE->set_heading('Global Community');

echo $OUTPUT->header();

echo '<div id="community-app"></div>';

$PAGE->requires->js_call_amd('local_community/community', 'init');

echo $OUTPUT->footer();