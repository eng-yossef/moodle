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

namespace local_jobfeed;

use moodle_exception;

/**
 * Job feed API client.
 *
 * @package   local_jobfeed
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_client {
    /** @var string API base endpoint. */
    private const ENDPOINT = 'http://localhost:8000/jobs';

    /** @var int Timeout in seconds for HTTP requests. */
    private const TIMEOUT = 10;

    /**
     * Fetch jobs from external endpoint.
     *
     * @param string $skill Skill name.
     * @param int $limit Number of jobs to request.
     * @return array<int, array<string, string>>
     * @throws moodle_exception If API call or payload is invalid.
     */
    public function get_jobs(string $skill = 'java', int $limit = 5): array {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $params = [
            'skill' => trim(core_text::strtolower($skill)),
            'limit' => $limit,
        ];

        $url = new \moodle_url(self::ENDPOINT, $params);

        $curl = new \curl();
        $options = [
            'CURLOPT_TIMEOUT' => self::TIMEOUT,
            'CURLOPT_CONNECTTIMEOUT' => self::TIMEOUT,
            'CURLOPT_FOLLOWLOCATION' => false,
        ];

        $responsebody = $curl->get($url->out(false), [], $options);
        $httpcode = $curl->get_info()['http_code'] ?? 0;

        if ((int)$httpcode !== 200 || $responsebody === false) {
            throw new moodle_exception('apifailure', 'local_jobfeed');
        }

        $payload = json_decode($responsebody, true);
        if (!is_array($payload) || !isset($payload['jobs']) || !is_array($payload['jobs'])) {
            throw new moodle_exception('invalidresponse', 'local_jobfeed');
        }

        $jobs = [];
        foreach ($payload['jobs'] as $job) {
            if (!is_array($job)) {
                continue;
            }

            $title = $this->get_required_field($job, 'title');
            $company = $this->get_required_field($job, 'company');
            $location = $this->get_required_field($job, 'location');
            $date = $this->get_required_field($job, 'date');
            $joburl = $this->get_required_field($job, 'url');

            if (!clean_param($joburl, PARAM_URL)) {
                continue;
            }

            $jobs[] = [
                'title' => $title,
                'company' => $company,
                'location' => $location,
                'date' => $date,
                'url' => $joburl,
            ];
        }

        return $jobs;
    }

    /**
     * Returns required scalar field as string.
     *
     * @param array<string, mixed> $job Job payload.
     * @param string $field Field name.
     * @return string
     * @throws moodle_exception
     */
    private function get_required_field(array $job, string $field): string {
        if (!array_key_exists($field, $job) || !is_scalar($job[$field])) {
            throw new moodle_exception('invalidresponse', 'local_jobfeed');
        }

        return (string)$job[$field];
    }
}
