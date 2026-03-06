<?php
// This file is part of Moodle - http://moodle.org/

namespace local_gamificationarena\local;

defined('MOODLE_INTERNAL') || die();

use dml_exception;
use moodle_exception;

/**
 * Core service to create and run game matches.
 *
 * @package local_gamificationarena
 */
class match_manager {

    /** Matchmaking timeout in seconds */
    public const MATCHMAKING_TIMEOUT = 20;

    /** Question time limit in seconds */
    public const QUESTION_TIMELIMIT = 30;

    /**
     * Queue player into matchmaking.
     *
     * @param int $courseid
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    public static function queue_player(int $courseid, int $userid): array {
        global $DB;

        $now = time();

        $queuedmatch = $DB->get_record_select(
            'local_ga_matches',
            'courseid = :courseid AND status = :status AND timecreated >= :mintime',
            [
                'courseid' => $courseid,
                'status' => 'queued',
                'mintime' => $now - self::MATCHMAKING_TIMEOUT
            ],
            '*',
            IGNORE_MULTIPLE
        );

        if ($queuedmatch) {

            $exists = $DB->record_exists('local_ga_match_players', [
                'matchid' => $queuedmatch->id,
                'userid' => $userid
            ]);

            if (!$exists) {

                $DB->insert_record('local_ga_match_players', [
                    'matchid' => $queuedmatch->id,
                    'userid' => $userid,
                    'isbot' => 0,
                    'score' => 0,
                    'streak' => 0,
                    'answerscorrect' => 0,
                    'answerstime' => 0,
                    'timejoined' => $now
                ]);
            }

            $players = $DB->count_records('local_ga_match_players', [
                'matchid' => $queuedmatch->id
            ]);

            if ($players >= 2) {
                self::start_match((int)$queuedmatch->id, 'multiplayer');
            }

            return [
                'matchid' => (int)$queuedmatch->id,
                'status' => 'queued'
            ];
        }

        $matchid = $DB->insert_record('local_ga_matches', [
            'courseid' => $courseid,
            'mode' => 'bot',
            'status' => 'queued',
            'difficulty' => 'medium',
            'questioncount' => 7,
            'timecreated' => $now
        ]);

        $DB->insert_record('local_ga_match_players', [
            'matchid' => $matchid,
            'userid' => $userid,
            'isbot' => 0,
            'score' => 0,
            'streak' => 0,
            'answerscorrect' => 0,
            'answerstime' => 0,
            'timejoined' => $now
        ]);

        return [
            'matchid' => (int)$matchid,
            'status' => 'queued'
        ];
    }

    /**
     * Spawn bot opponent if matchmaking timeout reached.
     *
     * @param int $matchid
     * @throws dml_exception
     */
    public static function maybe_spawn_bot(int $matchid): void {
        global $DB;

        $match = $DB->get_record('local_ga_matches', ['id' => $matchid], '*', MUST_EXIST);

        if ($match->status !== 'queued') {
            return;
        }

        $players = $DB->get_records('local_ga_match_players', ['matchid' => $matchid]);

        if (count($players) > 1) {
            self::start_match($matchid, 'multiplayer');
            return;
        }

        if ((time() - (int)$match->timecreated) < self::MATCHMAKING_TIMEOUT) {
            return;
        }

        $botuserid = -$matchid;

        $DB->insert_record('local_ga_match_players', [
            'matchid' => $matchid,
            'userid' => $botuserid,
            'isbot' => 1,
            'score' => 0,
            'streak' => 0,
            'answerscorrect' => 0,
            'answerstime' => 0,
            'botdifficulty' => $match->difficulty,
            'timejoined' => time()
        ]);

        self::start_match($matchid, 'bot');
    }

    /**
     * Start match and load questions.
     *
     * @param int $matchid
     * @param string $mode
     * @throws dml_exception
     */
    public static function start_match(int $matchid, string $mode): void {
        global $DB;

        $match = $DB->get_record('local_ga_matches', ['id' => $matchid], '*', MUST_EXIST);

        if ($match->status === 'active') {
            return;
        }

        $questions = question_provider::get_match_questions(
            (int)$match->courseid,
            (int)$match->questioncount
        );

        if (empty($questions)) {
            throw new moodle_exception('No questions available for match');
        }

        $slot = 1;

        foreach ($questions as $question) {

            $DB->insert_record('local_ga_match_questions', [
                'matchid' => $matchid,
                'questionid' => $question->id,
                'slot' => $slot,
                'questiontext' => format_string($question->name),
                'timelimit' => self::QUESTION_TIMELIMIT
            ]);

            $slot++;
        }

        $match->mode = $mode;
        $match->status = 'active';
        $match->timestarted = time();

        $DB->update_record('local_ga_matches', $match);
    }

    /**
     * Get current match state.
     *
     * @param int $matchid
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    public static function get_match_state(int $matchid, int $userid): array {
        global $DB;

        self::maybe_spawn_bot($matchid);

        $match = $DB->get_record('local_ga_matches', ['id' => $matchid], '*', MUST_EXIST);

        $questions = array_values(
            $DB->get_records('local_ga_match_questions', ['matchid' => $matchid], 'slot ASC')
        );

        $answerscount = $DB->count_records('local_ga_answers', [
            'matchid' => $matchid,
            'userid' => $userid
        ]);

        $currentslot = min($answerscount + 1, max(count($questions), 1));

        $currentquestion = $questions[$currentslot - 1] ?? null;

        $players = array_values(
            $DB->get_records('local_ga_match_players', ['matchid' => $matchid], 'score DESC')
        );

        $deadline =
            ($match->timestarted ?: time()) +
            (($currentslot - 1) * self::QUESTION_TIMELIMIT) +
            self::QUESTION_TIMELIMIT;

        return [
            'matchid' => $matchid,
            'status' => $match->status,
            'mode' => $match->mode,
            'currentslot' => $currentslot,
            'questioncount' => count($questions),
            'question' => $currentquestion,
            'players' => $players,
            'timeleft' => max(0, $deadline - time())
        ];
    }

    /**
     * Submit answer and calculate score.
     *
     * @param int $matchid
     * @param int $userid
     * @param int $slot
     * @param string $answer
     * @param int $responsetime
     * @return array
     * @throws dml_exception
     */
    public static function submit_answer(
        int $matchid,
        int $userid,
        int $slot,
        string $answer,
        int $responsetime
    ): array {

        global $DB;

        if ($DB->record_exists('local_ga_answers', [
            'matchid' => $matchid,
            'userid' => $userid,
            'questionslot' => $slot
        ])) {
            throw new moodle_exception('Duplicate answer attempt');
        }

        $question = $DB->get_record(
            'local_ga_match_questions',
            ['matchid' => $matchid, 'slot' => $slot],
            '*',
            MUST_EXIST
        );

        $iscorrect = question_provider::validate_answer(
            (int)$question->questionid,
            $answer
        );

        $basepoints = $iscorrect ? 100 : 0;
        $speedbonus = $iscorrect ? max(0, 50 - max(0, $responsetime - 5) * 2) : 0;

        $player = $DB->get_record('local_ga_match_players', [
            'matchid' => $matchid,
            'userid' => $userid
        ], '*', MUST_EXIST);

        $streak = $iscorrect ? ($player->streak + 1) : 0;
        $streakbonus = $iscorrect ? ($streak * 20) : 0;

        $points = $basepoints + $speedbonus + $streakbonus;

        $DB->insert_record('local_ga_answers', [
            'matchid' => $matchid,
            'questionslot' => $slot,
            'userid' => $userid,
            'answerraw' => $answer,
            'iscorrect' => $iscorrect ? 1 : 0,
            'responsetime' => $responsetime,
            'pointsearned' => $points,
            'timecreated' => time()
        ]);

        $player->score += $points;
        $player->streak = $streak;
        $player->answerstime += $responsetime;

        if ($iscorrect) {
            $player->answerscorrect += 1;
        }

        $DB->update_record('local_ga_match_players', $player);

        bot_engine::play_turn_if_needed($matchid, $slot);

        self::finish_match_if_done($matchid);

        return [
            'iscorrect' => $iscorrect,
            'points' => $points,
            'score' => $player->score,
            'streak' => $streak
        ];
    }

    /**
     * Finish match when all questions answered.
     *
     * @param int $matchid
     * @throws dml_exception
     */
    public static function finish_match_if_done(int $matchid): void {
        global $DB;

        $match = $DB->get_record('local_ga_matches', ['id' => $matchid], '*', MUST_EXIST);

        if ($match->status !== 'active') {
            return;
        }

        $questions = $DB->count_records('local_ga_match_questions', ['matchid' => $matchid]);

        $players = $DB->get_records('local_ga_match_players', ['matchid' => $matchid]);

        foreach ($players as $player) {

            $count = $DB->count_records('local_ga_answers', [
                'matchid' => $matchid,
                'userid' => $player->userid
            ]);

            if ($count < $questions) {
                return;
            }
        }

        usort($players, static function($a, $b) {
            return $b->score <=> $a->score;
        });

        $winner = array_values($players)[0];

        $match->status = 'finished';
        $match->winnerid = $winner->userid;
        $match->timeended = time();

        $DB->update_record('local_ga_matches', $match);

        foreach ($players as $player) {

            if ($player->isbot) {
                continue;
            }

            stats_manager::update_for_player(
                (int)$match->courseid,
                (int)$player->userid,
                (int)$player->score,
                $player->userid === $winner->userid
            );
        }
    }
}