<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_jobfeed', get_string('pluginname', 'local_jobfeed'));

    $settings->add(new admin_setting_configtext(
        'local_jobfeed/defaultlimit',
        get_string('defaultlimit', 'local_jobfeed'),
        get_string('defaultlimit_desc', 'local_jobfeed'),
        5,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
