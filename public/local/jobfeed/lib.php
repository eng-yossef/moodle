<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add plugin link to Moodle top navigation bar
 *
 * @param global_navigation $nav
 */
function local_jobfeed_extend_navigation(global_navigation $nav) {
    global $USER;

    // Optional: check if user can view plugin
    if (!isloggedin() || isguestuser()) {
        return;
    }

    // Add top-level link
    $url = new moodle_url('/local/jobfeed/index.php');
    $nav->add(
        'Job Feed',                   // Text to display
        $url,                         // Link
        navigation_node::TYPE_CUSTOM, // Node type
        null,                         // Short text (null)
        null,                         // Key (null)
        new pix_icon('i/info', 'Job Feed') // Icon
    );
}
/**
 * Get jobs from external API
 */
function local_jobfeed_get_jobs($skill = 'python', $limit = 1) {
    $url = "http://localhost:8000/jobs?skill={$skill}&limit={$limit}";

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);

    if(curl_errno($curl)) {
        return ['error' => curl_error($curl)];
    }

    curl_close($curl);
    $data = json_decode($response, true);

    if(!$data) {
        return ['error' => 'Invalid JSON response'];
    }

    return $data;
}