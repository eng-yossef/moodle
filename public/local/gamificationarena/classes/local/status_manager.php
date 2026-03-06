<?php
// This file is part of Moodle - http://moodle.org/

namespace local_gamificationarena\local;

defined('MOODLE_INTERNAL') || die();

use grade_grade;

/**
 * Handles XP, ranking and leaderboard writes.
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
        $xp = max(25, (int)floor($score / 5)) + ($winner ? 100 : 0);

        $stats = $DB->get_record('local_ga_player_stats', ['courseid' => $courseid, 'userid' => $userid]);
        if (!$stats) {
            $stats = (object)[
                'courseid' => $courseid,
                'userid' => $userid,
                'totmatches' => 0,
                'wins' => 0,
                'losses' => 0,
                'totalxp' => 0,
                'rating' => 1000,
                'currentstreak' => 0,
                'beststreak' => 0,
                'timemodified' => $now,
            ];
            $stats->id = $DB->insert_record('local_ga_player_stats', $stats);
        }

        $stats->totmatches += 1;
        $stats->totalxp += $xp;
        $stats->rating += $winner ? 25 : -10;
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

        self::upsert_leaderboard($courseid, $userid, 'alltime', 'all', $xp, $winner);
        self::upsert_leaderboard($courseid, $userid, 'week', date('o-W'), $xp, $winner);

        // Optional gradebook integration as participation grade item score.
        self::push_grade($courseid, $userid, $score);

        cache_helper::purge_by_event('changesincourse');
    }

    /**
     * @param int $courseid
     * @param int $userid
     * @param string $period
     * @param string $periodkey
     * @param int $xp
     * @param bool $winner
     * @return void
     */
    protected static function upsert_leaderboard(int $courseid, int $userid, string $period, string $periodkey, int $xp, bool $winner): void {
        global $DB;

        $record = $DB->get_record('local_ga_leaderboard', [
            'courseid' => $courseid,
            'userid' => $userid,
            'period' => $period,
            'periodkey' => $periodkey,
        ]);

        if (!$record) {
            $record = (object)[
                'courseid' => $courseid,
                'userid' => $userid,
                'period' => $period,
                'periodkey' => $periodkey,
                'rankpoints' => 0,
                'wins' => 0,
                'losses' => 0,
                'xp' => 0,
                'timemodified' => time(),
            ];
            $record->id = $DB->insert_record('local_ga_leaderboard', $record);
        }

        $record->rankpoints += $winner ? 3 : 1;
        $record->xp += $xp;
        if ($winner) {
            $record->wins += 1;
        } else {
            $record->losses += 1;
        }
        $record->timemodified = time();
        $DB->update_record('local_ga_leaderboard', $record);
    }

    /**
     * @param int $courseid
     * @param int $userid
     * @param int $rawgrade
     * @return void
     */
    protected static function push_grade(int $courseid, int $userid, int $rawgrade): void {
        if (!function_exists('grade_update')) {
            return;
        }

        $grades = [$userid => (object)['rawgrade' => min(100, max(0, round($rawgrade / 10)))]];
        grade_update(
            'local/gamificationarena',
            $courseid,
            'local',
            'gamificationarena',
            0,
            0,
            $grades,
            [
                'itemname' => 'Gamification Arena',
                'gradetype' => GRADE_TYPE_VALUE,
                'grademax' => 100,
                'grademin' => 0,
            ]
        );
    }
}
