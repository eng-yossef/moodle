<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_helpdesk
// @copyright  2026 Helpdesk Plugin
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/helpdesk:viewowntickets', $context);

header('Content-Type: application/json');

$subject     = required_param('subject', PARAM_TEXT);
$priority    = required_param('priority', PARAM_ALPHA);
$description = required_param('description', PARAM_TEXT);
$question    = optional_param('question', '', PARAM_TEXT); // original user message (for logging)

$validpriorities = ['low', 'medium', 'high', 'urgent'];
if (!in_array($priority, $validpriorities)) {
    $priority = 'medium';
}

// Double‑check open tickets limit
$opencount = $DB->count_records_select(
    'local_helpdesk_tickets',
    "userid = :userid AND status IN ('open','inprogress')",
    ['userid' => $USER->id]
);
if ($opencount >= 3) {
    echo json_encode(['error' => get_string('chatbotmaxopen', 'local_helpdesk')]);
    exit;
}

$now = time();
$ticket = new stdClass();
$ticket->userid          = $USER->id;
$ticket->subject         = $subject;
$ticket->description     = $description;
$ticket->descriptionformat = FORMAT_HTML;
$ticket->priority        = $priority;
$ticket->status          = 'open';
$ticket->timecreated     = $now;
$ticket->timemodified    = $now;

$ticketid = $DB->insert_record('local_helpdesk_tickets', $ticket);

$DB->insert_record('local_helpdesk_ticket_log', (object)[
    'ticketid'   => $ticketid,
    'userid'     => $USER->id,
    'action'     => 'created_by_chatbot',
    'detail'     => 'Ticket created after user confirmation.',
    'timecreated'=> $now,
]);

echo json_encode([
    'success'   => true,
    'ticketid'  => $ticketid,
    'reply'     => get_string('chatbotfallbackcreated', 'local_helpdesk', $ticketid)
]);