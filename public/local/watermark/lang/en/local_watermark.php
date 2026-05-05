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

$string['pluginname'] = 'Watermark';
$string['enabled'] = 'Enable watermarking';
$string['enabled_desc'] = 'If enabled, all PDF file downloads will be watermarked with user information.';
$string['watermarktpl'] = 'Watermark text template';
$string['watermarktpl_desc'] = 'Use placeholders: {username}, {userid}, {email}, {firstname}, {lastname}, {time}';
$string['privacy:metadata'] = 'The Watermark plugin does not store any personal data itself; it only adds watermarks to PDF files on the fly.';
$string['invalidfileurl'] = 'Invalid file URL.';
$string['invalidcontext'] = 'Invalid context.';


// ... existing strings ...
$string['corner_heading'] = 'Corner Watermark';
$string['corner_heading_desc'] = 'Small text placed in the page corners.';
$string['corner_enabled'] = 'Enable corner watermark';
$string['corner_enabled_desc'] = 'Show user details in the corners.';
$string['corner_fontsize'] = 'Font size';
$string['corner_fontsize_desc'] = 'In points (e.g. 10).';
$string['corner_textcolor'] = 'Text colour';
$string['corner_textcolor_desc'] = 'RGB colour in HEX format.';
$string['corner_opacity'] = 'Opacity';
$string['corner_opacity_desc'] = 'Between 0 (invisible) and 1 (fully opaque).';
$string['corner_margin'] = 'Margin';
$string['corner_margin_desc'] = 'Distance from page edge (mm).';
$string['corner_positions'] = 'Corners to show';
$string['corner_positions_desc'] = 'Select which corners should contain the watermark text.';
$string['corner_topleft'] = 'Top Left';
$string['corner_topright'] = 'Top Right';
$string['corner_bottomleft'] = 'Bottom Left';
$string['corner_bottomright'] = 'Bottom Right';
$string['diagonal_heading'] = 'Diagonal Watermark';
$string['diagonal_heading_desc'] = 'Large rotated text in the background.';
$string['diagonal_enabled'] = 'Enable diagonal watermark';
$string['diagonal_enabled_desc'] = 'Show a large slanted text across the page.';
$string['diagonal_fontsize'] = 'Font size';
$string['diagonal_fontsize_desc'] = 'In points (e.g. 25).';
$string['diagonal_textcolor'] = 'Text colour';
$string['diagonal_textcolor_desc'] = 'RGB colour in HEX format.';
$string['diagonal_opacity_desc'] = 'Between 0 (invisible) and 1 (fully opaque).';
$string['diagonal_angle'] = 'Rotation angle';
$string['diagonal_angle_desc'] = 'Degrees to rotate the text (e.g. 45).';
$string['diagonal_offset_x'] = 'Horizontal shift';
$string['diagonal_offset_x_desc'] = 'Move the diagonal text left/right (mm).';
$string['diagonal_offset_y'] = 'Vertical shift';
$string['diagonal_offset_y_desc'] = 'Move the diagonal text up/down (mm).';
$string['logo_heading'] = 'Logo';
$string['logo_heading_desc'] = 'Image placed at a fixed position.';
$string['logo_enabled'] = 'Enable logo';
$string['logo_enabled_desc'] = 'Add a logo to every watermarked page.';
$string['logo'] = 'Logo image';
$string['logo_desc'] = 'Upload a PNG, JPG, or GIF file.';
$string['logo_width'] = 'Logo width';
$string['logo_width_desc'] = 'Width in mm (height scales proportionally).';
$string['logo_margin'] = 'Logo margin';
$string['logo_margin_desc'] = 'Distance from the edges (mm).';