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

/**
 * Bulk-enable or bulk-disable the selected tool_usertours tours.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('tool/usertours:managetours', $context);
require_sesskey();

$listurl = new moodle_url('/admin/tool/usertours/configure.php');

$ids = required_param_array('ids', PARAM_INT);
$enabled = required_param('enabled', PARAM_BOOL);

$changed = \local_bulktourmanager\toggler::set_enabled_for_ids($ids, $enabled);

$stringkey = $enabled ? 'bulkenableresult' : 'bulkdisableresult';
$nonestringkey = $enabled ? 'bulkenablenone' : 'bulkdisablenone';

if ($changed > 0) {
    redirect($listurl, get_string($stringkey, 'local_bulktourmanager', $changed), null, \core\notification::SUCCESS);
} else {
    redirect($listurl, get_string($nonestringkey, 'local_bulktourmanager'), null, \core\notification::WARNING);
}
