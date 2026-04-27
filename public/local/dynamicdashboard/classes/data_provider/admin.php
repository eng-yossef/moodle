<?php
// local/dynamicdashboard/classes/data_provider/admin.php
namespace local_dynamicdashboard\data_provider;

class admin extends base {

    public function get_widgets(): array {
        global $DB, $USER;
        $widgets = [];

        // --- KPI Counters ---
        $totalusers = $DB->count_records('user', ['deleted' => 0]);
        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => get_string('totalusers', 'local_dynamicdashboard'),
            'value' => $totalusers,
            'trend' => '+5%',    // bonus trend indicator
        ], 1);

        $activecourses = $DB->count_records('course', ['visible' => 1]);
        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => get_string('activecourses', 'local_dynamicdashboard'),
            'value' => $activecourses,
        ], 2);

        // Simulated DAU (in a real plugin you'd query the logstore)
        $dau = rand(20, 50);
        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => 'Daily Active Users',
            'value' => $dau,
        ], 3);

        // --- User Growth Chart (Line) ---
        // We generate sample data for 12 months (replace with real DB query)
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = date('M', strtotime("-$i months"));
        }
        $growth = [10, 15, 20, 25, 35, 50, 65, 80, 95, 110, 125, 140]; // sample
        $widgets[] = $this->create_widget('chart', [
            'title' => 'User Growth (12 months)',
            'type'  => 'line',
            'labels' => $months,
            'datasets' => [[
                'label' => 'New users',
                'data' => $growth,
                'borderColor' => '#4B77BE',
                'backgroundColor' => 'rgba(75,119,190,0.1)',
                'fill' => true,
            ]],
        ], 4);

        // --- Activity Heatmap ---
        // Sample grid: 7 rows (days of week) x 12 columns (weeks) with random intensity
        $heatmapData = [
            'title' => 'Activity Heatmap',
            'rows' => ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
            'columns' => range(date('W')-11, date('W')), // last 12 weeks
            'values' => $this->randomHeatmap(7, 12),
        ];
        $widgets[] = $this->create_widget('heatmap', $heatmapData, 5);

        // --- Activity Stream (formatted) ---
        $logs = $DB->get_records('logstore_standard_log', [], 'timecreated DESC', '*', 0, 10);
        $events = [];
        foreach ($logs as $log) {
            $events[] = [
                'description' => $this->friendly_event_name($log->eventname),
                'time'        => $log->timecreated,
                'userid'      => $log->userid,
            ];
        }
        $widgets[] = $this->create_widget('activity_stream', [
            'title'  => get_string('recentactivity', 'local_dynamicdashboard'),
            'events' => $events,
        ], 6);

        return $widgets;
    }

    /** Generate random heatmap data (0-4 intensity) */
    private function randomHeatmap(int $rows, int $cols): array {
        $grid = [];
        for ($r = 0; $r < $rows; $r++) {
            $row = [];
            for ($c = 0; $c < $cols; $c++) {
                $row[] = rand(0, 4);
            }
            $grid[] = $row;
        }
        return $grid;
    }

    /** Convert event name to friendly string (simplified) */
    private function friendly_event_name(string $eventname): string {
        $parts = explode('\\', $eventname);
        $last = end($parts);
        // For demo, replace underscores with spaces and capitalise
        $friendly = ucwords(str_replace('_', ' ', $last));
        return $friendly;
    }
}