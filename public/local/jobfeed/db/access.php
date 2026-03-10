<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/jobfeed:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW,
            'guest' => CAP_PREVENT,
        ]
    ]
];