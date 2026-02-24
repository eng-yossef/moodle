<?php
function local_community_extend_navigation(global_navigation $nav) {

$node = navigation_node::create(
'Global Community',
new moodle_url('/local/community/pages/index.php'),
navigation_node::TYPE_CUSTOM
);

$nav->add_node($node);
}