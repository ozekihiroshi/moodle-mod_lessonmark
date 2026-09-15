<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Preview External Service tests.
 *
 * @package   mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lessonmark\external;

use core_external\external_api;

/**
 * Verifies preview rendering and capability enforcement.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(render_preview::class)]
final class render_preview_test extends \advanced_testcase {
    /**
     * An authorised course editor can render unsaved Markdown.
     */
    public function test_authorised_course_preview(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();

        $result = render_preview::execute('# External preview', 0, $course->id, 0);
        $clean = external_api::clean_returnvalue(render_preview::execute_returns(), $result);

        $this->assertStringContainsString('External preview', $clean['html']);
        $this->assertSame([], $clean['diagnostics']);
    }

    /**
     * A learner without activity creation permission is rejected.
     */
    public function test_unauthorised_course_preview(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);
        render_preview::execute('# Rejected preview', 0, $course->id, 0);
    }

    /**
     * A request without an editing context is rejected.
     */
    public function test_missing_context_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->expectException(\moodle_exception::class);
        render_preview::execute('# Missing context');
    }
}
