<?php
// local/dynamicdashboard/classes/dashboard.php
namespace local_dynamicdashboard;

defined('MOODLE_INTERNAL') || die();

use local_dynamicdashboard\data_provider\admin;
use local_dynamicdashboard\data_provider\teacher;
use local_dynamicdashboard\data_provider\user;

class dashboard {
    /** @var data_provider\base */
    private $provider;

    public function __construct($user) {
        $this->provider = $this->resolve_provider($user);
    }

    public static function get_for_user($user) {
        return new static($user);
    }

    private function resolve_provider($user) {
        if (is_siteadmin($user->id)) {
            return new admin($user);
        }
        // Teacher check: capability to view course reports at system level?
        $context = \context_system::instance();
        if (has_capability('moodle/course:viewhiddenactivities', $context)) {
            return new teacher($user);
        }
        return new user($user);
    }

    public function get_widgets(): array {
        return $this->provider->get_widgets();
    }
}