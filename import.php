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
 * Bulk-import a zip of tool_usertours export JSON files.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('tool/usertours:managetours', $context);

$listurl = new moodle_url('/admin/tool/usertours/configure.php');

$PAGE->set_url('/local/bulktourmanager/import.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('bulkimport', 'local_bulktourmanager'));
$PAGE->set_heading(get_string('bulkimport', 'local_bulktourmanager'));
$PAGE->navbar->add(get_string('bulkimport', 'local_bulktourmanager'));

$form = new \local_bulktourmanager\form\import_form();

if ($form->is_cancelled()) {
    redirect($listurl);
} else if ($form->get_data()) {
    $tempdir = make_request_directory();
    $zippath = $tempdir . '/tourzip.zip';
    $form->save_file('tourzip', $zippath, true);

    $extractdir = $tempdir . '/extracted';
    check_dir_exists($extractdir);

    $packer = get_file_packer('application/zip');
    $packer->extract_to_pathname($zippath, $extractdir);

    $result = \local_bulktourmanager\importer::import_from_dir($extractdir);
    $imported = $result->imported;
    $failures = $result->failures;

    $total = $imported + count($failures);

    if ($failures) {
        \core\notification::add(
            get_string('bulkimportfailures', 'local_bulktourmanager', implode(', ', $failures)),
            \core\notification::WARNING
        );
    }

    if ($imported > 0) {
        redirect(
            $listurl,
            get_string('bulkimportresult', 'local_bulktourmanager', (object) ['imported' => $imported, 'total' => $total]),
            null,
            \core\notification::SUCCESS
        );
    } else {
        redirect(
            $listurl,
            get_string('bulkimportnone', 'local_bulktourmanager'),
            null,
            \core\notification::WARNING
        );
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('bulkimport', 'local_bulktourmanager'));
    echo html_writer::span(get_string('bulkimport_explanation', 'local_bulktourmanager'));
    $form->display();
    echo $OUTPUT->footer();
}
