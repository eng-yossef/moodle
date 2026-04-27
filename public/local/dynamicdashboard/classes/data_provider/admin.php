<?php
// local/dynamicdashboard/classes/data_provider/admin.php
namespace local_dynamicdashboard\data_provider;

use local_dynamicdashboard\widget\kpi_counter;
use local_dynamicdashboard\widget\activity_stream;

class admin extends base {
    public function get_widgets(): array {
        global $DB;
        $widgets = [];

        // KPI: Total users
        $totalusers = $DB->count_records('user', ['deleted' => 0]);
        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => get_string('totalusers', 'local_dynamicdashboard'),
            'value' => $totalusers,
        ], 1);

        // KPI: Active courses
        $activecourses = $DB->count_records_select('course', 'visible = 1');
        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => get_string('activecourses', 'local_dynamicdashboard'),
            'value' => $activecourses,
        ], 2);

        // Activity stream (last 10 admin-relevant events)
        $logs = $DB->get_records('logstore_standard_log', [], 'timecreated DESC', '*', 0, 10);
        $events = [];
        foreach ($logs as $log) {
            $events[] = [
                'description' => $log->eventname . ' by user ' . $log->userid,
                'time'        => $log->timecreated,
            ];
        }
        $widgets[] = $this->create_widget('activity_stream', [
            'title'  => get_string('recentactivity', 'local_dynamicdashboard'),
            'events' => $events,
        ], 3);

        return $widgets;
    }
}