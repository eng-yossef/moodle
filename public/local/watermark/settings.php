<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_watermark', get_string('pluginname', 'local_watermark'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_watermark/enabled',
        get_string('enabled', 'local_watermark'),
        get_string('enabled_desc', 'local_watermark'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_watermark/template',
        get_string('watermarktpl', 'local_watermark'),
        get_string('watermarktpl_desc', 'local_watermark'),
        'User: {username} | ID: {userid}',
        PARAM_TEXT
    ));
}