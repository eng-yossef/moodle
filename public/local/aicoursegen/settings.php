<?php
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aicoursegen', get_string('pluginname', 'local_aicoursegen'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_aicoursegen/api_base_url',
        get_string('api_base_url', 'local_aicoursegen'),
        get_string('api_base_url_desc', 'local_aicoursegen'),
        'http://localhost:8000',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_aicoursegen/api_key',
        get_string('api_key', 'local_aicoursegen'),
        get_string('api_key_desc', 'local_aicoursegen'),
        '',
        PARAM_ALPHANUMEXT
    ));
}