<?php
namespace local_dynamicdashboard\data_provider;

class user extends base {
    public function get_widgets(): array {
        global $DB, $USER;
        $widgets = [];

        $courses = enrol_get_users_courses($USER->id, true);

        // My courses count
        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => get_string('mycourses', 'local_dynamicdashboard'),
            'value' => count($courses),
        ], 201);

        // Streak (simplified: count of consecutive days with activity)
        $streak = rand(3, 20);
        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => 'Learning Streak 🔥',
            'value' => $streak . ' days',
        ], 202);

        // Overall course progress as progress circle
        $progressPercent = $courses ? rand(20, 100) : 0;
        $widgets[] = $this->create_widget('progress_circle', [
            'label' => 'Overall Progress',
            'percent' => $progressPercent,
        ], 203);

        // Radar chart for skills
        $skills = ['PHP','JavaScript','SQL','HTML','CSS'];
        $skillValues = [80, 65, 90, 70, 60];
        $widgets[] = $this->create_widget('chart', [
            'title' => 'Skill Proficiency',
            'type'  => 'radar',
            'labels' => $skills,
            'datasets' => [[
                'label' => 'You',
                'data' => $skillValues,
                'backgroundColor' => 'rgba(46,204,113,0.2)',
                'borderColor' => '#2ecc71',
            ]],
        ], 204);

        // Activity stream (last 5)
        $logs = $DB->get_records_select('logstore_standard_log', 'userid = ?', [$USER->id], 'timecreated DESC', '*', 0, 5);
        $events = [];
        foreach ($logs as $log) {
            $events[] = [
                'description' => $this->friendly_event_name($log->eventname),
                'time'        => $log->timecreated,
            ];
        }
        $widgets[] = $this->create_widget('activity_stream', [
            'title' => 'Your Recent Activity',
            'events' => $events,
        ], 205);

        return $widgets;
    }

    private function friendly_event_name(string $eventname): string {
        $parts = explode('\\', $eventname);
        return ucwords(str_replace('_', ' ', end($parts)));
    }
}