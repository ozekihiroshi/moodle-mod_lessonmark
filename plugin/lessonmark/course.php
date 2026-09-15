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
 * Continuous course presentation shell. Each frame request checks access again.
 * @package mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('lessonmark', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/lessonmark:view', $context);
$modules = \mod_lessonmark\local\course_presentation::modules($course);
$selected = null;
foreach ($modules as $index => $module) {
    if ((int) $module->id === $id) {
        $selected = $index;
    }
}
if ($selected === null) {
    throw new moodle_exception('coursenotlisted', 'mod_lessonmark');
}

$PAGE->set_url('/mod/lessonmark/course.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('embedded');
$PAGE->activityheader->disable();
$PAGE->requires->js_call_amd('mod_lessonmark/course-presentation', 'init');

echo $OUTPUT->header();
echo html_writer::start_div('mod_lessonmark-course-presentation', [
    'data-loading' => get_string('courseloading', 'mod_lessonmark'),
    'data-unavailable' => get_string('courseunavailable', 'mod_lessonmark'),
    'data-position' => get_string('courseposition', 'mod_lessonmark'),
]);
echo html_writer::start_tag('header', ['class' => 'mod_lessonmark-course-header']);
echo html_writer::tag('strong', format_string($course->fullname));
echo html_writer::link(
    new moodle_url('/course/view.php', ['id' => $course->id]),
    get_string('coursereturn', 'mod_lessonmark'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_tag('header');
echo html_writer::start_div('mod_lessonmark-course-toolbar');
echo html_writer::tag('label', get_string('courselessons', 'mod_lessonmark'), ['for' => 'lessonmark-course-outline']);
echo html_writer::start_tag('select', ['id' => 'lessonmark-course-outline', 'class' => 'custom-select']);
foreach ($modules as $index => $module) {
    $attributes = [
        'value' => (string) $module->id,
        'data-url' => (new moodle_url('/mod/lessonmark/view.php', ['id' => $module->id, 'present' => 1]))->out(false),
    ];
    if ($index === $selected) {
        $attributes['selected'] = 'selected';
    }
    echo html_writer::tag('option', ($index + 1) . '. ' . format_string($module->name), $attributes);
}
echo html_writer::end_tag('select');
foreach (['previous', 'next', 'fullscreen'] as $action) {
    echo html_writer::tag('button', get_string('presentation' . $action, 'mod_lessonmark'), [
        'type' => 'button', 'class' => 'btn btn-secondary', 'data-course-action' => $action,
    ]);
}
echo html_writer::tag('span', '', ['data-course-status' => '', 'role' => 'status', 'aria-live' => 'polite']);
echo html_writer::end_div();
echo html_writer::tag('iframe', '', [
    'id' => 'lessonmark-course-frame',
    'title' => get_string('coursepresentation', 'mod_lessonmark'),
    'src' => (new moodle_url('/mod/lessonmark/view.php', ['id' => $id, 'present' => 1]))->out(false),
]);
echo html_writer::tag('noscript', get_string('coursejavascript', 'mod_lessonmark'));
echo html_writer::end_div();
echo $OUTPUT->footer();
