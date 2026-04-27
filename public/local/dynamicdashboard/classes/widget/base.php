<?php
// local/dynamicdashboard/classes/widget/base.php
namespace local_dynamicdashboard\widget;

abstract class base {
    protected $data;
    public $id;
    public $type;

    public function __construct(array $data, int $id = 0) {
        $this->data = $data;
        $this->id = $id;
        $this->type = self::get_type_from_class(get_class($this));
    }

    /**
     * Returns the renderable HTML for initial page load.
     */
    abstract public function render_initial(): string;

    /**
     * Returns cleaned data for external API.
     */
    abstract public function get_data(): array;

    private static function get_type_from_class(string $classname): string {
        $parts = explode('\\', $classname);
        return str_replace('_', '', end($parts)); // e.g. 'kpi_counter'
    }
}