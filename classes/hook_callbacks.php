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

use core\hook\output\before_footer_html_generation;

/**
 * Hook callbacks for local_bulktourmanager.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Injects the bulk import/export/delete controls onto the tool_usertours
     * tour list page only, leaving every other page untouched.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE;

        if ($PAGE->pagetype !== 'admin-tool-usertours-configure') {
            return;
        }

        $action = optional_param('action', \tool_usertours\manager::ACTION_LISTTOURS, PARAM_ALPHANUMEXT);
        if ($action !== \tool_usertours\manager::ACTION_LISTTOURS) {
            return;
        }

        if (!has_capability('tool/usertours:managetours', \context_system::instance())) {
            return;
        }

        $PAGE->requires->js_call_amd('local_bulktourmanager/bulkactions', 'init');
    }
}
