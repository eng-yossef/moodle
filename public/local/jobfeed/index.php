<?php
require('../../config.php');
require_login();

global $CFG, $OUTPUT, $PAGE;

require_once($CFG->dirroot . '/local/jobfeed/lib.php');
use local_jobfeed\api_client;

// Page setup
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/jobfeed/index.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_jobfeed'));
$PAGE->set_heading(get_string('pluginname', 'local_jobfeed'));

// Get skill & limit from URL or plugin config
$skill = optional_param('skill', get_config('local_jobfeed', 'default_skill') ?: 'java', PARAM_ALPHANUMEXT);
$limit = optional_param('limit', local_jobfeed_get_default_limit(), PARAM_INT);
$limit = max(1, min($limit, 50)); // constrain limit

// Initialize renderer
$output = $PAGE->get_renderer('local_jobfeed');

// Set API endpoint
$endpoint = 'http://127.0.0.1:8000/jobs'; // FastAPI endpoint

// Initialize API client
$apiclient = new api_client($endpoint);

// Fetch jobs from API
$jobs = $apiclient->get_jobs($skill, $limit);

// Render page
echo $OUTPUT->header();
echo $output->render_jobs($jobs, $skill);
echo $OUTPUT->footer();