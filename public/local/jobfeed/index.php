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
// Display the form
$mform->display();
// Check if form is submitted and valid
if ($mform->is_submitted() && $mform->is_validated()) {
    $data = $mform->get_data();
    $skill = $data->skill;
    $limit = $data->limit;

    // Fetch jobs
    $jobsdata = local_jobfeed_get_jobs($skill, $limit);

    if(isset($jobsdata['error'])) {
        echo html_writer::tag('div', 'Error: '.$jobsdata['error'], ['class' => 'alert alert-danger']);
    } else {
        foreach($jobsdata['jobs'] as $job) {
            echo html_writer::start_div('job-card', ['style' => 'border:1px solid #ccc;padding:10px;margin:10px 0;']);
            echo html_writer::tag('h3', $job['title']);
            echo html_writer::tag('p', 'Company: '.$job['company']);
            echo html_writer::tag('p', 'Location: '.$job['location']);
            echo html_writer::tag('p', 'Posted: '.$job['date']);
            echo html_writer::tag('a', 'View Job', ['href' => $job['url'], 'target' => '_blank']);
            echo html_writer::end_div();
        }
    }
}



echo $OUTPUT->footer();