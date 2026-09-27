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
 * Tests for \local_bulktourmanager\importer.
 *
 * @package    local_bulktourmanager
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(importer::class)]
final class importer_test extends advanced_testcase {
    /**
     * Builds the minimal JSON shape that
     * \tool_usertours\manager::import_tour_from_json() expects, matching
     * what tool_usertours's own export produces.
     *
     * @param string $name
     * @return string
     */
    private function tour_json(string $name): string {
        return json_encode((object) [
            'name' => $name,
            'description' => 'Description of ' . $name,
            'pathmatch' => '/my/%',
            'enabled' => true,
            'configdata' => '',
            'displaystepnumbers' => true,
            'endtourlabel' => '',
            'steps' => [
                (object) [
                    'title' => 'Step 1',
                    'content' => 'Step 1 content',
                    'targettype' => \tool_usertours\target::TARGET_UNATTACHED,
                    'targetvalue' => '',
                    'sortorder' => 0,
                    'configdata' => '',
                ],
            ],
        ]);
    }

    public function test_imports_every_json_file_found_recursively(): void {
        global $DB;

        $this->resetAfterTest();

        $dir = make_request_directory();
        check_dir_exists($dir . '/nested');
        file_put_contents($dir . '/one.json', $this->tour_json('Imported tour one'));
        file_put_contents($dir . '/nested/two.json', $this->tour_json('Imported tour two'));
        file_put_contents($dir . '/notes.txt', 'ignore me');

        $before = $DB->count_records('tool_usertours_tours');

        $result = importer::import_from_dir($dir);

        $this->assertEquals(2, $result->imported);
        $this->assertEmpty($result->failures);
        $this->assertEquals($before + 2, $DB->count_records('tool_usertours_tours'));
        $this->assertTrue($DB->record_exists('tool_usertours_tours', ['name' => 'Imported tour one']));
        $this->assertTrue($DB->record_exists('tool_usertours_tours', ['name' => 'Imported tour two']));
    }

    public function test_records_failure_for_invalid_json_without_aborting_others(): void {
        global $DB;

        // The importer must reject the malformed file itself, before handing it to
        // tool_usertours (whose property reads are not null-guarded and would emit
        // PHP warnings, failing this suite under failOnWarning).
        $this->resetAfterTest();

        $dir = make_request_directory();
        file_put_contents($dir . '/valid.json', $this->tour_json('Still imported'));
        // Valid JSON, but missing the tour properties (including 'steps') that
        // manager::import_tour_from_json() reads without checking.
        file_put_contents($dir . '/broken.json', json_encode((object) ['name' => 'Missing steps']));

        $before = $DB->count_records('tool_usertours_tours');

        $result = importer::import_from_dir($dir);

        $this->assertEquals(1, $result->imported);
        $this->assertCount(1, $result->failures);
        $this->assertStringContainsString('broken.json', $result->failures[0]);
        $this->assertStringContainsString(
            get_string('bulkimportinvalidfile', 'local_bulktourmanager', 'pathmatch'),
            $result->failures[0]
        );
        $this->assertEquals($before + 1, $DB->count_records('tool_usertours_tours'));
        $this->assertFalse($DB->record_exists('tool_usertours_tours', ['name' => 'Missing steps']));
    }

    public function test_validate_tour_json(): void {
        $valid = json_decode($this->tour_json('Valid'));
        $this->assertNull(importer::validate_tour_json(json_encode($valid)));

        $this->assertSame('invalid JSON', importer::validate_tour_json('{not json'));
        $this->assertSame('invalid JSON', importer::validate_tour_json('[]'));

        $nosteps = clone $valid;
        unset($nosteps->steps);
        $this->assertSame('steps', importer::validate_tour_json(json_encode($nosteps)));

        $legacycomment = clone $valid;
        $legacycomment->comment = $legacycomment->description;
        unset($legacycomment->description);
        $this->assertNull(importer::validate_tour_json(json_encode($legacycomment)));

        $badconfig = clone $valid;
        $badconfig->configdata = null;
        $this->assertSame('configdata', importer::validate_tour_json(json_encode($badconfig)));

        $badstep = clone $valid;
        $badstep->steps = [clone $valid->steps[0]];
        unset($badstep->steps[0]->targettype);
        $this->assertSame('steps[0].targettype', importer::validate_tour_json(json_encode($badstep)));
    }

    public function test_empty_directory_imports_nothing(): void {
        $this->resetAfterTest();

        $dir = make_request_directory();

        $result = importer::import_from_dir($dir);

        $this->assertEquals(0, $result->imported);
        $this->assertEmpty($result->failures);
    }
}
