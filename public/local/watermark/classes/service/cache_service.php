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

namespace local_watermark\service;

defined('MOODLE_INTERNAL') || die();

class cache_service {

    /**
     * Get cached watermarked PDF content.
     *
     * @param \stored_file $file
     * @param \stdClass $user
     * @return string|false File content or false if not cached.
     */
    public static function get($file, $user) {
        $path = self::get_cache_path($file, $user);
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        return false;
    }

    /**
     * Store watermarked PDF content in cache.
     *
     * @param \stored_file $file
     * @param \stdClass $user
     * @param string $content PDF data.
     */
    public static function store($file, $user, $content) {
        $path = self::get_cache_path($file, $user);
        file_put_contents($path, $content);
    }

    /**
     * Build the cache file path.
     *
     * @param \stored_file $file
     * @param \stdClass $user
     * @return string
     */
    private static function get_cache_path($file, $user) {
        $dir = make_temp_directory('watermark_cache');
        $hash = sha1($file->get_contenthash() . '_' . $user->id);
        return $dir . '/' . $hash . '.pdf';
    }
}