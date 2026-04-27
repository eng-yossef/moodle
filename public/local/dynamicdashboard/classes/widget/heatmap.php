<?php
namespace local_dynamicdashboard\widget;

class heatmap extends base {

    public function render_initial(): string {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $data = $this->data ?? [];

$rows = [];

foreach ($data['rows'] ?? [] as $i => $rowlabel) {
    $rows[] = [
        'label' => $rowlabel,
        'values' => $data['values'][$i] ?? []
    ];
}

        return $renderer->render_from_template(
    'local_dynamicdashboard/widget_heatmap',
    [
        'id' => (int)$this->id,
        'title' => $data['title'] ?? 'Heatmap',
        'rows' => $rows,
        'columns' => $data['columns'] ?? []
    ]
);
    }

    public function get_data(): array {
        return $this->data ?? [];
    }
}