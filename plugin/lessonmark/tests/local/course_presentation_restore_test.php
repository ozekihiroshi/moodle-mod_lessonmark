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
 * Subsection playlist backup and restore regression.
 * @package mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lessonmark\local;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/backup/tests/backup_restore_base_testcase.php');

/**
 * Restored subsection positions determine the LessonMark playlist.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(course_presentation::class)]
final class course_presentation_restore_test extends \core_backup_backup_restore_base_testcase {
    /**
     * Backup and restore preserve presentation order even though module IDs change.
     */
    public function test_restored_subsection_order(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['format' => 'topics', 'numsections' => 1]);
        $subb = $generator->create_module('subsection', ['course' => $course->id, 'section' => 1]);
        $suba = $generator->create_module('subsection', ['course' => $course->id, 'section' => 1]);
        $info = get_fast_modinfo($course);
        foreach ([[$subb, 'Alpha second'], [$suba, 'Zulu first']] as [$sub, $name]) {
            $generator->create_module('lessonmark', [
                'course' => $course->id, 'name' => $name,
                'section' => $info->get_cm($sub->cmid)->get_delegated_section_info()->sectionnum,
            ]);
        }
        \core_courseformat\formatactions::cm($course)->move_before($suba->cmid, $subb->cmid);
        $backupid = $this->perform_backup($course);
        $restored = $generator->create_course(['format' => 'topics', 'numsections' => 0]);
        $this->perform_restore($backupid, $restored);
        $modules = course_presentation::modules($restored);
        $this->assertSame(['Zulu first', 'Alpha second'], array_map(static fn($cm) => $cm->name, $modules));
        foreach ($modules as $cm) {
            $this->assertEquals($restored->id, $cm->course);
        }
    }
}
