<?php
/**
 * Question provider for Gamification Arena.
 *
 * Compatible with Moodle 5.x question bank structure.
 *
 * @package    local_gamificationarena
 */

namespace local_gamificationarena\local;

defined('MOODLE_INTERNAL') || die();

use context_course;
use core_text;

class question_provider {

    /**
     * Get random questions from course question bank.
     */
    public static function get_match_questions(int $courseid, int $limit): array {
        global $DB;

        $context = context_course::instance($courseid);

        $sql = "SELECT q.id, q.name, q.questiontext
                  FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv
                       ON qv.questionbankentryid = qbe.id
                  JOIN {question} q
                       ON q.id = qv.questionid
                  JOIN {question_categories} qc
                       ON qc.id = qbe.questioncategoryid
                 WHERE qc.contextid = :contextid
                   AND qv.status = 'ready'
                   AND q.qtype IN ('multichoice','shortanswer','truefalse')";

        $questions = $DB->get_records_sql(
            $sql,
            ['contextid' => $context->id],
            0,
            $limit * 2
        );

        if (!$questions) {
            return [];
        }

        $questions = array_values($questions);

        shuffle($questions);

        return array_slice($questions, 0, $limit);
    }

    /**
     * Validate user answer.
     */
    public static function validate_answer(int $questionid, string $answer): bool {
        global $DB;

        if ($questionid <= 0) {
            return false;
        }

        $records = $DB->get_records(
            'question_answers',
            ['question' => $questionid]
        );

        if (!$records) {
            return false;
        }

        $answer = core_text::strtolower(trim(strip_tags($answer)));

        $maxfraction = 0;

        foreach ($records as $record) {

            $correct = core_text::strtolower(
                trim(strip_tags($record->answer))
            );

            if ($correct === $answer) {
                $maxfraction = max($maxfraction, (float)$record->fraction);
            }
        }

        return $maxfraction > 0.99;
    }
}