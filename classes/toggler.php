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
 * Bulk-enables or bulk-disables a set of tool_usertours tours.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toggler {
    /**
     * Sets the enabled state for each of the given tour ids.
     *
     * Ids that no longer exist (e.g. deleted concurrently) are silently
     * skipped rather than aborting the whole batch.
     *
     * @param int[] $ids
     * @param bool $enabled
     * @return int The number of tours actually changed.
     */
    public static function set_enabled_for_ids(array $ids, bool $enabled): int {
        $changed = 0;

        foreach ($ids as $id) {
            try {
                $tour = \tool_usertours\tour::instance($id);
            } catch (\Throwable $e) {
                continue;
            }
            $tour->set_enabled($enabled);
            $tour->persist();
            $changed++;
        }

        return $changed;
    }
}
