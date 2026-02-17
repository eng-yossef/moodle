<?php
namespace local_coptutor;

defined('MOODLE_INTERNAL') || die();

class manager {

    public static function save_qa($userid, $courseid, $question, $answer) {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->question = $question;
        $record->answer = $answer;
        $record->timecreated = time();

        $DB->insert_record('local_coptutor_qa', $record);
    }

    public static function get_history($userid, $courseid) {
        global $DB;

        return $DB->get_records('local_coptutor_qa', [
            'userid' => $userid,
            'courseid' => $courseid
        ]);
    }
}
