<?php

require('../../config.php');
require_once($CFG->libdir . '/filelib.php');

$curl = new curl();

$response = $curl->get(
    'http://127.0.0.1:8000/docs'
);

echo "<pre>";
var_dump($response);
echo "</pre>";