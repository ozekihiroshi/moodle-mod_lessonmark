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
 * External service for rendering an unsaved LessonMark preview.
 *
 * @package   mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lessonmark\external;

use context_course;
use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_lessonmark\local\moodle_markdown_renderer;

/**
 * Renders an editor preview after validating the Moodle context and capability.
 */
final class render_preview extends external_api {
    /**
     * Define accepted arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'markdownsource' => new external_value(PARAM_RAW, 'Unsaved Markdown source'),
            'cmid' => new external_value(PARAM_INT, 'Existing LessonMark course-module ID', VALUE_DEFAULT, 0),
            'courseid' => new external_value(PARAM_INT, 'Course ID for a new activity', VALUE_DEFAULT, 0),
            'draftitemid' => new external_value(PARAM_INT, 'Editor draft item ID', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Render a preview in an authorised module or course context.
     *
     * @param string $markdownsource Unsaved Markdown source.
     * @param int $cmid Existing course-module ID.
     * @param int $courseid Course ID for a new activity.
     * @param int $draftitemid Editor draft item ID.
     * @return array Rendered HTML and safe diagnostic messages.
     */
    public static function execute(
        string $markdownsource,
        int $cmid = 0,
        int $courseid = 0,
        int $draftitemid = 0
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'markdownsource' => $markdownsource,
            'cmid' => $cmid,
            'courseid' => $courseid,
            'draftitemid' => $draftitemid,
        ]);
        $context = self::resolve_context($params['cmid'], $params['courseid']);
        self::validate_context($context);

        if ($context instanceof context_module) {
            require_capability('mod/lessonmark:edit', $context);
        } else {
            require_capability('mod/lessonmark:addinstance', $context);
        }

        $renderer = new moodle_markdown_renderer(
            draftitemid: $params['draftitemid'] > 0 ? $params['draftitemid'] : null
        );
        $document = $renderer->render($params['markdownsource'], $context);
        $diagnostics = array_map(static function (array $diagnostic): array {
            $value = (string) ($diagnostic['language'] ?? $diagnostic['path'] ?? '');
            $message = match ($diagnostic['type'] ?? '') {
                'unsupportedlanguage' => get_string('diagnosticunsupportedlanguage', 'mod_lessonmark', $value),
                'unresolvedrelativeimage' => get_string('diagnosticrelativeimage', 'mod_lessonmark', $value),
                'missingimagealt' => get_string('diagnosticmissingalt', 'mod_lessonmark', $value),
                default => get_string('diagnosticrendering', 'mod_lessonmark'),
            };
            return ['message' => $message];
        }, $document->get_diagnostics());

        return ['html' => $document->get_content_html(), 'diagnostics' => $diagnostics];
    }

    /**
     * Define returned data.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'html' => new external_value(PARAM_RAW, 'Rendered safe HTML'),
            'diagnostics' => new external_multiple_structure(new external_single_structure([
                'message' => new external_value(PARAM_TEXT, 'Localised diagnostic message'),
            ])),
        ]);
    }

    /**
     * Resolve and authenticate the requested editing context.
     *
     * @param int $cmid Existing course-module ID.
     * @param int $courseid Course ID for a new activity.
     * @return context_module|context_course
     */
    private static function resolve_context(int $cmid, int $courseid): context_module|context_course {
        global $DB;

        if ($cmid > 0) {
            $cm = get_coursemodule_from_id('lessonmark', $cmid, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
            require_login($course, false, $cm);
            return context_module::instance($cm->id);
        }
        if ($courseid > 0) {
            $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
            require_login($course);
            return context_course::instance($course->id);
        }
        throw new \moodle_exception('missingpreviewcontext', 'mod_lessonmark');
    }
}
