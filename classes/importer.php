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

            $json = file_get_contents($realpath);
            $problem = ($json === false) ? 'unreadable' : self::validate_tour_json($json);
            if ($problem !== null) {
                // Rejected before core sees it: import_tour_from_json() does no
                // validation of its own and emits PHP warnings on a malformed record.
                $result->failures[] = $file->getFilename() . ': ' .
                    get_string('bulkimportinvalidfile', 'local_bulktourmanager', $problem);
                continue;
            }

            try {
                \tool_usertours\manager::import_tour_from_json($json);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->failures[] = $file->getFilename() . ': ' . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Check that a tour export JSON string has every property, of a usable
     * type, that \tool_usertours\manager::import_tour_from_json() and the
     * tour/step reload_from_record() methods it calls read unguarded.
     *
     * @param string $json
     * @return string|null Null if valid, otherwise a short description of the first problem found.
     */
    public static function validate_tour_json(string $json): ?string {
        $tour = json_decode($json);
        if (!$tour instanceof \stdClass) {
            return 'invalid JSON';
        }

        $problem = self::check_properties($tour, [
            'name' => 'string',
            'pathmatch' => 'nullable',
            'enabled' => 'scalar',
            'configdata' => 'string',
        ]);
        if ($problem !== null) {
            return $problem;
        }
        // Core falls back to the legacy 'comment' property when 'description' is absent.
        if (!property_exists($tour, 'description') && !property_exists($tour, 'comment')) {
            return 'description';
        }
        if (!isset($tour->steps) || !is_array($tour->steps)) {
            return 'steps';
        }

        foreach ($tour->steps as $index => $step) {
            if (!$step instanceof \stdClass) {
                return 'steps[' . $index . ']';
            }
            $problem = self::check_properties($step, [
                'title' => 'nullable',
                'content' => 'nullable',
                'targettype' => 'scalar',
                'targetvalue' => 'nullable',
                'sortorder' => 'scalar',
                'configdata' => 'string',
            ]);
            if ($problem !== null) {
                return 'steps[' . $index . '].' . $problem;
            }
        }

        return null;
    }

    /**
     * Check that each named property exists on the record with the given kind of value.
     *
     * @param \stdClass $record
     * @param array $rules Property name to 'string', 'scalar' or 'nullable' (scalar or null).
     * @return string|null Null if all present and well-typed, otherwise the offending property name.
     */
    private static function check_properties(\stdClass $record, array $rules): ?string {
        foreach ($rules as $property => $kind) {
            if (!property_exists($record, $property)) {
                return $property;
            }
            $value = $record->$property;
            $ok = match ($kind) {
                'string' => is_string($value),
                'scalar' => is_scalar($value),
                'nullable' => $value === null || is_scalar($value),
            };
            if (!$ok) {
                return $property;
            }
        }
        return null;
    }
}
