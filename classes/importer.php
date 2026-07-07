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

namespace local_bulktourmanager;

/**
 * Bulk-imports every tour export JSON file found under a directory (the
 * result of extracting an uploaded zip), reusing
 * \tool_usertours\manager::import_tour_from_json() for each file so the
 * created tours are indistinguishable from ones imported one at a time via
 * core's own single-tour import.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer {
    /**
     * Import every *.json file found (recursively) under the given directory.
     *
     * @param string $dir
     * @return \stdClass Object with ->imported (int) and ->failures (string[] of "filename: reason").
     */
    public static function import_from_dir(string $dir): \stdClass {
        $result = (object) [
            'imported' => 0,
            'failures' => [],
        ];

        $realdir = realpath($dir);
        if ($realdir === false) {
            return $result;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realdir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (strtolower($file->getExtension()) !== 'json') {
                continue;
            }

            $realpath = $file->getRealPath();
            if (strpos($realpath, $realdir) !== 0) {
                // Defence-in-depth: never read a path that escaped the extraction directory.
                continue;
            }

            try {
                \tool_usertours\manager::import_tour_from_json(file_get_contents($realpath));
                $result->imported++;
            } catch (\Throwable $e) {
                $result->failures[] = $file->getFilename() . ': ' . $e->getMessage();
            }
        }

        return $result;
    }
}
