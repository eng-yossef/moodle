<?php
require('../../../config.php');
require_login();

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/local/community/pages/post.php', ['id' => $id]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Post');
$PAGE->set_heading('Community Post');

echo $OUTPUT->header();

echo '<div id="post-app" data-postid="'.$id.'"></div>';

$PAGE->requires->js_call_amd('local_community/post', 'init', [$id]);

echo $OUTPUT->footer();