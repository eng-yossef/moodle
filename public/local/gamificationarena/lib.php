<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Extend course navigation with Game Challenge entry.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function local_gamificationarena_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context): void {
    if (!has_capability('local/gamificationarena:play', $context)) {
        return;
    }

    $url = new moodle_url('/local/gamificationarena/index.php', ['courseid' => $course->id]);
    $navigation->add(
        get_string('pluginname', 'local_gamificationarena'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_gamificationarena',
        new pix_icon('i/competencies', '')
    );
}
