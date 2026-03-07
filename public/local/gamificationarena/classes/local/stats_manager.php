<?php
/**
 * Handles XP, ranking and leaderboard writes for the Gamification Arena.
 *
 * @package    local_gamificationarena
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gamificationarena\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Stats manager class.
 */
class stats_manager {

    /**
     * Update aggregated stats for a finished match.
     *
     * @param int $courseid
     * @param int $userid
     * @param int $score
     * @param bool $winner
     * @return void
     */
    public static function update_for_player(int $courseid, int $userid, int $score, bool $winner): void {
        global $DB;

        $now = time();
        // Calculate XP: Base participation (25) + score performance + victory bonus.
        $xp = max(25, (int) floor($score / 5)) + ($winner ? 100 : 0);

        $stats = $DB->get_record('local_ga_player_stats', [
            'courseid' => $courseid,
            'userid'   => $userid,
        ]);

        if (!$stats) {
            $stats = (object) [
                'courseid'      => $courseid,
                'userid'        => $userid,
                'totmatches'    => 0,
                'wins'          => 0,
                'losses'        => 0,
                'totalxp'       => 0,
                'rating'        => 1000,
                'currentstreak' => 0,
                'beststreak'    => 0,
                'timemodified'  => $now,
            ];
            $stats->id = $DB->insert_record('local_ga_player_stats', $stats);
        }

        // Update values.
        $stats->totmatches += 1;
        $stats->totalxp    += $xp;
        $stats->rating     += $winner ? 25 : -10;

        if ($winner) {
            $stats->wins += 1;
            $stats->currentstreak += 1;
            $stats->beststreak = max($stats->beststreak, $stats->currentstreak);
        } else {
            $stats->losses += 1;
            $stats->currentstreak = 0;
        }

        $stats->timemodified = $now;
        $DB->update_record('local_ga_player_stats', $stats);

        // Update Leaderboards.
        self::upsert_leaderboard($courseid, $userid, 'alltime', 'all', $xp, $winner);
        self::upsert_leaderboard($courseid, $userid, 'week', date('o-W'), $xp, $winner);

        // Optional gradebook integration.
        self::push_grade($courseid, $userid, $score);

        // Clear course-related caches so the leaderboard UI refreshes.
        \cache_helper::purge_by_event('changesincourse');
    }

    /**
     * Upsert a record into the leaderboard table.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $period
     * @param string $periodkey
     * @param int $xp
     * @param bool $winner
     * @return void
     */
    protected static function upsert_leaderboard(
        int $courseid,
        int $userid,
        string $period,
        string $periodkey,
        int $xp,
        bool $winner
    ): void {
        global $DB;

        $record = $DB->get_record('local_ga_leaderboard', [
            'courseid'  => $courseid,
            'userid'    => $userid,
            'period'    => $period,
            'periodkey' => $periodkey,
        ]);

        if (!$record) {
            $record = (object) [
                'courseid'     => $courseid,
                'userid'       => $userid,
                'period'       => $period,
                'periodkey'    => $periodkey,
                'rankpoints'   => 0,
                'wins'         => 0,
                'losses'       => 0,
                'xp'           => 0,
                'timemodified' => time(),
            ];
            $record->id = $DB->insert_record('local_ga_leaderboard', $record);
        }

        $record->rankpoints += $winner ? 3 : 1;
        $record->xp         += $xp;

        if ($winner) {
            $record->wins += 1;
        } else {
            $record->losses += 1;
        }

        $record->timemodified = time();
        $DB->update_record('local_ga_leaderboard', $record);
    }

    /**
     * Pushes the match score to the Moodle Gradebook.
     *
     * @param int $courseid
     * @param int $userid
     * @param int $rawgrade
     * @return void
     */
    protected static function push_grade(int $courseid, int $userid, int $rawgrade): void {
        global $CFG;

        // Ensure the grade library is loaded.
        require_once($CFG->libdir . '/gradelib.php');

        if (!function_exists('grade_update')) {
            return;
        }

        // Scale the arena score (usually ~1000) to a 0-100 grade scale.
        $finalgrade = min(100, max(0, round($rawgrade / 10)));

        $grades = [];
        $grades[$userid] = (object) [
            'userid'   => $userid,
            'rawgrade' => $finalgrade,
        ];

        grade_update(
            'local/gamificationarena',
            $courseid,
            'local',
            'gamificationarena',
            0, // Item instance.
            0, // Item number.
            $grades,
            [
                'itemname'  => 'Gamification Arena Participation',
                'gradetype' => GRADE_TYPE_VALUE,
                'grademax'  => 100,
                'grademin'  => 0,
            ]
        );
    }
}