<?php
// This file is part of Moodle - http://moodle.org/

namespace local_gamificationarena\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Simulated bot player.
 */
class bot_engine {
    /**
     * Simulate a bot answer if a bot is present.
     *
     * @param int $matchid
     * @param int $slot
     * @return void
     */
    public static function play_turn_if_needed(int $matchid, int $slot): void {
        global $DB;

        $bot = $DB->get_record('local_ga_match_players', ['matchid' => $matchid, 'isbot' => 1]);
        if (!$bot) {
            return;
        }

        if ($DB->record_exists('local_ga_answers', ['matchid' => $matchid, 'questionslot' => $slot, 'userid' => $bot->userid])) {
            return;
        }

        $difficulty = $bot->botdifficulty ?: 'medium';
        $accuracy = ['easy' => 0.6, 'medium' => 0.75, 'hard' => 0.9][$difficulty] ?? 0.75;
        $iscorrect = (mt_rand(0, 100) / 100) <= $accuracy;
        $responsetime = random_int(3, 26);

        $streak = $iscorrect ? ($bot->streak + 1) : 0;
        $points = $iscorrect ? 100 + max(0, 50 - ($responsetime * 2)) + ($streak * 20) : 0;

        $DB->insert_record('local_ga_answers', [
            'matchid' => $matchid,
            'questionslot' => $slot,
            'userid' => $bot->userid,
            'answerraw' => $iscorrect ? 'bot-correct' : 'bot-incorrect',
            'iscorrect' => $iscorrect ? 1 : 0,
            'responsetime' => $responsetime,
            'pointsearned' => $points,
            'timecreated' => time(),
        ]);

        $bot->score += $points;
        $bot->streak = $streak;
        $bot->answerstime += $responsetime;
        if ($iscorrect) {
            $bot->answerscorrect += 1;
        }
        $DB->update_record('local_ga_match_players', $bot);
    }
}
