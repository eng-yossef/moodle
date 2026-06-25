<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// ... [Standard Moodle GPL Header] ...

require_once('../config.php');
require_once('lib.php');
require_once('edit_form.php');

$id = optional_param('id', 0, PARAM_INT); // Course id.
$categoryid = optional_param('category', 0, PARAM_INT); // Course category - can be changed in edit form.
$returnto = optional_param('returnto', 0, PARAM_ALPHANUM); // Generic navigation return page switch.
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL); // A return URL. returnto must also be set to 'url'.

if ($returnto === 'url' && confirm_sesskey() && $returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    if (!empty($id)) {
        $returnurl = new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $id));
    } else {
        $returnurl = new moodle_url($CFG->wwwroot . '/course/');
    }

    if ($returnto !== 0) {
        switch ($returnto) {
            case 'category':
                $returnurl = new moodle_url($CFG->wwwroot . '/course/index.php', array('categoryid' => $categoryid));
                break;
            case 'catmanage':
                $returnurl = new moodle_url($CFG->wwwroot . '/course/management.php', array('categoryid' => $categoryid));
                break;
            case 'topcatmanage':
                $returnurl = new moodle_url($CFG->wwwroot . '/course/management.php');
                break;
            case 'topcat':
                $returnurl = new moodle_url($CFG->wwwroot . '/course/');
                break;
            case 'pending':
                $returnurl = new moodle_url($CFG->wwwroot . '/course/pending.php');
                break;
        }
    }
}

$PAGE->set_pagelayout('admin');
if ($id) {
    $pageparams = array('id' => $id);
} else {
    $pageparams = array('category' => $categoryid);
}

if ($returnto !== 0) {
    $pageparams['returnto'] = $returnto;
    if ($returnto === 'url' && $returnurl) {
        $pageparams['returnurl'] = $returnurl;
    }
}

$PAGE->set_url('/course/edit.php', $pageparams);

// Basic access control checks.
if ($id) {
    if ($id == SITEID){
        throw new \moodle_exception('cannoteditsiteform');
    }
    $course = get_course($id);
    require_login($course);
    $course = course_get_format($course)->get_course();
    $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);
    $coursecontext = context_course::instance($course->id);
    require_capability('moodle/course:update', $coursecontext);

} else if ($categoryid) {
    $course = null;
    require_login();
    $category = $DB->get_record('course_categories', array('id'=>$categoryid), '*', MUST_EXIST);
    $catcontext = context_coursecat::instance($category->id);
    require_capability('moodle/course:create', $catcontext);
    $PAGE->set_context($catcontext);

} else {
    $course = null;
    require_login();
    $category = core_course_category::get_default();
    $catcontext = context_coursecat::instance($category->id);
    require_capability('moodle/course:create', $catcontext);
    $PAGE->set_context($catcontext);
}

if (isset($catcontext)) {
    $PAGE->set_secondary_active_tab('categorymain');
}

// Prepare course and the editor.
$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true);
$overviewfilesoptions = course_overviewfiles_options($course);

if (!empty($course)) {
    $editoroptions['context'] = $coursecontext;
    $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'course', 'summary', 0);
    $course = file_prepare_standard_editor($course, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
    if ($overviewfilesoptions) {
        file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, $coursecontext, 'course', 'overviewfiles', 0);
    }
    $course->tags = core_tag_tag::get_item_tags_array('core', 'course', $course->id);
} else {
    $editoroptions['context'] = $catcontext;
    $editoroptions['subdirs'] = 0;
    $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
    if ($overviewfilesoptions) {
        file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, null, 'course', 'overviewfiles', 0);
    }
}

$args = array(
    'course' => $course,
    'category' => $category,
    'editoroptions' => $editoroptions,
    'returnto' => $returnto,
    'returnurl' => $returnurl
);
$editform = new course_edit_form(null, $args);

if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    if (empty($course->id)) {
        $course = create_course($data, $editoroptions);
        $context = context_course::instance($course->id, MUST_EXIST);
        if (is_siteadmin($USER->id)) {
            $enroluser = $CFG->enroladminnewcourse;
        } else {
            $enroluser = !is_viewing($context, null, 'moodle/role:assign');
        }
        if (!empty($CFG->creatornewroleid) and $enroluser and !is_enrolled($context, null, 'moodle/role:assign')) {
            enrol_try_internal_enrol($course->id, $USER->id, $CFG->creatornewroleid);
        }
        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
    } else {
        update_course($data, $editoroptions);
        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
    }

    if (isset($data->saveanddisplay)) {
        redirect($courseurl);
    } else {
        redirect($returnurl);
    }
}

// Print the form.
$site = get_site();
$streditcoursesettings = get_string("editcoursesettings");
$straddnewcourse = get_string("addnewcourse");
$stradministration = get_string("administration");
$strcategories = get_string("categories");

if (!empty($course->id)) {
    $pagedesc = $streditcoursesettings;
    $title = $streditcoursesettings;
    $fullname = $course->fullname;
} else {
    $managementurl = new moodle_url('/course/management.php');
    $managementcaps = array('moodle/category:manage', 'moodle/course:create');
    if ($categoryid && !has_any_capability($managementcaps, context_system::instance())) {
        $managementurl->param('categoryid', $categoryid);
    }
    navigation_node::override_active_url(new moodle_url('/course/index.php', ['categoryid' => $category->id]), true);
    $PAGE->set_primary_active_tab('home');
    $PAGE->navbar->add(get_string('coursemgmt', 'admin'), $managementurl);

    $pagedesc = $straddnewcourse;
    $title = $straddnewcourse;
    $fullname = format_string($category->name);
    $PAGE->navbar->add($pagedesc);
}

$PAGE->set_title($title);
$PAGE->add_body_class('limitedwidth');
$PAGE->set_heading($fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagedesc);

// ── "CREATE WITH AI" BUTTON ──────────────────────────────────────────────
// Show only when creating a new course (no course ID yet)
if (empty($course->id)) {
    if (get_string_manager()->string_exists('createwithai', 'local_aicoursegen')) {
        $btnlabel = get_string('createwithai', 'local_aicoursegen');
    } else {
        $btnlabel = '✨ Create with AI';
    }
    
    $aiurl = new moodle_url('/local/aicoursegen/index.php', ['categoryid' => $category->id]);
    echo html_writer::div(
        html_writer::link($aiurl, $btnlabel, ['class' => 'btn btn-primary mb-3']),
        'ai-course-btn-container'
    );
}
// ─────────────────────────────────────────────────────────────────────────

$editform->display();

echo $OUTPUT->footer();