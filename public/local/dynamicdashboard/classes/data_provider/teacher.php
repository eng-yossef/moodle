<?php
namespace local_dynamicdashboard\data_provider;

class teacher extends base {
    public function get_widgets(): array {
        global $DB, $USER;
        $widgets = [];

        $teachercourses = enrol_get_users_courses($USER->id, true);
        $totalstudents = 0;
        $completionRates = [];
        foreach ($teachercourses as $course) {
            $context = \context_course::instance($course->id);
            $students = get_enrolled_users($context, 'mod/assignment:submit');
            $totalstudents += count($students);
            // Simulated completion rate per course (replace with real)
            $completionRates[] = rand(50, 100);
        }

        // KPI: courses count
        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => get_string('yourcourses', 'local_dynamicdashboard'),
            'value' => count($teachercourses),
        ], 101);

        // KPI: total enrolled
        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => get_string('totalenrolled', 'local_dynamicdashboard'),
            'value' => $totalstudents,
        ], 102);

        // Average completion as progress circle
        $avgCompletion = count($completionRates) ? round(array_sum($completionRates) / count($completionRates)) : 0;
        $widgets[] = $this->create_widget('progress_circle', [
            'label' => 'Average Completion',
            'percent' => $avgCompletion,
        ], 103);

        // Grade Distribution bar chart (sample)
        $grades = ['<50', '50-59', '60-69', '70-79', '80-89', '90-100'];
        $counts = [2, 5, 10, 15, 12, 6]; // sample
        $widgets[] = $this->create_widget('chart', [
            'title' => 'Grade Distribution',
            'type' => 'bar',
            'labels' => $grades,
            'datasets' => [[
                'label' => 'Students',
                'data' => $counts,
                'backgroundColor' => '#19B5FE',
            ]],
        ], 104);

        // Engagement Funnel - formatted as array of objects with percent calculation
        $funnelSteps = [
            'Enrolled' => $totalstudents, 
            'Started' => round($totalstudents * 0.9), 
            'Active 1st week' => round($totalstudents * 0.7), 
            'Mid-term active' => round($totalstudents * 0.5), 
            'Completed' => round($totalstudents * 0.3)
        ];
        
        $max = $funnelSteps['Enrolled'];
        $steps = [];
        foreach ($funnelSteps as $stepLabel => $count) {
            $steps[] = [
                'label' => $stepLabel, 
                'value' => $count, 
                'percent' => $max > 0 ? round(($count / $max) * 100) : 0
            ];
        }
        
        $widgets[] = $this->create_widget('funnel', [
            'title' => 'Engagement Funnel', 
            'steps' => $steps
        ], 105);

        return $widgets;
    }
}