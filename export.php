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
 * Bulk-export the selected tool_usertours tours as a single zip of the same
 * per-tour JSON shape produced by tool_usertours's own single-tour export.
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
if (empty($ids)) {
    redirect($listurl, get_string('bulkexportnone', 'local_bulktourmanager'), null, \core\notification::WARNING);
}

$files = \local_bulktourmanager\exporter::build_zip_files($ids);

if (empty($files)) {
    redirect($listurl, get_string('bulkexportnone', 'local_bulktourmanager'), null, \core\notification::WARNING);
}

$tempdir = make_request_directory();
$zippath = $tempdir . '/usertours_export.zip';

$packer = get_file_packer('application/zip');
$packer->archive_to_pathname($files, $zippath);

// The pathisstring param below must be false - $zippath is a real file path, not raw content.
send_file($zippath, 'usertours_export_' . time() . '.zip', 0, 0, false, true);
