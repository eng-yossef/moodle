<?php
require_once('../../config.php');
$original = 'http://moodle.local/pluginfile.php/17/mod_resource/content/3/Computer%20Interface%20%E2%80%93%20%D8%A8%D8%B5%D9%85%D8%AC%D8%A9%20%D9%85%D8%AD%D8%AA%D8%B1%D9%85%D8%A9.pdf';
$encoded = urlencode($original);
$watermarked_url = $CFG->wwwroot . '/local/watermark/download.php?file=' . $encoded;
echo "Click this link: <a href='$watermarked_url'>$watermarked_url</a>";
?>