<?php
require('../../config.php');
require_login();
require_once($CFG->dirroot.'/local/jobfeed/lib.php');
require_once($CFG->dirroot.'/local/jobfeed/forms/jobform.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/jobfeed/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Job Feed');
$PAGE->set_heading('Job Feed');

echo $OUTPUT->header();

// Initialize form
$mform = new jobfeed_form();

// Pagination parameters
$page = optional_param('page', 0, PARAM_INT);
$jobsperpage = optional_param('jobsperpage', 5, PARAM_INT); // default 5 jobs per page

// Skill input
if ($mform->is_submitted() && $mform->is_validated()) {
    $data = $mform->get_data();
    $skill = $data->skill;
    $page = 0; // reset page when new search
} else {
    $skill = optional_param('skill', 'python', PARAM_TEXT); // default skill
}

// Display form with current skill
$mform->set_data(['skill' => $skill]);
$mform->display();

// Fetch all jobs from API
$alljobs = local_jobfeed_get_jobs($skill, 100); // fetch max 100 jobs

if (isset($alljobs['error'])) {
    echo html_writer::tag('div', 'Error: '.$alljobs['error'], ['class'=>'alert alert-danger']);
} else {
    $jobs = $alljobs['jobs'];
    $totaljobs = count($jobs);

    // Slice jobs for current page
    $start = $page * $jobsperpage;
    $pagedjobs = array_slice($jobs, $start, $jobsperpage);

    foreach ($pagedjobs as $job) {
        echo html_writer::start_div('job-card', ['style'=>'border:1px solid #ccc;padding:10px;margin:10px 0;']);
        echo html_writer::tag('h3', $job['title']);
        echo html_writer::tag('p', 'Company: '.$job['company']);
        echo html_writer::tag('p', 'Location: '.$job['location']);
        echo html_writer::tag('p', 'Posted: '.$job['date']);
        echo html_writer::tag('a', 'View Job', ['href'=>$job['url'], 'target'=>'_blank']);
        echo html_writer::end_div();
    }

    // Pagination links
    $pages = ceil($totaljobs / $jobsperpage);
    echo '<div class="pagination" style="margin-top:15px;">';
    for ($i = 0; $i < $pages; $i++) {
        $url = new moodle_url('/local/jobfeed/index.php', [
            'page' => $i,
            'skill' => $skill,
            'jobsperpage' => $jobsperpage
        ]);
        $label = $i + 1;
        if ($i == $page) {
            echo html_writer::tag('span', $label, ['style'=>'margin:5px;font-weight:bold;']);
        } else {
            echo html_writer::link($url, $label, ['style'=>'margin:5px;']);
        }
    }
    echo '</div>';
}

echo $OUTPUT->footer();