<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_interview_mod_form extends moodleform_mod {
    function definition() {
        global $CFG;
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size'=>'64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        // Optional API URL override
        $mform->addElement('text', 'apiurl', get_string('apiurl', 'mod_interview'), ['size'=>'60']);
        $mform->setType('apiurl', PARAM_URL);
        $mform->addHelpButton('apiurl', 'apiurl', 'mod_interview');
        $mform->setDefault('apiurl', get_config('mod_interview', 'apiurl'));

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}