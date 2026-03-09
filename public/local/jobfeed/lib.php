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
 * Library callbacks for local_jobfeed.
 *
 * @package   local_jobfeed
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends the site navigation with a Job Feed link.
 *
 * @param global_navigation $navigation Navigation node object.
 */
function local_jobfeed_extend_navigation(global_navigation $navigation): void {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    $url = new moodle_url('/local/jobfeed/index.php');
    $node = navigation_node::create(
        get_string('pluginname', 'local_jobfeed'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_jobfeed'
    );

    $navigation->add_node($node);
}
