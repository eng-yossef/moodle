<?php

require('../../config.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);

use local_gamificationarena\local\question_provider;

$questions = question_provider::get_match_questions($courseid, 5);

echo "<pre>";
print_r($questions);