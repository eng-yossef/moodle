<?php
require_once("$CFG->libdir/formslib.php");

class jobfeed_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        // Skill input
        $mform->addElement('text', 'skill', 'Skill');
        $mform->setType('skill', PARAM_TEXT);
        $mform->setDefault('skill', 'python'); // initial skill

        // Submit button
        $mform->addElement('submit', 'submitbutton', 'Search Jobs');
    }
}