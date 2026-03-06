<?php
// This file is part of Moodle - http://moodle.org/

namespace local_gamificationarena\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Question source resolver for question bank, lessons and plugin pool.
 */
class question_provider {
    /**
     * Collect random questions for a match.
     *
     * @param int $courseid
     * @param int $limit
     * @return array
     */
    public static function get_match_questions(int $courseid, int $limit): array {
        global $DB;

        $sql = "SELECT q.id, q.name
                  FROM {question} q
                  JOIN {question_categories} qc ON qc.id = q.category
                 WHERE qc.contextid IN (
                    SELECT ctx.id FROM {context} ctx WHERE ctx.contextlevel = :contextlevel AND ctx.instanceid = :courseid
                 )
                   AND q.hidden = 0
                   AND q.parent = 0";
        $questions = $DB->get_records_sql($sql, ['contextlevel' => CONTEXT_COURSE, 'courseid' => $courseid]);

        if (count($questions) < $limit) {
            $custom = $DB->get_records('local_ga_match_questions', null, '', 'questionid AS id, questiontext AS name', 0, $limit - count($questions));
            $questions = array_merge($questions, $custom);
        }

        shuffle($questions);
        return array_slice($questions, 0, $limit);
    }

    /**
     * Validate answer server-side.
     *
     * @param int $questionid
     * @param string $answer
     * @return bool
     */
    public static function validate_answer(int $questionid, string $answer): bool {
        global $DB;

        if ($questionid <= 0) {
            return random_int(0, 1) === 1;
        }

        $fraction = (float)$DB->get_field_sql(
            "SELECT MAX(qa.fraction)
               FROM {question_answers} qa
              WHERE qa.question = :questionid
                AND LOWER(qa.answer) = LOWER(:answer)",
            ['questionid' => $questionid, 'answer' => trim($answer)]
        );

        return $fraction > 0.99;
    }
}
