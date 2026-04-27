<?php
// local/dynamicdashboard/settings.php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_dynamicdashboard', get_string('pluginname', 'local_dynamicdashboard'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_dynamicdashboard/refreshinterval',
        get_string('refreshinterval', 'local_dynamicdashboard'),
        get_string('refreshinterval_desc', 'local_dynamicdashboard'),
        30,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_dynamicdashboard/enableconfetti',
        get_string('enableconfetti', 'local_dynamicdashboard'),
        get_string('enableconfetti_desc', 'local_dynamicdashboard'),
        1
    ));
}