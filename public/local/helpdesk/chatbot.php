<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Customer-service chatbot page.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
use local_helpdesk\chatbot_service;

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/helpdesk/chatbot.php');
$PAGE->set_title(get_string('chatbottitle', 'local_helpdesk'));
$PAGE->set_heading(get_string('chatbottitle', 'local_helpdesk'));
$PAGE->set_pagelayout('standard');

require_capability('local/helpdesk:viewowntickets', $context);

$question = optional_param('question', '', PARAM_TEXT);
$response = '';
$createdticketid = 0;

if (data_submitted() && confirm_sesskey()) {
    $question = trim($question);

    if ($question === '') {
        $response = get_string('chatbotemptyquestion', 'local_helpdesk');
    } else {
        $response = chatbot_service::answer_question($question);

        if ($response === null) {
            require_capability('local/helpdesk:submitticket', $context);

            $opencount = $DB->count_records_select(
                'local_helpdesk_tickets',
                "userid = :userid AND status IN ('open','inprogress')",
                ['userid' => $USER->id]
            );

            if ($opencount >= 3) {
                $response = get_string('chatbotmaxopen', 'local_helpdesk');
            } else {
                $now = time();
                $subject = get_string('chatbotticketsubject', 'local_helpdesk');
                $description = get_string('chatbotticketdesc', 'local_helpdesk', format_string($question));

                $createdticketid = $DB->insert_record('local_helpdesk_tickets', (object)[
                    'userid' => $USER->id,
                    'courseid' => null,
                    'subject' => clean_param($subject, PARAM_TEXT),
                    'description' => $description,
                    'descriptionformat' => FORMAT_HTML,
                    'priority' => 'medium',
                    'status' => 'open',
                    'timecreated' => $now,
                    'timemodified' => $now,
                ]);

                $DB->insert_record('local_helpdesk_ticket_log', (object)[
                    'ticketid' => $createdticketid,
                    'userid' => $USER->id,
                    'action' => 'created_by_chatbot',
                    'detail' => 'Ticket auto-created from chatbot escalation.',
                    'timecreated' => $now,
                ]);

                $response = get_string('chatbotfallbackcreated', 'local_helpdesk', $createdticketid);
            }
        }
    }
}

echo $OUTPUT->header();
?>
<div class="container">
    <div class="card">
        <div class="card-body">
            <h3 class="card-title"><?php echo get_string('chatbottitle', 'local_helpdesk'); ?></h3>
            <p class="text-muted"><?php echo get_string('chatbotintro', 'local_helpdesk'); ?></p>

            <form method="post" action="<?php echo (new moodle_url('/local/helpdesk/chatbot.php'))->out(false); ?>">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                <div class="form-group">
                    <label for="chatbot-question"><?php echo get_string('chatbotquestionlabel', 'local_helpdesk'); ?></label>
                    <textarea
                        id="chatbot-question"
                        name="question"
                        class="form-control"
                        rows="4"
                        required
                    ><?php echo s($question); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary mt-2"><?php echo get_string('chatbotaskbutton', 'local_helpdesk'); ?></button>
                <a href="<?php echo (new moodle_url('/local/helpdesk/index.php'))->out(false); ?>" class="btn btn-secondary mt-2">
                    <?php echo get_string('mytickets', 'local_helpdesk'); ?>
                </a>
            </form>

            <?php if ($response !== ''): ?>
                <hr>
                <h4><?php echo get_string('chatbotanswerlabel', 'local_helpdesk'); ?></h4>
                <div class="alert <?php echo $createdticketid ? 'alert-warning' : 'alert-info'; ?> mb-0">
                    <?php echo format_text($response, FORMAT_HTML); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php

echo $OUTPUT->footer();
