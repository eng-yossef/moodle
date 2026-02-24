<?php
require('../../../config.php');
require_login();

header('Content-Type: application/json');

$q = required_param('q', PARAM_TEXT);

// For now, return dummy data.
// Later: call FastAPI → embeddings → semantic search.
$similar = [
    ['id' => 1, 'title' => 'How to integrate AI chatbot in Moodle?'],
    ['id' => 2, 'title' => 'Best practices for Atto editor plugins'],
    ['id' => 3, 'title' => 'Troubleshooting AJAX in local plugins'],
];

echo json_encode($similar);
