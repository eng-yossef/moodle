<?php
// local/dynamicdashboard/classes/external.php

namespace local_dynamicdashboard;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

class external extends external_api {

    /**
     * Describes the parameters.
     */
    public static function get_widgets_parameters() {
        return new external_function_parameters([
            'widgetids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Widget ID'),
                'List of widget IDs',
                VALUE_DEFAULT,
                []
            ),
            'since' => new external_value(
                PARAM_INT,
                'Timestamp',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * Returns widget data.
     */
    public static function get_widgets($widgetids = [], $since = 0) {
        global $USER;

        $context = context_system::instance();
        self::validate_context($context);

        require_capability('local/dynamicdashboard:view', $context);

        $dashboard = dashboard::get_for_user($USER);
        $widgets = $dashboard->get_widgets();

        $result = [];

        foreach ($widgets as $widget) {

            if (!empty($widgetids) && !in_array($widget->id, $widgetids)) {
                continue;
            }

            $result[] = [
                'id' => $widget->id,
                'type' => $widget->type,
                'data' => json_encode($widget->get_data()),
                'updated' => time(),
            ];
        }

        return ['widgets' => $result];
    }

    /**
     * Return structure.
     */
    public static function get_widgets_returns() {
        return new external_single_structure([
            'widgets' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Widget ID'),
                    'type' => new external_value(PARAM_ALPHAEXT, 'Widget type'),
                    'data' => new external_value(PARAM_RAW, 'JSON data'),
                    'updated' => new external_value(PARAM_INT, 'Timestamp'),
                ])
            ),
        ]);
    }
}