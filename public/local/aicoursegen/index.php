<?php
require_once('../../config.php');
require_once($CFG->libdir  . '/adminlib.php');
require_once($CFG->dirroot . '/local/aicoursegen/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$context  = context_course::instance($courseid);
require_login($courseid);
require_capability('local/aicoursegen:generatecourse', $context);

$PAGE->set_url('/local/aicoursegen/index.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('pluginname',   'local_aicoursegen'));
$PAGE->set_heading(get_string('pluginname', 'local_aicoursegen'));

$mform = new \local_aicoursegen\form\generate_form(null, ['courseid' => $courseid]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

if ($data = $mform->get_data()) {

    // ── 1. Retrieve the uploaded file from the draft area ─────────────────────
    $draftid = $data->file;
    if (!$draftid) {
        throw new moodle_exception('error', 'local_aicoursegen', '', 'No file uploaded');
    }

    $usercontext = context_user::instance($USER->id);
    $fs    = get_file_storage();
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftid, 'id', false);
    if (empty($files)) {
        throw new moodle_exception('error', 'local_aicoursegen', '', 'No file found in draft area');
    }
    $file = reset($files);

    // Save to a temporary location.
    $tempdir  = make_temp_directory('local_aicoursegen');
    $filepath = $tempdir . '/' . $file->get_filename();
    $file->copy_content_to($filepath);

    // Prepare parameters for FastAPI.
    $params = [
        'title'          => $data->title,
        'num_lectures'   => $data->numlectures,
        'include_quizzes'=> $data->includequizzes,
        'description'    => $data->description ?? '',
    ];

    try {
        // ── 2. Call FastAPI ───────────────────────────────────────────────────
        $result = local_aicoursegen_call_fastapi($filepath, $params);

        // ── 3. Create a new course ────────────────────────────────────────────
        $shortname   = 'AI_' . $result['course_id'];
        $newcourseid = local_aicoursegen_create_course($params['title'], $shortname);
        if (!$newcourseid || $newcourseid <= 0) {
            throw new moodle_exception('error', 'local_aicoursegen', '', 'Failed to create course.');
        }

        // ── 4. Download and add each lecture PDF ──────────────────────────────
        $api_base = rtrim(get_config('local_aicoursegen', 'api_base_url'), '/');
        $section  = 0;
        foreach ($result['lecture_pdf_urls'] as $relative_url) {
            $full_url      = $api_base . $relative_url;
            $pdfdata       = local_aicoursegen_download_file($full_url);
            $lecture_title = basename($relative_url, '.pdf');
            $lecture_title = str_replace(['_', '-'], ' ', $lecture_title);
            local_aicoursegen_add_file_resource($newcourseid, $lecture_title, $pdfdata, $section);
            $section++;
        }

        // ── 5. Import Moodle XML questions into the question bank ─────────────
        if (!empty($result['moodle_export_url'])) {

            require_once($CFG->libdir  . '/questionlib.php');
            require_once($CFG->dirroot . '/question/engine/lib.php');

            $full_xml_url = $api_base . $result['moodle_export_url'];
            $xmldata      = local_aicoursegen_download_file($full_xml_url);

            $newcontext = context_course::instance($newcourseid);

            // Ensure categories exist
            $topcat = $DB->get_record('question_categories',
                          ['contextid' => $newcontext->id, 'parent' => 0]);
            if (!$topcat) {
                $topcat            = new stdClass();
                $topcat->name      = 'top';
                $topcat->info      = '';
                $topcat->contextid = $newcontext->id;
                $topcat->parent    = 0;
                $topcat->sortorder = 0;
                $topcat->stamp     = make_unique_id_code();
                $topcat->id        = $DB->insert_record('question_categories', $topcat);
            }

            $qcategory = $DB->get_record('question_categories',
                             ['contextid' => $newcontext->id, 'parent' => $topcat->id]);
            if (!$qcategory) {
                $qcategory            = new stdClass();
                $qcategory->name      = get_string('defaultfor', 'question',
                                            $newcontext->get_context_name(false));
                $qcategory->info      = '';
                $qcategory->contextid = $newcontext->id;
                $qcategory->parent    = $topcat->id;
                $qcategory->sortorder = 999;
                $qcategory->stamp     = make_unique_id_code();
                $qcategory->id        = $DB->insert_record('question_categories', $qcategory);
            }

            // Parse XML and save questions
            $xml = @simplexml_load_string($xmldata);
            if ($xml === false) {
                throw new moodle_exception('error', 'local_aicoursegen', '',
                    'Could not parse quiz XML returned by the API.');
            }

            foreach ($xml->question as $qnode) {
                $qtype = (string)$qnode['type'];
                if ($qtype !== 'multichoice') {
                    continue;
                }

                // FIX 1: Create a separate $question object for the first argument
                $question                = new stdClass();
                $question->id            = 0; // 0 indicates a new question
                $question->qtype         = 'multichoice';

                // FIX 2: Use $qdata as the $form object (second argument)
                $qdata                         = new stdClass();
                
                // FIX 3: Format category as "id,contextid" as expected by save_question()
                $qdata->category               = $qcategory->id . ',' . $qcategory->contextid;
                $qdata->contextid              = $newcontext->id;
                $qdata->name                   = trim((string)$qnode->name);
                
                // FIX 4: Text fields MUST be arrays with 'text' and 'format' keys.
                // Passing them as strings causes PHP 8 to throw "Cannot access offset of type string on string"
                // when Moodle's core tries to access $form->questiontext['text'].
                $qdata->questiontext           = [
                    'text'   => (string)$qnode->questiontext->text,
                    'format' => FORMAT_HTML,
                ];
                
                $qdata->generalfeedback        = [
                    'text'   => '',
                    'format' => FORMAT_HTML,
                ];

                // Combined feedback fields must also be arrays to prevent TypeErrors in save_combined_feedback_helper()
                $qdata->correctfeedback        = ['text' => '', 'format' => FORMAT_HTML];
                $qdata->partiallycorrectfeedback = ['text' => '', 'format' => FORMAT_HTML];
                $qdata->incorrectfeedback      = ['text' => '', 'format' => FORMAT_HTML];

                $qdata->defaultmark            = 1;
                $qdata->penalty                = 0.3333333;
                $qdata->single                 = 1;
                $qdata->shuffleanswers         = 1;
                $qdata->answernumbering        = 'abc';

                $answers   = [];
                $fractions = [];
                $feedbacks = [];
                foreach ($qnode->answer as $ans) {
                    $answers[]   = ['text' => (string)$ans->text, 'format' => FORMAT_HTML];
                    $fractions[] = (float)$ans['fraction'] / 100.0;
                    $feedbacks[] = ['text' => isset($ans->feedback->text)
                                               ? (string)$ans->feedback->text : '',
                                    'format' => FORMAT_HTML];
                }
                $qdata->answer   = $answers;
                $qdata->fraction = $fractions;
                $qdata->feedback = $feedbacks;

                // FIX 5: Pass $question as first argument, $qdata as second argument ($form)
                question_bank::get_qtype('multichoice')->save_question($question, $qdata);
            }
        }

        // ── 6. Update course sections count ───────────────────────────────────
        $course              = get_course($newcourseid);
        $course->numsections = $section;
        update_course($course);

        // ── 7. Make the course visible ────────────────────────────────────────
        $course->visible = 1;
        update_course($course);

        // ── 8. Clean up the source PDF temp file ─────────────────────────────
        @unlink($filepath);

        // ── 9. Redirect to the new course ─────────────────────────────────────
        $courseurl = new moodle_url('/course/view.php', ['id' => $newcourseid]);
        redirect($courseurl,
            get_string('success', 'local_aicoursegen', ['courseurl' => $courseurl->out()]));

    } catch (Exception $e) {
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
        throw $e;
    }
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();