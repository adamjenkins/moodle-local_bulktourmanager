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
 * English language strings for local_bulktourmanager.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['bulkdeletenone'] = 'No tours were deleted.';
$string['bulkdeleteresult'] = 'Deleted {$a} tour(s).';
$string['bulkexportnone'] = 'No tours were selected to export.';
$string['bulkimport'] = 'Bulk import (zip)';
$string['bulkimport_explanation'] = 'Upload a zip file containing one or more tour export JSON files (as produced by User tours\' own "Export" action). Every JSON file found in the zip will be imported as a new tour.';
$string['bulkimportfailures'] = 'Some files in the zip could not be imported: {$a}';
$string['bulkimportnone'] = 'No tours were imported. The zip did not contain any valid tour export JSON files.';
$string['bulkimportresult'] = 'Imported {$a->imported} of {$a->total} tours.';
$string['confirmbulkdeletequestion'] = 'Delete {$a} selected tour(s)? This cannot be undone.';
$string['confirmbulkdeletetitle'] = 'Delete selected tours';
$string['deleteselected'] = 'Delete selected';
$string['exportselected'] = 'Export selected';
$string['pluginname'] = 'Bulk tour manager';
$string['privacy:metadata'] = 'The Bulk tour manager plugin does not store any personal data itself; it only manages tour definitions on behalf of Site administration > User tours.';
$string['tourzip'] = 'Zip file of tour exports';
