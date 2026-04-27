<?php
// local/dynamicdashboard/classes/widget/activity_stream.php
namespace local_dynamicdashboard\widget;

class activity_stream extends base {
    public function render_initial(): string {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core');
        $templatecontext = [
            'title'  => $this->data['title'] ?? '',
            'events' => $this->data['events'] ?? [],
            'id'     => $this->id,
        ];
        return $renderer->render_from_template('local_dynamicdashboard/widget_activity_stream', $templatecontext);
    }

    public function get_data(): array {
        return [
            'title'  => $this->data['title'],
            'events' => $this->data['events'],
        ];
    }
}