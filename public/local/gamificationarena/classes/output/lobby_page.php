<?php
/**
 * Lobby page renderable and templatable for the Gamification Arena.
 *
 * @package    local_gamificationarena
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gamificationarena\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;
use moodle_url;

/**
 * Lobby page context class.
 *
 * Prepares the data for the Mustache template 'local_gamificationarena/lobby_page'.
 */
class lobby_page implements renderable, templatable {

    /** @var int The course ID. */
    protected $courseid;

    /**
     * Constructor for the lobby page.
     *
     * @param int $courseid The course context the user is in.
     */
    public function __construct(int $courseid) {
        $this->courseid = $courseid;
    }

    /**
     * Exports the data for use in a Mustache template.
     *
     * @param renderer_base $output The renderer to use for outputting strings or images.
     * @return array The context data for the template.
     */
    public function export_for_template(renderer_base $output): array {
        global $USER;

        // Logic Fix: Ensure the URL is correctly constructed using the global moodle_url class.
        $leaderboardurl = new moodle_url('/local/gamificationarena/leaderboard.php', [
            'courseid' => $this->courseid,
        ]);

        return [
            'courseid'       => $this->courseid,
            'sesskey'        => sesskey(),
            'startchallenge' => get_string('startchallenge', 'local_gamificationarena'),
            'leaderboardurl' => $leaderboardurl->out(false),
            // User context additions (standard for lobby pages).
            'userfullname'   => fullname($USER),
            'userpicture'    => $output->user_picture($USER, ['size' => 100]),
            'welcome'        => get_string('welcome', 'local_gamificationarena', fullname($USER)),
        ];
    }
}