<?php
// local/dynamicdashboard/classes/data_provider/user.php
namespace local_dynamicdashboard\data_provider;

class user extends base {
    public function get_widgets(): array {
        global $DB, $USER;
        $widgets = [];

        // Enrolled courses count.
        $courses = enrol_get_users_courses($USER->id, true);
        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => get_string('mycourses', 'local_dynamicdashboard'),
            'value' => count($courses),
        ], 201);

        // Activity log (last 5 actions).
        $logs = $DB->get_records('logstore_standard_log', ['userid' => $USER->id], 'timecreated DESC', '*', 0, 5);
        $events = [];
        foreach ($logs as $log) {
            $events[] = [
                'description' => $log->eventname,
                'time'        => $log->timecreated,
            ];
        }
        $widgets[] = $this->create_widget('activity_stream', [
            'title'  => get_string('youractivity', 'local_dynamicdashboard'),
            'events' => $events,
        ], 202);

        return $widgets;
    }
}