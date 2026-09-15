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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Displays a LessonMark resource.
 *
 * @package   mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$instanceid = optional_param('n', 0, PARAM_INT);
$present = optional_param('present', false, PARAM_BOOL);
if ($id) {
    $cm = get_coursemodule_from_id('lessonmark', $id, 0, false, MUST_EXIST);
    $lessonmark = $DB->get_record('lessonmark', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($instanceid) {
    $lessonmark = $DB->get_record('lessonmark', ['id' => $instanceid], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('lessonmark', $lessonmark->id, $lessonmark->course, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingidandcmid', 'mod_lessonmark');
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/lessonmark:view', $context);

$PAGE->set_url('/mod/lessonmark/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($lessonmark->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
if ($present) {
    $PAGE->set_url('/mod/lessonmark/view.php', ['id' => $cm->id, 'present' => 1]);
    $PAGE->set_pagelayout('embedded');
    $PAGE->activityheader->disable();
    $PAGE->requires->js_call_amd('mod_lessonmark/presentation', 'init');
}

$completion = new completion_info($course);
$completion->set_module_viewed($cm);
$event = \mod_lessonmark\event\course_module_viewed::create([
    'objectid' => $lessonmark->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('lessonmark', $lessonmark);
$event->trigger();

$renderer = new \mod_lessonmark\local\moodle_markdown_renderer();
$document = $renderer->render_with_print_breaks((string) $lessonmark->markdownsource, $context);
$contenthtml = $document->get_content_html();
$PAGE->requires->js_call_amd('mod_lessonmark/browser-print', 'init');
if ($present) {
    $contenthtml = '';
    foreach (\mod_lessonmark\local\presentation_source::split((string) $lessonmark->markdownsource) as $index => $source) {
        $slidehtml = $renderer->render($source, $context)->get_content_html();
        // Keep repeated heading IDs unique between independently rendered slides.
        $slidehtml = preg_replace_callback(
            '/\b(id="|for="|name="|href="#)([^"]*)"/',
            static fn(array $match): string => $match[1] . 'slide-' . $index . '-' . $match[2] . '"',
            $slidehtml
        );
        $contenthtml .= html_writer::tag('section', $slidehtml, [
            'class' => 'mod_lessonmark-slide',
            'tabindex' => '0',
            'aria-label' => get_string('presentationpage', 'mod_lessonmark') . ' ' . ($index + 1),
        ]);
    }
}

if (str_contains($contenthtml, 'language-')) {
    $PAGE->requires->js_call_amd('mod_lessonmark/syntax-highlighter', 'init', ['.mod_lessonmark-content']);
}
if (!$present && str_contains($contenthtml, 'data-self-check=')) {
    $PAGE->requires->js_call_amd('mod_lessonmark/self-check', 'init', [[
        'cmid' => (int) $cm->id,
        'userId' => (int) $USER->id,
    ]]);
}
if (\mod_lessonmark\local\browser_assets::requires_math($contenthtml)) {
    $PAGE->requires->data_for_js('ozmdMathConfig', [
        'katexCssUrl' => (new moodle_url('/mod/lessonmark/vendor/katex/katex.min.css'))->out(false),
        'labels' => [
            'copy' => get_string('copylatex', 'mod_lessonmark'),
            'copied' => get_string('copied', 'mod_lessonmark'),
            'copyFailed' => get_string('copyfailed', 'mod_lessonmark'),
        ],
    ]);
    $PAGE->requires->js(new moodle_url('/mod/lessonmark/vendor/math/math-render.min.js'));
}
if (\mod_lessonmark\local\browser_assets::requires_mermaid($contenthtml)) {
    $PAGE->requires->js(new moodle_url('/mod/lessonmark/vendor/mermaid/mermaid.min.js'));
    $PAGE->requires->js(new moodle_url('/mod/lessonmark/vendor/mermaid/mermaid-render.js'));
}

echo $OUTPUT->header();
if ($present) {
    echo html_writer::start_div('mod_lessonmark-presentation', ['data-cmid' => (string) $cm->id]);
    echo html_writer::start_div('mod_lessonmark-presentation-controls');
    foreach (['previous', 'next', 'fullscreen'] as $action) {
        echo html_writer::tag('button', get_string('presentation' . $action, 'mod_lessonmark'), [
            'type' => 'button', 'class' => 'btn btn-secondary', 'data-presentation-action' => $action,
        ]);
    }
    echo html_writer::tag('span', '', ['data-presentation-status' => '', 'role' => 'status', 'aria-live' => 'polite']);
    echo html_writer::link(
        new moodle_url('/mod/lessonmark/view.php', ['id' => $cm->id]),
        get_string('presentationreturn', 'mod_lessonmark'),
        ['class' => 'btn btn-secondary']
    );
    echo html_writer::end_div();
} else {
    foreach (\mod_lessonmark\local\course_presentation::modules($course) as $listedmodule) {
        if ((int) $listedmodule->id === (int) $cm->id) {
            echo html_writer::link(
                new moodle_url('/mod/lessonmark/course.php', ['id' => $cm->id]),
                get_string('coursepresentation', 'mod_lessonmark'),
                ['class' => 'btn btn-secondary mod_lessonmark-course-launch mb-3 mr-2']
            );
            break;
        }
    }
    echo html_writer::link(
        new moodle_url('/mod/lessonmark/view.php', ['id' => $cm->id, 'present' => 1]),
        get_string('presentation', 'mod_lessonmark'),
        ['class' => 'btn btn-secondary mod_lessonmark-presentation-launch mb-3']
    );
}
if (!$present && trim((string) $lessonmark->intro) !== '') {
    echo $OUTPUT->box(format_module_intro('lessonmark', $lessonmark, $cm->id), 'generalbox mod_introbox');
}
echo html_writer::div($contenthtml, 'mod_lessonmark-content');
if ($present) {
    echo html_writer::end_div();
}
echo $OUTPUT->footer();
