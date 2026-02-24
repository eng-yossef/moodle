<?php
require('../../../config.php');
require_login();

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/local/community/pages/post.php', ['id' => $id]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Post');
$PAGE->set_heading('Community Post');

echo $OUTPUT->header();

// Editor options
$editoroptions = [
    'maxfiles' => 0,
    'context' => context_system::instance(),
];
$draftid = file_get_submitted_draft_itemid('answercontent');
$defaulttext = '';

// Answer form with Moodle editor
echo '<div id="answerform">';
echo '<textarea id="answercontent" name="answercontent">'.$defaulttext.'</textarea>';
echo '<button id="addanswer">Add Answer</button>';
echo '</div>';

// Initialise Moodle’s preferred editor (Atto/TinyMCE)
$editor = editors_get_preferred_editor();
$editor->use_editor('answercontent', $editoroptions);

// Container for JS app
echo '<div id="post-app" data-postid="'.$id.'"></div>';

// Load your post AMD module
$PAGE->requires->js_call_amd('local_community/post', 'init', [$id]);

echo $OUTPUT->footer();
