<?php
namespace local_jobfeed;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * API Client for fetching job listings from FastAPI
 */
class api_client {

    /** @var string API endpoint URL */
    private $endpoint;

    /** @var int Request timeout in seconds */
    private $timeout;

    /** @var int Default limit of jobs */
    private $defaultlimit;

    /**
     * Constructor
     */
    public function __construct(
        string $endpoint = 'http://127.0.0.1:8000/jobs',
        int $timeout = 30,
        int $defaultlimit = 5
    ) {
        $this->endpoint = $endpoint;
        $this->timeout = $timeout;
        $this->defaultlimit = $defaultlimit;
    }

    /**
     * Fetch jobs from API
     */
    public function get_jobs(string $skill = 'java', int $limit = null): array {
        if ($limit === null) {
            $limit = $this->defaultlimit;
        }

        $url = $this->endpoint . '?' . http_build_query([
            'skill' => $skill,
            'limit' => $limit
        ]);

        try {
            $curl = new \curl();

            // Make GET request
            $response = $curl->get($url);

            if ($curl->get_errno()) {
                debugging('Job API Curl Error: ' . $curl->error, DEBUG_DEVELOPER);
                return [];
            }

            $data = json_decode($response, true);

            if (!is_array($data) || !isset($data['jobs'])) {
                debugging('Invalid API response', DEBUG_DEVELOPER);
                return [];
            }

            $jobs = [];
            foreach ($data['jobs'] as $job) {
                $sanitized = $this->sanitize_job($job);
                if ($sanitized) {
                    $jobs[] = $sanitized;
                }
            }

            return $jobs;

        } catch (\Exception $e) {
            debugging('Job API exception: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }

    /**
     * Sanitize job array
     */
    private function sanitize_job(array $job): ?array {
        $required = ['title', 'company', 'location', 'date', 'url'];

        foreach ($required as $field) {
            if (!isset($job[$field]) || !is_string($job[$field])) {
                return null;
            }
        }

        if (!filter_var($job['url'], FILTER_VALIDATE_URL)) {
            return null;
        }

        return [
            'title' => clean_param($job['title'], PARAM_TEXT),
            'company' => clean_param($job['company'], PARAM_TEXT),
            'location' => clean_param($job['location'], PARAM_TEXT),
            'date' => clean_param($job['date'], PARAM_TEXT),
            'url' => clean_param($job['url'], PARAM_URL)
        ];
    }
}