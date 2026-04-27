<?php
// local/dynamicdashboard/classes/data_provider/base.php
namespace local_dynamicdashboard\data_provider;

abstract class base {
    protected $user;

    public function __construct($user) {
        $this->user = $user;
    }

    abstract public function get_widgets(): array;

    /**
     * Helper to create a widget object.
     */
    protected function create_widget(string $type, array $data, int $id = 0): \local_dynamicdashboard\widget\base {
        $classname = '\\local_dynamicdashboard\\widget\\' . $type;
        if (!class_exists($classname)) {
            throw new \coding_exception("Widget type $type not found.");
        }
        return new $classname($data, $id);
    }
}