<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_myplugin', get_string('pluginname', 'local_myplugin'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_myplugin/welcome_message',
        'Welcome Message',
        'Message displayed to users',
        'Hello Moodle User!',
        PARAM_TEXT
    ));
}
