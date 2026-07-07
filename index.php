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
 * Admin landing page for local_bulktourmanager.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/bulktourmanager:manage', $context);

$PAGE->set_url('/local/bulktourmanager/index.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_bulktourmanager'));
$PAGE->set_heading(get_string('pluginname', 'local_bulktourmanager'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managetours', 'local_bulktourmanager'));
echo $OUTPUT->notification('Bulk import/export of user tours is not implemented yet.', 'info');
echo $OUTPUT->footer();
