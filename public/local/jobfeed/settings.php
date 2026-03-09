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

/**
 * Admin settings for local_jobfeed plugin.
 *
 * @package    local_jobfeed
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_jobfeed', new lang_string('pluginname', 'local_jobfeed'));

    if ($ADMIN->fulltree) {
        // API Endpoint configuration.
        $settings->add(new admin_setting_configtext(
            'local_jobfeed/api_endpoint',
            new lang_string('api_endpoint', 'local_jobfeed'),
            new lang_string('api_endpoint_desc', 'local_jobfeed'),
            'http://localhost:8000/jobs',
            PARAM_URL
        ));
        
        // Default job limit.
        $settings->add(new admin_setting_configtext(
            'local_jobfeed/default_limit',
            new lang_string('default_limit', 'local_jobfeed'),
            new lang_string('default_limit_desc', 'local_jobfeed'),
            10,
            PARAM_INT
        ));
        
        // Default skill filter.
        $settings->add(new admin_setting_configtext(
            'local_jobfeed/default_skill',
            new lang_string('default_skill', 'local_jobfeed'),
            new lang_string('default_skill_desc', 'local_jobfeed'),
            'java',
            PARAM_ALPHANUMEXT
        ));
    }
}