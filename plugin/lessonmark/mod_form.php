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

/**
 * Module form for LessonMark.
 *
 * @package   mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Defines the LessonMark activity form.
 */
class mod_lessonmark_mod_form extends moodleform_mod {
    /**
     * Defines form elements.
     */
    public function definition(): void {
        global $PAGE;

        $mform = $this->_form;
        $mform->addElement('text', 'name', get_string('lessonmarkname', 'mod_lessonmark'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();
        $mform->addElement('textarea', 'markdownsource', get_string('markdownsource', 'mod_lessonmark'), [
            'rows' => 24,
            'cols' => 100,
            'wrap' => 'soft',
        ]);
        $mform->setType('markdownsource', PARAM_RAW);
        $mform->addRule('markdownsource', null, 'required', null, 'client');
        $mform->addHelpButton('markdownsource', 'markdownsource', 'mod_lessonmark');

        $editorsuffix = random_int(1000, 999999);
        $previewid = 'lessonmark-preview-' . $editorsuffix;
        $sourcepanelid = 'lessonmark-source-' . $editorsuffix;
        $edittabid = 'lessonmark-edit-tab-' . $editorsuffix;
        $previewtabid = 'lessonmark-preview-tab-' . $editorsuffix;
        $cmid = $this->_cm ? (int) $this->_cm->id : 0;
        $preview = html_writer::start_div('mod_lessonmark-editor', [
            'data-region' => 'lessonmark-editor',
            'data-preview-id' => $previewid,
        ]);
        $preview .= html_writer::start_div('mod_lessonmark-editor__toolbar');
        $preview .= html_writer::start_div('mod_lessonmark-editor__tabs', [
            'role' => 'tablist',
            'aria-label' => get_string('editormodes', 'mod_lessonmark'),
        ]);
        $preview .= html_writer::tag('button', get_string('editmode', 'mod_lessonmark'), [
            'id' => $edittabid,
            'type' => 'button',
            'class' => 'btn btn-secondary mod_lessonmark-editor__tab is-active',
            'data-action' => 'show-editor',
            'role' => 'tab',
            'aria-selected' => 'true',
            'aria-controls' => $sourcepanelid,
            'tabindex' => '0',
        ]);
        $preview .= html_writer::tag('button', get_string('previewmode', 'mod_lessonmark'), [
            'id' => $previewtabid,
            'type' => 'button',
            'class' => 'btn btn-secondary mod_lessonmark-editor__tab',
            'data-action' => 'show-preview',
            'role' => 'tab',
            'aria-selected' => 'false',
            'aria-controls' => $previewid,
            'tabindex' => '-1',
        ]);
        $preview .= html_writer::end_div();
        $preview .= html_writer::start_div('mod_lessonmark-editor__actions');
        $preview .= html_writer::tag('button', get_string('importmarkdown', 'mod_lessonmark'), [
            'type' => 'button',
            'class' => 'btn btn-link',
            'data-action' => 'import-markdown',
        ]);
        $preview .= html_writer::empty_tag('input', [
            'type' => 'file',
            'accept' => '.md,text/markdown,text/plain',
            'hidden' => 'hidden',
            'data-action' => 'import-file',
            'aria-label' => get_string('importfilelabel', 'mod_lessonmark'),
        ]);
        if ($cmid > 0) {
            $exporturl = new moodle_url('/mod/lessonmark/export.php', [
                'id' => $cmid,
                'sesskey' => sesskey(),
            ]);
            $preview .= html_writer::link(
                $exporturl,
                get_string('exportsavedmarkdown', 'mod_lessonmark'),
                ['class' => 'btn btn-link', 'data-action' => 'export-markdown']
            );
            $pdfurl = new moodle_url('/mod/lessonmark/export_pdf.php', [
                'id' => $cmid,
                'sesskey' => sesskey(),
            ]);
            $preview .= html_writer::link(
                $pdfurl,
                get_string('exportsavedpdf', 'mod_lessonmark'),
                ['class' => 'btn btn-link', 'data-action' => 'export-pdf']
            );
        }
        $preview .= html_writer::tag('button', get_string('refreshpreview', 'mod_lessonmark'), [
            'type' => 'button',
            'class' => 'btn btn-link',
            'data-action' => 'refresh-preview',
        ]);
        $preview .= html_writer::end_div();
        $preview .= html_writer::end_div();
        $preview .= html_writer::div(
            get_string('previewempty', 'mod_lessonmark'),
            'mod_lessonmark-editor__preview mod_lessonmark-content text-muted',
            [
                'id' => $previewid,
                'data-region' => 'preview-content',
                'role' => 'region',
                'aria-label' => get_string('previewmode', 'mod_lessonmark'),
                'aria-busy' => 'false',
                'tabindex' => '0',
            ]
        );
        $preview .= html_writer::div('', 'mod_lessonmark-editor__status', [
            'data-region' => 'preview-status',
            'role' => 'status',
            'aria-live' => 'polite',
        ]);
        $preview .= html_writer::end_div();
        $mform->addElement('html', $preview);
        $mform->addElement('header', 'imagessection', get_string('imagessection', 'mod_lessonmark'));
        $mform->addElement(
            'filemanager',
            \mod_lessonmark\local\content_files::FORM_FIELD,
            get_string('imagefiles', 'mod_lessonmark'),
            null,
            \mod_lessonmark\local\content_files::options()
        );
        $mform->addHelpButton(\mod_lessonmark\local\content_files::FORM_FIELD, 'imagefiles', 'mod_lessonmark');

        $courseid = (int) ($this->current->course ?? 0);
        $PAGE->requires->data_for_js('ozmdMathConfig', [
            'katexCssUrl' => (new moodle_url('/mod/lessonmark/vendor/katex/katex.min.css'))->out(false),
            'labels' => [
                'copy' => get_string('copylatex', 'mod_lessonmark'),
                'copied' => get_string('copied', 'mod_lessonmark'),
                'copyFailed' => get_string('copyfailed', 'mod_lessonmark'),
            ],
        ]);
        $PAGE->requires->js(new moodle_url('/mod/lessonmark/vendor/math/math-render.min.js'));
        $PAGE->requires->js(new moodle_url('/mod/lessonmark/vendor/mermaid/mermaid.min.js'));
        $PAGE->requires->js(new moodle_url('/mod/lessonmark/vendor/mermaid/mermaid-render.js'));
        $PAGE->requires->js_call_amd('mod_lessonmark/editor', 'init', [[
            'sourceSelector' => '#id_markdownsource',
            'containerSelector' => '[data-preview-id="' . $previewid . '"]',
            'sourcePanelId' => $sourcepanelid,
            'editTabId' => $edittabid,
            'previewTabId' => $previewtabid,
            'filesSelector' => '#id_' . \mod_lessonmark\local\content_files::FORM_FIELD,
            'cmid' => $cmid,
            'courseid' => $courseid,
            'maxSourceBytes' => \mod_lessonmark\local\moodle_markdown_renderer::MAX_SOURCE_BYTES,
        ]]);
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Copies existing teaching images into the user's editing draft area.
     *
     * @param array $defaultvalues Form defaults.
     */
    public function data_preprocessing(&$defaultvalues): void {
        if (!$this->current->instance) {
            return;
        }
        $field = \mod_lessonmark\local\content_files::FORM_FIELD;
        $draftitemid = file_get_submitted_draft_itemid($field);
        \mod_lessonmark\local\content_files::prepare_draft_area(
            $draftitemid,
            $this->context
        );
        $defaultvalues[$field] = $draftitemid;
    }

    /**
     * Validates Markdown source constraints.
     *
     * @param array $data Submitted values.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $source = (string) ($data['markdownsource'] ?? '');
        if (strlen($source) > \mod_lessonmark\local\moodle_markdown_renderer::MAX_SOURCE_BYTES) {
            $errors['markdownsource'] = get_string('errorsourcetoolarge', 'mod_lessonmark');
        } else if (preg_match('//u', $source) !== 1) {
            $errors['markdownsource'] = get_string('errorinvalidutf8', 'mod_lessonmark');
        }
        return $errors;
    }
}
