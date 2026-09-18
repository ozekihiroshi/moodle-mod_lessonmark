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
 * Course presentation playlist.
 * @package mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lessonmark\local;

/**
 * Builds a playlist without loading content or recording activity views.
 */
final class course_presentation {
    /**
     * Accessible, course-listed LessonMark modules in section/module order.
     * Hidden and stealth activities are deliberately excluded, including for teachers.
     *
     * @param \stdClass $course Course record.
     * @return \cm_info[] Ordered modules accessible to the current user.
     */
    public static function modules(\stdClass $course): array {
        $modinfo = get_fast_modinfo($course);
        $result = [];
        $modules = $modinfo->get_cms();
        // Delegated sections belong at their displayed position, not their storage position.
        $modinfo->sort_cm_array($modules);
        foreach ($modules as $cm) {
            $section = $modinfo->get_section_info($cm->sectionnum);
            if (
                $cm->modname !== 'lessonmark' || !$cm->visible || !$cm->visibleoncoursepage ||
                !$section->visible || !$cm->uservisible || $cm->deletioninprogress
            ) {
                continue;
            }
            if (has_capability('mod/lessonmark:view', \context_module::instance($cm->id))) {
                $result[] = $cm;
            }
        }
        return $result;
    }
}
