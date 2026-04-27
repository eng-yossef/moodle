<?php
// local/dynamicdashboard/classes/data_provider/teacher.php
namespace local_dynamicdashboard\data_provider;

class teacher extends base {
    public function get_widgets(): array {
        global $DB, $USER;
        $widgets = [];

        // Get courses where user is teacher.
        $teachercourses = enrol_get_users_courses($USER->id, true);
        $totalstudents = 0;
        $completiontotal = 0;
        $counter = 0;
        foreach ($teachercourses as $course) {
            $context = \context_course::instance($course->id);
            $students = get_enrolled_users($context, 'mod/assignment:submit');
            $totalstudents += count($students);
            // Simplified completion rate: 75% placeholder.
            $completiontotal += 75;
            $counter++;
        }
        $avgcompletion = $counter ? round($completiontotal / $counter) : 0;

        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => get_string('yourcourses', 'local_dynamicdashboard'),
            'value' => count($teachercourses),
        ], 101);

        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => get_string('totalenrolled', 'local_dynamicdashboard'),
            'value' => $totalstudents,
        ], 102);

        $widgets[] = $this->create_widget('kpi_counter', [
            'label' => get_string('avgcompletion', 'local_dynamicdashboard'),
            'value' => $avgcompletion . '%',
        ], 103);

        return $widgets;
    }
}