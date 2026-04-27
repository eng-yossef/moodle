<?php
// local/dynamicdashboard/db/services.php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_dynamicdashboard_get_widgets' => [
        'classname'     => 'local_dynamicdashboard\external',
        'methodname'    => 'get_widgets',
        'description'   => 'Retrieve dashboard widget data for the current user.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];