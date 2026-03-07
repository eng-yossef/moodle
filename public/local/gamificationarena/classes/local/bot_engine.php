<?php
/**
 * Simulated bot player engine.
 *
 * @package    local_gamificationarena
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gamificationarena\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Bot engine class.
 *
 * Handles simulated logic for bot opponents, including accuracy and response timing.
 */
class bot_engine {

    /**
     * Simulate a bot answer if a bot is present in the match.
     *
     * @param int $matchid The ID of the current match.
     * @param int $slot    The question slot being answered.
     * @return void
     */
    public static function play_turn_if_needed(int $matchid, int $slot): void {
        global $DB;

        // 1. Check if there is even a bot in this match.
        $bot = $DB->get_record('local_ga_match_players', [
            'matchid' => $matchid,
            'isbot'   => 1,
        ]);

        if (!$bot) {
            return;
        }

        // 2. Ensure the bot hasn't already answered this slot.
        $alreadyanswered = $DB->record_exists('local_ga_answers', [
            'matchid'      => $matchid,
            'questionslot' => $slot,
            'userid'       => $bot->userid,
        ]);

        if ($alreadyanswered) {
            return;
        }

        // 3. Determine bot performance based on difficulty.
        $difficulty = $bot->botdifficulty ?: 'medium';
        $accuracy = [
            'easy'   => 60,
            'medium' => 75,
            'hard'   => 90,
        ][$difficulty] ?? 75;

        $iscorrect = (random_int(1, 100) <= $accuracy);
        
        // Bots take between 3 and 25 seconds to "think".
        $responsetime = random_int(3, 25);

        // 4. Scoring Logic (Must match match_manager::submit_answer exactly).
        $basepoints = $iscorrect ? 100 : 0;
        
        // Logic Fix: Apply the 5-second grace period used in the manager.
        $speedbonus = $iscorrect ? max(0, 50 - (max(0, $responsetime - 5) * 2)) : 0;

        $streak = $iscorrect ? ($bot->streak + 1) : 0;
        $streakbonus = $iscorrect ? ($streak * 20) : 0;

        $points = (int) ($basepoints + $speedbonus + $streakbonus);

        // 5. Record the simulated answer.
        $DB->insert_record('local_ga_answers', [
            'matchid'      => $matchid,
            'questionslot' => $slot,
            'userid'       => $bot->userid,
            'answerraw'    => $iscorrect ? 'bot-correct' : 'bot-incorrect',
            'iscorrect'    => $iscorrect ? 1 : 0,
            'responsetime' => $responsetime,
            'pointsearned' => $points,
            'timecreated'  => time(),
        ]);

        // 6. Update bot player stats.
        $bot->score        += $points;
        $bot->streak        = $streak;
        $bot->answerstime  += $responsetime;

        if ($iscorrect) {
            $bot->answerscorrect += 1;
        }

        $DB->update_record('local_ga_match_players', $bot);
    }
}