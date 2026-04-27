<?php
// local/dynamicdashboard/classes/widget/kpi_counter.php
namespace local_dynamicdashboard\widget;

use \html_writer;

class kpi_counter extends base {
    public function render_initial(): string {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core');
        $templatecontext = [
    'id'    => $this->id,
    'label' => $this->data['label'],
    'value' => $this->data['value'],
];
        return $renderer->render_from_template('local_dynamicdashboard/widget_kpi', $templatecontext);
    }

    public function get_data(): array {
    return [
        'id'    => $this->id,   
        'label' => $this->data['label'],
        'value' => $this->data['value'],
    ];
}
}