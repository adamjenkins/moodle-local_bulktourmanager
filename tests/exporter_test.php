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
 * Tests for \local_bulktourmanager\exporter.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(exporter::class)]
final class exporter_test extends advanced_testcase {
    /**
     * Creates a persisted tour (with one step) directly via tool_usertours's
     * own public API, so tests exercise the real objects exporter reads from.
     *
     * @param string $name
     * @param string $pathmatch
     * @return \tool_usertours\tour
     */
    private function create_tour(string $name, string $pathmatch): \tool_usertours\tour {
        $tourconfig = (object) [
            'id' => null,
            'name' => $name,
            'description' => 'Description of ' . $name,
            'pathmatch' => $pathmatch,
            'enabled' => true,
            'configdata' => '',
            'displaystepnumbers' => true,
        ];
        $tour = \tool_usertours\tour::load_from_record($tourconfig, true);
        $tour->persist(true);

        $stepconfig = (object) [
            'id' => null,
            'tourid' => $tour->get_id(),
            'title' => 'Step 1',
            'content' => 'Step 1 content',
            'targettype' => \tool_usertours\target::TARGET_UNATTACHED,
            'targetvalue' => '',
            'sortorder' => 0,
            'configdata' => '',
        ];
        $step = \tool_usertours\step::load_from_record($stepconfig, true, true);
        $step->persist(true);

        return $tour;
    }

    public function test_slug_sanitises_names(): void {
        $this->assertEquals('hello-world', exporter::slug('Hello, World!'));
        $this->assertEquals('tour', exporter::slug('???'));
        $this->assertEquals('tour', exporter::slug(''));
    }

    public function test_build_export_record_matches_core_shape(): void {
        $this->resetAfterTest();

        $tour = $this->create_tour('Export test tour', '/my/%');

        $export = exporter::build_export_record($tour);

        $this->assertObjectNotHasProperty('id', $export);
        $this->assertEquals('Export test tour', $export->name);
        $this->assertEquals(get_config('tool_usertours', 'version'), $export->version);
        $this->assertCount(1, $export->steps);
        $this->assertObjectNotHasProperty('id', $export->steps[0]);
        $this->assertObjectNotHasProperty('tourid', $export->steps[0]);
        $this->assertEquals('Step 1', $export->steps[0]->title);
    }

    public function test_build_zip_files_builds_one_entry_per_tour(): void {
        $this->resetAfterTest();

        $tour1 = $this->create_tour('Tour One', '/course/view.php%');
        $tour2 = $this->create_tour('Tour Two', '/my/%');

        $files = exporter::build_zip_files([$tour1->get_id(), $tour2->get_id()]);

        $this->assertCount(2, $files);

        foreach ($files as $filename => $content) {
            $this->assertStringEndsWith('.json', $filename);
            $decoded = json_decode($content[0]);
            $this->assertNotNull($decoded);
            $this->assertContains($decoded->name, ['Tour One', 'Tour Two']);
        }
    }

    public function test_build_zip_files_skips_missing_ids(): void {
        $this->resetAfterTest();

        $tour = $this->create_tour('Real tour', '/my/%');

        $files = exporter::build_zip_files([$tour->get_id(), 999999]);

        $this->assertCount(1, $files);
    }

    public function test_exported_json_round_trips_through_core_import(): void {
        $this->resetAfterTest();

        $tour = $this->create_tour('Round trip tour', '/my/%');
        $files = exporter::build_zip_files([$tour->get_id()]);
        $json = reset($files)[0];

        $imported = \tool_usertours\manager::import_tour_from_json($json);

        $this->assertNotEquals($tour->get_id(), $imported->get_id());
        $this->assertEquals('Round trip tour', $imported->get_name());
        $this->assertCount(1, $imported->get_steps());
    }
}
