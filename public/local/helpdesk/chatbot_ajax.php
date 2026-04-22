<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @package    local_helpdesk
// @copyright  2026 Helpdesk Plugin
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_helpdesk\faq_service;
use local_helpdesk\ai_service;

require_login();

$context = context_system::instance();
require_capability('local/helpdesk:viewowntickets', $context);

header('Content-Type: application/json');

$question = optional_param('question', '', PARAM_TEXT);

if (!$question) {
    echo json_encode(['error' => get_string('chatbotemptyquestion', 'local_helpdesk')]);
    exit;
}

// Step 1: Try FAQ service
$faqanswer = faq_service::find_answer($question);
if ($faqanswer) {
    echo json_encode(['reply' => $faqanswer, 'escalated' => false]);
    exit;
}

// Step 2: Try AI service
$ai = ai_service::ask_llm($question);
if (!$ai) {
    echo json_encode(['error' => 'AI service unavailable. Please try again later.']);
    exit;
}

if (!empty($ai['escalate'])) {
    // Step 3: Escalation – create a ticket
    $opencount = $DB->count_records_select(
        'local_helpdesk_tickets',
        "userid = :userid AND status IN ('open','inprogress')",
        ['userid' => $USER->id]
    );

    if ($opencount >= 3) {
        echo json_encode(['reply' => get_string('chatbotmaxopen', 'local_helpdesk'), 'escalated' => false]);
        exit;
    }

    $now = time();
    $ticket = new stdClass();
    $ticket->userid = $USER->id;
    $ticket->subject = clean_param($ai['ticket_summary'], PARAM_TEXT);
    $ticket->description = $question . "\n\nAI Analysis:\n" . $ai['ticket_summary'];
    $ticket->descriptionformat = FORMAT_HTML;
    $ticket->priority = $ai['priority'] ?? 'medium';
    $ticket->status = 'open';
    $ticket->timecreated = $now;
    $ticket->timemodified = $now;

    $ticketid = $DB->insert_record('local_helpdesk_tickets', $ticket);

    // Log the escalation
    $DB->insert_record('local_helpdesk_ticket_log', (object)[
        'ticketid' => $ticketid,
        'userid' => $USER->id,
        'action' => 'created_by_chatbot',
        'detail' => 'Ticket auto-created from chatbot escalation.',
        'timecreated' => $now,
    ]);

    echo json_encode([
        'reply' => get_string('chatbotfallbackcreated', 'local_helpdesk', $ticketid),
        'escalated' => true,
        'ticketid' => $ticketid
    ]);
    exit;
}

// Standard AI response
echo json_encode(['reply' => $ai['answer'], 'escalated' => false]);