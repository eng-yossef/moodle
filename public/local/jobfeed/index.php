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

require_once(__DIR__ . '/../../config.php');

require_login();

$defaultlimit = (int)get_config('local_jobfeed', 'defaultlimit');
if ($defaultlimit < 1) {
    $defaultlimit = 5;
}

$skill = optional_param('skill', 'java', PARAM_ALPHANUMEXT);
$limit = optional_param('limit', $defaultlimit, PARAM_INT);
if ($limit < 1 || $limit > 50) {
    $limit = $defaultlimit;
}

$pageurl = new moodle_url('/local/jobfeed/index.php', ['skill' => $skill, 'limit' => $limit]);
$PAGE->set_url($pageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_jobfeed'));
$PAGE->set_heading(get_string('pluginname', 'local_jobfeed'));

$client = new \local_jobfeed\api_client();
$jobs = [];
$error = '';

try {
    $jobs = $client->get_jobs($skill, $limit);
} catch (Throwable $e) {
    $error = get_string('errorfetchjobs', 'local_jobfeed');
}

$templatecontext = (object)[
    'skill' => s($skill),
    'hasjobs' => !empty($jobs),
    'jobs' => array_map(static function(array $job): array {
        return [
            'title' => s($job['title']),
            'company' => s($job['company']),
            'location' => s($job['location']),
            'date' => s($job['date']),
            'url' => s($job['url']),
        ];
    }, $jobs),
    'error' => $error,
    'haserror' => !empty($error),
    'nojobs' => empty($jobs) && empty($error),
    'applylabel' => get_string('applynow', 'local_jobfeed'),
    'nojobsmessage' => get_string('nojobsfound', 'local_jobfeed'),
];

/** @var \local_jobfeed\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_jobfeed');

echo $OUTPUT->header();
echo $renderer->render_jobs_page($templatecontext);
echo $OUTPUT->footer();
