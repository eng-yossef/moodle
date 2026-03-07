<?php
/**
 * Core service to create and run game matches.
 *
 * @package    local_gamificationarena
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gamificationarena\local;

defined('MOODLE_INTERNAL') || die();

use dml_exception;
use moodle_exception;

class match_manager {

    public const MATCHMAKING_TIMEOUT = 20;
    public const QUESTION_TIMELIMIT = 30;

    /**
     * Queue a player into matchmaking or return their existing active match.
     */
  public static function queue_player(int $courseid, int $userid): array {
    global $DB;
    $now = time();

    // 1. Check if user is already in an active or queued match for this course.
    $sql = "SELECT m.* FROM {local_ga_matches} m
              JOIN {local_ga_match_players} p ON p.matchid = m.id
             WHERE p.userid = :userid 
               AND m.courseid = :courseid
               AND m.status IN ('queued', 'active')
          ORDER BY m.timecreated DESC";
    
    $existing = $DB->get_record_sql($sql, ['userid' => $userid, 'courseid' => $courseid], IGNORE_MULTIPLE);

    if ($existing) {
        return ['matchid' => (int)$existing->id, 'status' => $existing->status];
    }

    // 2. Look for an available queued match that hasn't timed out.
    $queuedmatch = $DB->get_record_select(
        'local_ga_matches',
        'courseid = :courseid AND status = :status AND timecreated >= :mintime',
        ['courseid' => $courseid, 'status' => 'queued', 'mintime' => $now - self::MATCHMAKING_TIMEOUT],
        '*',
        IGNORE_MULTIPLE
    );

    if ($queuedmatch) {
        // Check if user is already in the match (to avoid duplicates)
        if (!$DB->record_exists('local_ga_match_players', ['matchid' => $queuedmatch->id, 'userid' => $userid])) {
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

        // Count humans after insertion
        $humans = $DB->count_records('local_ga_match_players', ['matchid' => $queuedmatch->id, 'isbot' => 0]);

        // If 2 or more humans, start multiplayer match immediately
        if ($humans >= 2) {
            self::start_match((int)$queuedmatch->id, 'multiplayer');
        }

        return ['matchid' => (int)$queuedmatch->id, 'status' => $queuedmatch->status];
    }

    // 3. No queued match found -> Create new match
    $matchid = $DB->insert_record('local_ga_matches', [
        'courseid' => $courseid,
        'mode' => 'bot', // Default mode; upgraded if another human joins
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

    return ['matchid' => (int)$matchid, 'status' => 'queued'];
}

    /**
     * Spawn bot if timeout reached and still solo.
     */
    public static function maybe_spawn_bot(int $matchid): void {
    global $DB;

    $match = $DB->get_record('local_ga_matches', ['id' => $matchid], '*', MUST_EXIST);

    // Only act on queued matches
    if ($match->status !== 'queued') {
        return;
    }

    // Count human players
    $humans = $DB->count_records('local_ga_match_players', ['matchid' => $matchid, 'isbot' => 0]);

    // 1. If 2 or more humans joined, start multiplayer immediately
    if ($humans >= 2) {
        self::start_match($matchid, 'multiplayer');
        return;
    }

    // 2. Check if matchmaking timeout passed
    $elapsed = time() - (int)$match->timecreated;
    if ($elapsed < self::MATCHMAKING_TIMEOUT) {
        return; // Still waiting for second human
    }

    // 3. Spawn bot if not already added
    $botuserid = -$matchid; // unique negative ID per match
    if (!$DB->record_exists('local_ga_match_players', ['matchid' => $matchid, 'userid' => $botuserid])) {
        $DB->insert_record('local_ga_match_players', [
            'matchid' => $matchid,
            'userid' => $botuserid,
            'isbot' => 1,
            'score' => 0,
            'streak' => 0,
            'answerscorrect' => 0,
            'answerstime' => 0,
            'timejoined' => time()
        ]);
    }

    // 4. Start match in bot mode
    self::start_match($matchid, 'bot');
}

    /**
     * Start match and generate question slots.
     */
    public static function start_match(int $matchid, string $mode): void {
        global $DB;

        $match = $DB->get_record('local_ga_matches', ['id' => $matchid], '*', MUST_EXIST);
        if ($match->status !== 'queued') {
            return;
        }

        // Ensure we have a question provider (assuming this class exists in your namespace).
        $questions = question_provider::get_match_questions((int)$match->courseid, (int)$match->questioncount);
        if (empty($questions)) {
            throw new moodle_exception('noquestions', 'local_gamificationarena');
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
     * Get state for AJAX polling.
     */
  public static function get_match_state(int $matchid, int $userid): array {
    global $DB;

    // 1. Check for bot timeout and start match if enough humans
    self::maybe_spawn_bot($matchid);

    $match = $DB->get_record('local_ga_matches', ['id' => $matchid], '*', MUST_EXIST);

    // 2. If match is still queued, check if enough human players to start it
    if ($match->status === 'queued') {
        $humans = $DB->count_records('local_ga_match_players', ['matchid' => $matchid, 'isbot' => 0]);
        if ($humans >= 2) {
            self::start_match($matchid, 'multiplayer');
            $match = $DB->get_record('local_ga_matches', ['id' => $matchid], '*', MUST_EXIST);
        }
    }

    // 3. Load questions and current slot
    $questions = array_values($DB->get_records('local_ga_match_questions', ['matchid' => $matchid], 'slot ASC'));
    $answerscount = $DB->count_records('local_ga_answers', ['matchid' => $matchid, 'userid' => $userid]);

    $currentslot = $answerscount + 1;
    $totalquestions = count($questions);

    // Only return the needed question data for frontend
    $currentquestion = ($currentslot <= $totalquestions && isset($questions[$currentslot - 1]))
        ? ['questiontext' => $questions[$currentslot - 1]->questiontext, 'slot' => $currentslot]
        : null;

    // 4. Load players for scoreboard
    $players = array_values($DB->get_records('local_ga_match_players', ['matchid' => $matchid], 'score DESC'));

    // 5. Calculate time left
    $deadline = ($match->timestarted ?: time()) + ($currentslot * self::QUESTION_TIMELIMIT);
    $timeleft = ($currentslot <= $totalquestions) ? max(0, $deadline - time()) : 0;

    return [
        'matchid' => $matchid,
        'status' => $match->status,
        'mode' => $match->mode,
        'currentslot' => $currentslot,
        'questioncount' => $totalquestions,
        'question' => $currentquestion,
        'players' => array_map(function($p) {
            return [
                'userid' => $p->userid,
                'score' => $p->score,
                'isbot' => $p->isbot
            ];
        }, $players),
        'timeleft' => $timeleft
    ];
}

    /**
     * Handle answer submission and scoring.
     */
    public static function submit_answer(int $matchid, int $userid, int $slot, string $answer, int $responsetime): array {
        global $DB;

        if ($DB->record_exists('local_ga_answers', ['matchid' => $matchid, 'userid' => $userid, 'questionslot' => $slot])) {
            throw new moodle_exception('duplicateanswer', 'local_gamificationarena');
        }

        $question = $DB->get_record('local_ga_match_questions', ['matchid' => $matchid, 'slot' => $slot], '*', MUST_EXIST);
        $iscorrect = question_provider::validate_answer((int)$question->questionid, $answer);

        $basepoints = $iscorrect ? 100 : 0;
        $validresponsetime = min($responsetime, self::QUESTION_TIMELIMIT);
        
        // Speed bonus: max 50 points, decreasing by 2 for every second after 5 seconds.
        $speedbonus = $iscorrect ? max(0, 50 - (max(0, $validresponsetime - 5) * 2)) : 0;

        $player = $DB->get_record('local_ga_match_players', ['matchid' => $matchid, 'userid' => $userid], '*', MUST_EXIST);
        $streak = $iscorrect ? ($player->streak + 1) : 0;
        $streakbonus = $iscorrect ? ($streak * 20) : 0;
        $points = (int) ($basepoints + $speedbonus + $streakbonus);

        $DB->insert_record('local_ga_answers', [
            'matchid' => $matchid,
            'questionslot' => $slot,
            'userid' => $userid,
            'answerraw' => $answer,
            'iscorrect' => $iscorrect ? 1 : 0,
            'responsetime' => $validresponsetime,
            'pointsearned' => $points,
            'timecreated' => time()
        ]);

        $player->score += $points;
        $player->streak = $streak;
        $player->answerstime += $validresponsetime;
        if ($iscorrect) {
            $player->answerscorrect += 1;
        }
        $DB->update_record('local_ga_match_players', $player);

        self::finish_match_if_done($matchid);

        return [
            'iscorrect' => (bool)$iscorrect, 
            'points' => $points, 
            'score' => $player->score, 
            'streak' => $streak
        ];
    }

    /**
     * Mark match as finished if all humans are done.
     */
    public static function finish_match_if_done(int $matchid): void {
        global $DB;

        $match = $DB->get_record('local_ga_matches', ['id' => $matchid], '*', MUST_EXIST);
        if ($match->status !== 'active') {
            return;
        }

        $totalquestions = $DB->count_records('local_ga_match_questions', ['matchid' => $matchid]);
        $players = $DB->get_records('local_ga_match_players', ['matchid' => $matchid]);

        foreach ($players as $player) {
            $count = $DB->count_records('local_ga_answers', ['matchid' => $matchid, 'userid' => $player->userid]);
            if ($count < $totalquestions) {
                return;
            }
        }

        // Sort by score to find winner.
        usort($players, static fn($a, $b) => $b->score <=> $a->score);
        $winner = reset($players);

        $match->status = 'finished';
        $match->winnerid = $winner->userid;
        $match->timeended = time();
        $DB->update_record('local_ga_matches', $match);
    }
}