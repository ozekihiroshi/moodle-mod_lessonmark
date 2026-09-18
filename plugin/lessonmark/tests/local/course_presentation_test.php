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
 * Playlist access and ordering tests.
 * @package mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lessonmark\local;

/**
 * Tests playlist construction without exposing hidden teaching materials.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(course_presentation::class)]
final class course_presentation_test extends \advanced_testcase {
    /**
     * Playlist follows sections, not creation order, and excludes nonlisted modules.
     */
    public function test_order_and_visibility(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('allowstealth', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 3]);
        $later = $generator->create_module('lessonmark', ['course' => $course->id, 'section' => 2]);
        $first = $generator->create_module('lessonmark', ['course' => $course->id, 'section' => 1]);
        $second = $generator->create_module('lessonmark', ['course' => $course->id, 'section' => 1]);
        $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $generator->create_module('lessonmark', ['course' => $course->id, 'visible' => 0]);
        $stealth = $generator->create_module('lessonmark', ['course' => $course->id]);
        $DB->set_field('course_modules', 'visibleoncoursepage', 0, ['id' => $stealth->cmid]);
        rebuild_course_cache($course->id, true);
        $generator->create_module('lessonmark', ['course' => $course->id, 'section' => 3]);
        $section = get_fast_modinfo($course)->get_section_info(3);
        \core_courseformat\formatactions::section($course->id)->set_visibility($section, 0);
        $ids = array_map(static fn($cm): int => (int) $cm->id, course_presentation::modules($course));
        $this->assertSame([(int) $first->cmid, (int) $second->cmid, (int) $later->cmid], $ids);
    }

    /**
     * Student-specific availability and capability restrictions remove entries.
     */
    public function test_student_access(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $allowed = $generator->create_module('lessonmark', ['course' => $course->id]);
        $denied = $generator->create_module('lessonmark', ['course' => $course->id]);
        $blocked = $generator->create_module('lessonmark', ['course' => $course->id]);
        $DB->set_field('course_modules', 'availability', json_encode([
            'op' => '&', 'c' => [['type' => 'date', 'd' => '>=', 't' => time() + YEARSECS]], 'showc' => [false],
        ]), ['id' => $blocked->cmid]);
        set_config('enableavailability', 1);
        rebuild_course_cache($course->id, true);
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        assign_capability('mod/lessonmark:view', CAP_PROHIBIT, $roleid, \context_module::instance($denied->cmid)->id);
        $this->setUser($student);
        $ids = array_map(static fn($cm): int => (int) $cm->id, course_presentation::modules($course));
        $this->assertSame([(int) $allowed->cmid], $ids);
    }

    /**
     * Listing never crosses course boundaries or marks unvisited modules as viewed.
     */
    public function test_scope_and_no_completion(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enablecompletion', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $other = $generator->create_course();
        $generator->create_module('lessonmark', ['course' => $other->id]);
        $this->assertSame([], course_presentation::modules($course));
        $lesson = $generator->create_module('lessonmark', [
            'course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1,
        ]);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $events = $this->redirectEvents();
        $this->assertCount(1, course_presentation::modules($course));
        $this->assertFalse($DB->record_exists('course_modules_completion', [
            'coursemoduleid' => $lesson->cmid, 'userid' => $student->id,
        ]));
        $this->assertCount(0, $events->get_events());
        $events->close();
    }

    /**
     * Subsection placement, movement and visibility determine the playlist for both roles.
     */
    public function test_subsection_display_order_and_access(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enableavailability', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['format' => 'topics', 'numsections' => 1]);
        $first = $generator->create_module('lessonmark', ['course' => $course->id, 'section' => 1]);
        $last = $generator->create_module('lessonmark', ['course' => $course->id, 'section' => 1]);
        $subb = $generator->create_module('subsection', ['course' => $course->id, 'section' => 1]);
        $suba = $generator->create_module('subsection', ['course' => $course->id, 'section' => 1]);
        $modinfo = get_fast_modinfo($course);
        $b = $generator->create_module('lessonmark', [
            'course' => $course->id, 'name' => 'Alpha second',
            'section' => $modinfo->get_cm($subb->cmid)->get_delegated_section_info()->sectionnum,
        ]);
        $a = $generator->create_module('lessonmark', [
            'course' => $course->id, 'name' => 'Zulu first',
            'section' => $modinfo->get_cm($suba->cmid)->get_delegated_section_info()->sectionnum,
        ]);
        $actions = \core_courseformat\formatactions::cm($course);
        $actions->move_before($suba->cmid, $last->cmid);
        $actions->move_before($subb->cmid, $last->cmid);
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $ids = static fn() => array_map(static fn($cm): int => (int)$cm->id, course_presentation::modules($course));
        $expected = [(int)$first->cmid, (int)$a->cmid, (int)$b->cmid, (int)$last->cmid];
        foreach ([$student, $teacher] as $user) {
            $this->setUser($user);
            $this->assertSame($expected, $ids());
        }
        $this->setAdminUser();
        $actions->move_before($subb->cmid, $suba->cmid);
        foreach ([$student, $teacher] as $user) {
            $this->setUser($user);
            $this->assertSame([(int)$first->cmid, (int)$b->cmid, (int)$a->cmid, (int)$last->cmid], $ids());
        }
        $this->setAdminUser();
        $DB->set_field('course_modules', 'availability', json_encode([
            'op' => '&', 'c' => [['type' => 'date', 'd' => '>=', 't' => time() + DAYSECS]], 'showc' => [false],
        ]), ['id' => $subb->cmid]);
        rebuild_course_cache($course->id, true);
        $this->setUser($student);
        $this->assertSame([(int)$first->cmid, (int)$a->cmid, (int)$last->cmid], $ids());
        $this->setAdminUser();
        $DB->set_field('course_modules', 'availability', null, ['id' => $subb->cmid]);
        set_coursemodule_visible($subb->cmid, 0);
        foreach ([$student, $teacher] as $user) {
            $this->setUser($user);
            $this->assertSame([(int)$first->cmid, (int)$a->cmid, (int)$last->cmid], $ids());
        }
        $this->setAdminUser();
        $DB->set_field('course_modules', 'deletioninprogress', 1, ['id' => $a->cmid]);
        rebuild_course_cache($course->id, true);
        $this->setUser($student);
        $this->assertSame([(int)$first->cmid, (int)$last->cmid], $ids());
        $this->setAdminUser();
        $DB->set_field('course_modules', 'deletioninprogress', 0, ['id' => $a->cmid]);
        $section = get_fast_modinfo($course)->get_section_info(1);
        \core_courseformat\formatactions::section($course->id)->set_visibility($section, 0);
        foreach ([$student, $teacher] as $user) {
            $this->setUser($user);
            $this->assertSame([], $ids());
        }
    }
}
