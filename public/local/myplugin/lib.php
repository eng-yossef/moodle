<?php
function local_myplugin_extend_navigation(global_navigation $nav) {
    $node = $nav->add(
        get_string('pluginname', 'local_myplugin'),
        new moodle_url('/local/myplugin/index.php')
    );
    $node->showinflatnavigation = true;
}
