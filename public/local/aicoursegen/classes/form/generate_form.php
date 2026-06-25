<?php
namespace local_aicoursegen\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class generate_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        // Hidden course ID.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        // File upload – context is not needed for draft files.
        $mform->addElement('filepicker', 'file', get_string('upload_file', 'local_aicoursegen'), null, [
            'accepted_types' => ['.pdf', '.ppt', '.pptx'],
            'maxbytes' => 50 * 1024 * 1024,
        ]);
        $mform->addHelpButton('file', 'upload_file', 'local_aicoursegen');
        $mform->addRule('file', null, 'required');

        // Course title.
        $mform->addElement('text', 'title', get_string('course_title', 'local_aicoursegen'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        // Number of lectures.
        $mform->addElement('select', 'numlectures', get_string('num_lectures', 'local_aicoursegen'), range(1, 20));
        $mform->setDefault('numlectures', 5);

        // Include quizzes.
        $mform->addElement('advcheckbox', 'includequizzes', get_string('include_quizzes', 'local_aicoursegen'), '');
        $mform->setDefault('includequizzes', 1);

        // Description (optional).
        $mform->addElement('textarea', 'description', get_string('description', 'moodle'), 'rows="5"');
        $mform->setType('description', PARAM_TEXT);

        // Submit.
        $this->add_action_buttons(false, get_string('submit', 'local_aicoursegen'));
    }
}