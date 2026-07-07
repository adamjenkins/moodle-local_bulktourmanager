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

declare(strict_types=1);

namespace local_bulktourmanager\form;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for \local_bulktourmanager\form\bulk_filters_form.
 *
 * Exercises the form through the same generic AJAX entry point
 * (\core_form\external\dynamic_form::execute()) that core_form/modalform
 * actually calls client-side, rather than calling process_dynamic_submission()
 * directly, so this also proves the form is wired up correctly end-to-end.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(bulk_filters_form::class)]
final class bulk_filters_form_test extends advanced_testcase {
    /**
     * Creates a persisted tour.
     *
     * @param string $name
     * @return \tool_usertours\tour
     */
    private function create_tour(string $name): \tool_usertours\tour {
        $tourconfig = (object) [
            'id' => null,
            'name' => $name,
            'description' => '',
            'pathmatch' => '/my/%',
            'enabled' => true,
            'configdata' => '',
            'displaystepnumbers' => true,
        ];
        $tour = \tool_usertours\tour::load_from_record($tourconfig, true);
        $tour->persist(true);

        return $tour;
    }

    public function test_only_the_checked_filter_is_applied_to_all_selected_tours(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $tour1 = $this->create_tour('Filter test tour one');
        $tour2 = $this->create_tour('Filter test tour two');

        $formidentifier = 'local_bulktourmanager_form_bulk_filters_form';
        $formdata = http_build_query([
            'ids' => "{$tour1->get_id()},{$tour2->get_id()}",
            'sesskey' => sesskey(),
            "_qf__{$formidentifier}" => 1,
            'changefilter_role' => 1,
            'filter_role' => ['student'],
        ], '', '&');

        // Sesskey confirmation (deep inside moodleform's submission detection)
        // reads the real $_POST superglobal, not the $ajaxformdata this form was
        // constructed with - mirrors what the actual AJAX POST request carries.
        $_POST['sesskey'] = sesskey();
        $result = \core_form\external\dynamic_form::execute(bulk_filters_form::class, $formdata);

        $this->assertTrue($result['submitted']);
        $data = json_decode($result['data']);
        $this->assertEquals(2, $data->updated);
        $this->assertEquals(['role'], $data->changedfilters);

        $tour1 = \tool_usertours\tour::instance($tour1->get_id());
        $tour2 = \tool_usertours\tour::instance($tour2->get_id());
        $this->assertEquals(['student'], $tour1->get_filter_values('role'));
        $this->assertEquals(['student'], $tour2->get_filter_values('role'));

        // An untouched filter (its checkbox was never sent) must be left alone.
        $this->assertEquals([], $tour1->get_filter_values('category'));
        $this->assertEquals([], $tour2->get_filter_values('category'));
    }

    public function test_no_filters_checked_changes_nothing(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $tour = $this->create_tour('Untouched filter test tour');

        $formidentifier = 'local_bulktourmanager_form_bulk_filters_form';
        $formdata = http_build_query([
            'ids' => (string) $tour->get_id(),
            'sesskey' => sesskey(),
            "_qf__{$formidentifier}" => 1,
        ], '', '&');

        // Sesskey confirmation (deep inside moodleform's submission detection)
        // reads the real $_POST superglobal, not the $ajaxformdata this form was
        // constructed with - mirrors what the actual AJAX POST request carries.
        $_POST['sesskey'] = sesskey();
        $result = \core_form\external\dynamic_form::execute(bulk_filters_form::class, $formdata);

        $this->assertTrue($result['submitted']);
        $data = json_decode($result['data']);
        $this->assertEquals(1, $data->updated);
        $this->assertEquals([], $data->changedfilters);

        $tour = \tool_usertours\tour::instance($tour->get_id());
        $this->assertEquals([], $tour->get_filter_values('role'));
    }
}
