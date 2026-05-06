<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_watermark', get_string('pluginname', 'local_watermark'));
    $ADMIN->add('localplugins', $settings);

    // ---------- General ----------
    $settings->add(new admin_setting_configcheckbox(
        'local_watermark/enabled',
        get_string('enabled', 'local_watermark'),
        get_string('enabled_desc', 'local_watermark'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_watermark/template',
        get_string('watermarktpl', 'local_watermark'),
        get_string('watermarktpl_desc', 'local_watermark'),
        'User: {username} | ID: {userid}',
        PARAM_TEXT
    ));

    // ---------- Corner Watermark ----------
    $settings->add(new admin_setting_heading('local_watermark/corner_heading',
        get_string('corner_heading', 'local_watermark'),
        get_string('corner_heading_desc', 'local_watermark')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_watermark/corner_enabled',
        get_string('corner_enabled', 'local_watermark'),
        get_string('corner_enabled_desc', 'local_watermark'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_watermark/corner_fontsize',
        get_string('corner_fontsize', 'local_watermark'),
        get_string('corner_fontsize_desc', 'local_watermark'),
        '10',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_watermark/corner_textcolor',
        get_string('corner_textcolor', 'local_watermark'),
        get_string('corner_textcolor_desc', 'local_watermark'),
        '#969696'   // same as RGB(150,150,150)
    ));


    $settings->add(new admin_setting_configtext(
        'local_watermark/corner_margin',
        get_string('corner_margin', 'local_watermark'),
        get_string('corner_margin_desc', 'local_watermark'),
        '10',
        PARAM_INT
    ));

    // Which corners to show (multi‑checkbox)
    $corners = [
        'top-left'     => get_string('corner_topleft', 'local_watermark'),
        'top-right'    => get_string('corner_topright', 'local_watermark'),
        'bottom-left'  => get_string('corner_bottomleft', 'local_watermark'),
        'bottom-right' => get_string('corner_bottomright', 'local_watermark'),
    ];
    $settings->add(new admin_setting_configmulticheckbox(
        'local_watermark/corner_positions',
        get_string('corner_positions', 'local_watermark'),
        get_string('corner_positions_desc', 'local_watermark'),
        ['top-left' => 1, 'bottom-left' => 1, 'bottom-right' => 1], // default: three corners as before (top-right excluded, logo goes there)
        $corners
    ));

    // ---------- Diagonal Watermark ----------
    $settings->add(new admin_setting_heading('local_watermark/diagonal_heading',
        get_string('diagonal_heading', 'local_watermark'),
        get_string('diagonal_heading_desc', 'local_watermark')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_watermark/diagonal_enabled',
        get_string('diagonal_enabled', 'local_watermark'),
        get_string('diagonal_enabled_desc', 'local_watermark'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_watermark/diagonal_fontsize',
        get_string('diagonal_fontsize', 'local_watermark'),
        get_string('diagonal_fontsize_desc', 'local_watermark'),
        '25',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_watermark/diagonal_textcolor',
        get_string('diagonal_textcolor', 'local_watermark'),
        get_string('diagonal_textcolor_desc', 'local_watermark'),
        '#C8C8C8'   // RGB(200,200,200)
    ));



    $settings->add(new admin_setting_configtext(
        'local_watermark/diagonal_angle',
        get_string('diagonal_angle', 'local_watermark'),
        get_string('diagonal_angle_desc', 'local_watermark'),
        '45',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_watermark/diagonal_offset_x',
        get_string('diagonal_offset_x', 'local_watermark'),
        get_string('diagonal_offset_x_desc', 'local_watermark'),
        '0',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_watermark/diagonal_offset_y',
        get_string('diagonal_offset_y', 'local_watermark'),
        get_string('diagonal_offset_y_desc', 'local_watermark'),
        '0',
        PARAM_INT
    ));

    // ---------- Logo ----------
    $settings->add(new admin_setting_heading('local_watermark/logo_heading',
        get_string('logo_heading', 'local_watermark'),
        get_string('logo_heading_desc', 'local_watermark')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_watermark/logo_enabled',
        get_string('logo_enabled', 'local_watermark'),
        get_string('logo_enabled_desc', 'local_watermark'),
        1
    ));

    // Upload a logo file (stored in the plugin's file area)
    $settings->add(new admin_setting_configstoredfile(
        'local_watermark/logo',
        get_string('logo', 'local_watermark'),
        get_string('logo_desc', 'local_watermark'),
        'logo',                  // filearea
        0,                       // itemid (0 = site-wide)
        [
            'maxfiles' => 1,
            'accepted_types' => ['.png', '.jpg', '.jpeg', '.gif']
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'local_watermark/logo_width',
        get_string('logo_width', 'local_watermark'),
        get_string('logo_width_desc', 'local_watermark'),
        '15',
        PARAM_INT
    ));

    // Logo position is fixed to top‑right; you can make it a dropdown later if needed.
    $settings->add(new admin_setting_configtext(
        'local_watermark/logo_margin',
        get_string('logo_margin', 'local_watermark'),
        get_string('logo_margin_desc', 'local_watermark'),
        '10',
        PARAM_INT
    ));

    // ---------- Background Overlay ----------
    $settings->add(new admin_setting_heading('local_watermark/background_heading',
        get_string('background_heading', 'local_watermark'),
        get_string('background_heading_desc', 'local_watermark')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_watermark/background_enabled',
        get_string('background_enabled', 'local_watermark'),
        get_string('background_enabled_desc', 'local_watermark'),
        0
    ));

    $settings->add(new admin_setting_configstoredfile(
        'local_watermark/background_file',
        get_string('background_file', 'local_watermark'),
        get_string('background_file_desc', 'local_watermark'),
        'background', // filearea
        0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg']]
    ));

    $settings->add(new admin_setting_configtext(
        'local_watermark/background_alpha',
        get_string('background_alpha', 'local_watermark'),
        get_string('background_alpha_desc', 'local_watermark'),
        '0.15',
        PARAM_FLOAT
    ));
}