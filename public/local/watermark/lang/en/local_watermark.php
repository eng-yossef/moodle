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