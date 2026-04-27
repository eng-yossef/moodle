<?php
// local/dynamicdashboard/lib.php
defined('MOODLE_INTERNAL') || die();

/**
 * Extend navigation to add the dashboard link (optional).
 */
function local_dynamicdashboard_extend_navigation(global_navigation $nav) {
    // We don't need to add a link here; admin will set default home page.
}

/**
 * Serve the files from the plugin file areas.
 */
function local_dynamicdashboard_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    // No file serving needed.
    return false;
}

/**
 * Fragment callback: allows dynamic reloading of a widget.
 */
function local_dynamicdashboard_output_fragment($args) {
    global $PAGE, $USER;
    $widgettype = $args['type'] ?? null;
    $data = json_decode($args['data'] ?? '{}', true);
    $widgetclass = '\\local_dynamicdashboard\\widget\\' . $widgettype;
    if (!class_exists($widgetclass)) {
        throw new \moodle_exception('Invalid widget type');
    }
    $renderer = new $widgetclass($data);
    return $renderer->render_initial();
}