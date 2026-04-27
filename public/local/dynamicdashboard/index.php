<?php
// local/dynamicdashboard/index.php
require_once('../../config.php');
require_login();

$PAGE->set_url('/local/dynamicdashboard/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('dashboard', 'local_dynamicdashboard'));
$PAGE->set_heading(get_string('dashboard', 'local_dynamicdashboard'));

// Enqueue AMD modules.
$PAGE->requires->js_call_amd('local_dynamicdashboard/dashboard', 'init', [
    'refreshInterval' => (int)get_config('local_dynamicdashboard', 'refreshinterval'),
]);

// Get dashboard data for current user.
$dashboard = \local_dynamicdashboard\dashboard::get_for_user($USER);
$widgets = $dashboard->get_widgets();

$widgetsdata = [];
foreach ($widgets as $widget) {
    $widgetsdata[] = [
        'id'      => $widget->id,
        'type'    => $widget->type,
        'content' => $widget->render_initial(),
    ];
}

$role = 'user';
if (is_siteadmin()) {
    $role = 'admin';
} else if (has_capability('moodle/course:viewhiddenactivities', context_system::instance())) {
    $role = 'teacher';
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_dynamicdashboard/dashboard_layout', [
    'role'    => $role,
    'widgets' => $widgetsdata,
]);
echo $OUTPUT->footer();