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

namespace local_jobfeed\output;

use plugin_renderer_base;
use stdClass;

/**
 * Renderer for local_jobfeed.
 *
 * @package   local_jobfeed
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Render jobs page.
     *
     * @param stdClass $data Template data.
     * @return string
     */
    public function render_jobs_page(stdClass $data): string {
        return $this->render_from_template('local_jobfeed/jobs', $data);
    }
}
