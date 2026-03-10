<?php
require_once("$CFG->libdir/formslib.php");

class jobfeed_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'skill', 'Skill');
        $mform->setType('skill', PARAM_TEXT);
        $mform->setDefault('skill', 'python');

        $mform->addElement('text', 'limit', 'Number of jobs');
        $mform->setType('limit', PARAM_INT);
        $mform->setDefault('limit', 1);

        $mform->addElement('submit', 'submitbutton', 'Get Jobs');
    }
}