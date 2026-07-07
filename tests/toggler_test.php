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

namespace local_bulktourmanager;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for \local_bulktourmanager\toggler.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(toggler::class)]
final class toggler_test extends advanced_testcase {
    /**
     * Creates a persisted tour with the given initial enabled state.
     *
     * @param bool $enabled
     * @return \tool_usertours\tour
     */
    private function create_tour(bool $enabled): \tool_usertours\tour {
        $tourconfig = (object) [
            'id' => null,
            'name' => 'Toggler test tour',
            'description' => '',
            'pathmatch' => '/my/%',
            'enabled' => $enabled,
            'configdata' => '',
            'displaystepnumbers' => true,
        ];
        $tour = \tool_usertours\tour::load_from_record($tourconfig, true);
        $tour->persist(true);

        return $tour;
    }

    public function test_enables_disabled_tours(): void {
        global $DB;

        $this->resetAfterTest();

        $tour1 = $this->create_tour(false);
        $tour2 = $this->create_tour(false);

        $changed = toggler::set_enabled_for_ids([$tour1->get_id(), $tour2->get_id()], true);

        $this->assertEquals(2, $changed);
        $this->assertEquals(1, $DB->get_field('tool_usertours_tours', 'enabled', ['id' => $tour1->get_id()]));
        $this->assertEquals(1, $DB->get_field('tool_usertours_tours', 'enabled', ['id' => $tour2->get_id()]));
    }

    public function test_disables_enabled_tours(): void {
        global $DB;

        $this->resetAfterTest();

        $tour1 = $this->create_tour(true);
        $tour2 = $this->create_tour(true);

        $changed = toggler::set_enabled_for_ids([$tour1->get_id(), $tour2->get_id()], false);

        $this->assertEquals(2, $changed);
        $this->assertEquals(0, $DB->get_field('tool_usertours_tours', 'enabled', ['id' => $tour1->get_id()]));
        $this->assertEquals(0, $DB->get_field('tool_usertours_tours', 'enabled', ['id' => $tour2->get_id()]));
    }

    public function test_skips_missing_ids_without_aborting(): void {
        $this->resetAfterTest();

        $tour = $this->create_tour(false);

        $changed = toggler::set_enabled_for_ids([$tour->get_id(), 999999], true);

        $this->assertEquals(1, $changed);
    }
}
