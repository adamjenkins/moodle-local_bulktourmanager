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
 * Builds a zip of tool_usertours export records for a set of tour ids.
 *
 * Reproduces the same per-tour JSON shape as
 * \tool_usertours\manager::export_tour(), purely from that plugin's own
 * public API (tour::to_record(), tour::get_steps(), step::to_record()),
 * so multiple tours can be exported together without touching core.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exporter {
    /**
     * Build a filesystem-safe slug from a tour name for use as a zip entry name.
     *
     * @param string $name
     * @return string
     */
    public static function slug(string $name): string {
        $slug = \core_text::strtolower(strip_tags($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug !== '' ? $slug : 'tour';
    }

    /**
     * Build the export record for a single tour, matching
     * \tool_usertours\manager::export_tour()'s record shape.
     *
     * @param \tool_usertours\tour $tour
     * @return \stdClass
     */
    public static function build_export_record(\tool_usertours\tour $tour): \stdClass {
        $export = $tour->to_record();
        unset($export->id);
        $export->version = get_config('tool_usertours', 'version');

        $export->steps = [];
        foreach ($tour->get_steps() as $step) {
            $record = $step->to_record(true);
            unset($record->id);
            unset($record->tourid);
            $export->steps[] = $record;
        }

        return $export;
    }

    /**
     * Build the zip-packer file list for the given tour ids.
     *
     * Ids that no longer exist (e.g. deleted concurrently) are silently
     * skipped rather than aborting the whole export.
     *
     * @param int[] $ids
     * @return array Zip-packer compatible array: filename => [jsoncontent].
     */
    public static function build_zip_files(array $ids): array {
        $files = [];

        foreach ($ids as $id) {
            try {
                $tour = \tool_usertours\tour::instance($id);
            } catch (\Throwable $e) {
                continue;
            }

            $export = self::build_export_record($tour);
            $filename = sprintf('%02d-%s.json', $tour->get_sortorder(), self::slug($tour->get_name()));
            $files[$filename] = [json_encode($export)];
        }

        return $files;
    }
}
