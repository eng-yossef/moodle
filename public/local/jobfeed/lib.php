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
 * Library functions for local_jobfeed plugin.
 *
 * @package    local_jobfeed
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Get the default limit for job results.
 *
 * Falls back to plugin config or hardcoded default.
 *
 * @return int Default limit value
 */
function local_jobfeed_get_default_limit(): int {
    $config = get_config('local_jobfeed');
    return !empty($config->default_limit) ? (int)$config->default_limit : 10;
}

/**
 * Get the API endpoint URL.
 *
 * @return string API endpoint URL
 */
function local_jobfeed_get_api_endpoint(): string {
    $config = get_config('local_jobfeed');
    return !empty($config->api_endpoint) 
        ? $config->api_endpoint 
        : 'http://127.0.0.1:8000/jobs';
}