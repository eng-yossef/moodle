<?php

defined('MOODLE_INTERNAL') || die();

function interview_supports($feature) {
    switch ($feature) {

        case FEATURE_MOD_INTRO:
            return true;

        case FEATURE_BACKUP_MOODLE2:
            return true;

        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}

function interview_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();

    return $DB->insert_record('interview', $data);
}

function interview_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    return $DB->update_record('interview', $data);
}

function interview_delete_instance($id) {
    global $DB;

    if (!$interview = $DB->get_record('interview', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('interview_sessions', [
        'interviewid' => $id
    ]);

    $DB->delete_records('interview', [
        'id' => $id
    ]);

    return true;
}