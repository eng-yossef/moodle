<?php
function local_coptutor_extend_navigation_course($navigation, $course, $context) {
    global $PAGE;
    $PAGE->requires->js_call_amd('local_coptutor/chat', 'init', ['courseid' => $course->id]);
}
