<?php
require('../../../config.php');
require_login();

header('Content-Type: application/json');

global $CFG;

// Get skill parameter from URL (optional)
$skill = optional_param('skill', 'python', PARAM_ALPHANUMEXT);
$limit = optional_param('limit', 5, PARAM_INT);
$limit = max(1, min($limit, 50)); // constrain limit

// Build API URL properly
$api_url = 'http://127.0.0.1:8000/jobs?' . http_build_query([
    'skill' => $skill,
    'limit' => $limit
]);

// Call API
$options = [
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n"
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($api_url, false, $context);

if ($response === false) {
    echo json_encode(['error' => 'Job API service unavailable']);
    exit;
}

// Decode JSON
$data = json_decode($response, true);

if (!is_array($data)) {
    echo json_encode(['error' => 'Invalid response from Job API']);
    exit;
}

// Optionally sanitize output here if needed
echo json_encode($data);
exit;