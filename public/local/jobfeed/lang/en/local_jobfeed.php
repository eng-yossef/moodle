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
 * English language strings for local_jobfeed plugin.
 *
 * @package    local_jobfeed
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Job Feed';
$string['apply_now'] = 'Apply Now';
$string['jobs_for_skill'] = 'Jobs for {$a}';
$string['no_jobs_found'] = 'No {$a} jobs found at the moment.';
$string['try_different_skill'] = 'Try searching for a different skill or check back later.';
$string['api_error'] = 'Unable to fetch job listings. Please try again later.';

// Settings strings.
$string['api_endpoint'] = 'API Endpoint';
$string['api_endpoint_desc'] = 'The base URL for the job listings API endpoint.';
$string['default_limit'] = 'Default Job Limit';
$string['default_limit_desc'] = 'Default number of jobs to display when no limit parameter is provided (1-50).';
$string['default_skill'] = 'Default Skill';
$string['default_skill_desc'] = 'Default skill filter to use when no skill parameter is provided.';