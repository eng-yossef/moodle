<?php
// local/dynamicdashboard/db/access.php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/dynamicdashboard:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW,
            'guest' => CAP_PREVENT,
        ],
    ],
];