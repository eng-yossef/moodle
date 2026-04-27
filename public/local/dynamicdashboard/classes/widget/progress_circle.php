<?php
namespace local_dynamicdashboard\widget;

class progress_circle extends base {
    public function render_initial(): string {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core');
        return $renderer->render_from_template('local_dynamicdashboard/widget_progress_circle', $this->data + ['id' => $this->id]);
    }

    public function get_data(): array {
        return $this->data;
    }
}