<?php
require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/myplugin:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/myplugin/index.php');
$PAGE->set_title('My Plugin');
$PAGE->set_heading('My Plugin');

echo $OUTPUT->header();
echo html_writer::tag('h2', 'Adaptive Learning Plugin Loaded Successfully ✅');
echo $OUTPUT->footer();
