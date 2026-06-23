<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'mod_interview/apiurl',
        get_string('apiurl', 'mod_interview'),
        get_string('apiurl_help', 'mod_interview'),
        'http://127.0.0.1:8000',
        PARAM_URL
    ));
}