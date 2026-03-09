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
 * Custom renderer for job feed plugin.
 *
 * @package    local_jobfeed
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_jobfeed\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

/**
 * Renderer class for job feed output.
 *
 * @package    local_jobfeed
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    
    /**
     * Render a list of jobs using Mustache template.
     *
     * @param array $jobs Array of job data arrays
     * @param string $skill The skill filter used
     * @return string Rendered HTML
     */
    public function render_jobs(array $jobs, string $skill = 'java'): string {
        $data = [
            'jobs' => $jobs,
            'skill' => $skill,
            'has_jobs' => !empty($jobs),
            'no_jobs_message' => get_string('no_jobs_found', 'local_jobfeed', $skill),
            'error_message' => get_string('api_error', 'local_jobfeed'),
        ];
        
        return $this->render_from_template('local_jobfeed/jobs', $data);
    }
}