<?php
// This file is part of Moodle - http://moodle.org/

namespace local_gamificationarena\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Lobby page context.
 */
class lobby_page implements renderable, templatable {
    /** @var int */
    protected $courseid;

    /**
     * @param int $courseid
     */
    public function __construct(int $courseid) {
        $this->courseid = $courseid;
    }

    /**
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'courseid' => $this->courseid,
            'startchallenge' => get_string('startchallenge', 'local_gamificationarena'),
            'leaderboardurl' => (new \moodle_url('/local/gamificationarena/leaderboard.php', ['courseid' => $this->courseid]))->out(false),
            'sesskey' => sesskey(),
        ];
    }
}
