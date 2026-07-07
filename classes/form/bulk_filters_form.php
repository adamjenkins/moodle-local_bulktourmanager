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

namespace local_bulktourmanager\form;

use tool_usertours\helper;
use tool_usertours\tour;

/**
 * Bulk-edit tour filters, rendered inside a modal via core_form/modalform.
 *
 * Every registered tool_usertours filter (helper::get_all_filters()) gets
 * its own "Change this filter" checkbox plus that filter's own form
 * fields, reused as-is via its add_filter_to_form()/save_filter_values_from_form()
 * methods. A filter is only applied to the selected tours if its checkbox
 * is checked; there is no per-tour prefill, since the selected tours may
 * already differ on any given filter.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_filters_form extends \core_form\dynamic_form {
    /**
     * Form definition.
     */
    public function definition() {
        global $CFG;

        // The courseformat filter's add_filter_to_form() calls the legacy global
        // get_sorted_course_formats(), which the normal configure.php admin page
        // happens to have loaded already but this AJAX/dynamic_form endpoint does not.
        require_once($CFG->dirroot . '/course/lib.php');

        $mform = $this->_form;

        $mform->addElement('hidden', 'ids');
        $mform->setType('ids', PARAM_SEQUENCE);

        foreach (helper::get_all_filters() as $filterclass) {
            $filtername = $filterclass::get_filter_name();

            $mform->addElement('header', "filterheader_{$filtername}", get_string("filter_{$filtername}", 'tool_usertours'));

            $mform->addElement(
                'advcheckbox',
                "changefilter_{$filtername}",
                get_string('bulkeditfilter_change', 'local_bulktourmanager')
            );

            $filterclass::add_filter_to_form($mform);
        }
    }

    /**
     * Check if current user has access to this form.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('tool/usertours:managetours', $this->get_context_for_dynamic_submission());
    }

    /**
     * Returns form context.
     *
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): \context {
        return \context_system::instance();
    }

    /**
     * Load in existing data as form defaults.
     *
     * There is no per-tour prefill (see class docblock); only the selected
     * tour ids are set, everything else uses its normal moodleform default.
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data((object) [
            'ids' => $this->optional_param('ids', '', PARAM_SEQUENCE),
        ]);
    }

    /**
     * Process the form submission.
     *
     * @return array
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $ids = array_filter(array_map('intval', explode(',', $data->ids)));

        $changedfilters = [];

        foreach (helper::get_all_filters() as $filterclass) {
            $filtername = $filterclass::get_filter_name();
            $changekey = "changefilter_{$filtername}";

            if (empty($data->$changekey)) {
                continue;
            }

            foreach ($ids as $id) {
                try {
                    $tour = tour::instance($id);
                } catch (\Throwable $e) {
                    continue;
                }
                $filterclass::save_filter_values_from_form($tour, $data);
                $tour->persist();
            }

            $changedfilters[] = $filtername;
        }

        return [
            'updated' => count($ids),
            'changedfilters' => $changedfilters,
        ];
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is rendered or submitted via AJAX.
     *
     * @return \moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/admin/tool/usertours/configure.php');
    }
}
