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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for LessonMark PDF export preparation.
 *
 * @package   mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lessonmark\local;

/**
 * Tests the printable transformation and binary generation.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(pdf_exporter::class)]
final class pdf_exporter_test extends \advanced_testcase {
    /**
     * Saved content produces a PDF with expanded answers and embedded images.
     */
    public function test_generate_pdf_from_saved_activity(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'PDF course']);
        $lessonmark = $this->getDataGenerator()->create_module('lessonmark', [
            'course' => $course->id,
            'name' => 'PDF lesson',
            'markdownsource' => "# PDF lesson\n\n"
                . "<!-- slide -->\n\n## Official questions\n\n### Image page\n\n"
                . "![Pixel](@@PLUGINFILE@@/pixel.png)\n\n"
                . "> [!RESPONSE]\n> Explain.\n\n"
                . "> [!ANSWER]\n> **Answer:** Evidence.",
        ]);
        $cm = get_coursemodule_from_instance('lessonmark', $lessonmark->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'mod_lessonmark',
            'filearea' => content_files::FILEAREA,
            'itemid' => content_files::ITEMID,
            'filepath' => '/',
            'filename' => 'pixel.png',
            'mimetype' => 'image/png',
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

        $exporter = new pdf_exporter();
        $document = (new moodle_markdown_renderer())->render_with_print_breaks(
            $lessonmark->markdownsource,
            $context
        );
        $html = $exporter->prepare_html($document->get_content_html(), 'PDF lesson', 'PDF course', $context);
        $this->assertStringNotContainsString('slide --', $html);
        $this->assertStringNotContainsString('mod_lessonmark-print-page-break', $html);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $groups = $xpath->query('//div[@class="lessonmark-pdf-figure" and @nobr="true"]');
        $this->assertCount(1, $groups);
        $this->assertSame('Official questions', $groups[0]->getElementsByTagName('h2')[0]->textContent);
        $this->assertSame('Image page', $groups[0]->getElementsByTagName('h3')[0]->textContent);
        $image = $groups[0]->getElementsByTagName('img')[0];
        $this->assertSame('0.265mm', $image->getAttribute('width'));
        $this->assertSame('0.265mm', $image->getAttribute('height'));
        $this->assertStringContainsString('Evidence.', $html);
        $this->assertCount(1, $xpath->query('//div[@class="lessonmark-pdf-toc"]'));
        $this->assertStringNotContainsString('<details', $html);
        $this->assertCount(1, $xpath->query('//div[@class="lessonmark-pdf-answer" and @nobr="true"]'));
        $this->assertCount(1, $xpath->query('//div[@class="lessonmark-pdf-response" and @nobr="true"]'));
        $this->assertCount(1, $xpath->query('//br[@pagebreak="true"]'));

        $bytes = $exporter->generate($lessonmark, $course, $context);
        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertStringContainsString('/Subtype /Image', $bytes);
        preg_match_all('/\/Type\s*\/Page\b/', $bytes, $pages);
        $this->assertCount(2, $pages[0]);
        $this->assertGreaterThan(1000, strlen($bytes));
    }

    /**
     * Browser-only formulas and diagrams remain readable source in PDF HTML.
     */
    public function test_prepare_html_retains_math_and_mermaid_source(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Portable course']);
        $lessonmark = $this->getDataGenerator()->create_module('lessonmark', [
            'course' => $course->id,
            'name' => 'Portable lesson',
            'markdownsource' => '# Portable lesson',
        ]);
        $cm = get_coursemodule_from_instance('lessonmark', $lessonmark->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $content = '<p><code>math:\\frac{a}{b}</code></p>'
            . '<pre class="mod_lessonmark-math-source"><code class="language-math">x^2</code></pre>'
            . '<pre class="mod_lessonmark-mermaid-source"><code class="language-mermaid">'
            . 'flowchart LR; A--&gt;B</code></pre>';

        $html = (new pdf_exporter())->prepare_html($content, 'Portable lesson', 'Portable course', $context);

        $this->assertStringContainsString('math:\\frac{a}{b}', $html);
        $this->assertStringContainsString('language-math', $html);
        $this->assertStringContainsString('x^2', $html);
        $this->assertStringContainsString('language-mermaid', $html);
        $this->assertStringContainsString('flowchart LR; A--&gt;B', $html);
        $this->assertStringNotContainsString('<svg', $html);

        $longanswer = '<details><summary>Answer</summary><p>' . str_repeat('Long answer. ', 100) . '</p></details>';
        $longhtml = (new pdf_exporter())->prepare_html($longanswer, 'Long', 'Course', $context);
        $this->assertStringNotContainsString('nobr="true"', $longhtml);
    }

    /**
     * Export filenames are safe and always end in .pdf.
     */
    public function test_export_filename(): void {
        $this->assertSame('Lesson.pdf', pdf_exporter::export_filename('Lesson.pdf'));
        $this->assertSame('lessonmark.pdf', pdf_exporter::export_filename('...'));
    }

    /**
     * Preformatted code must not fall back to Courier and replace Japanese with question marks.
     */
    public function test_pdf_code_retains_japanese_text(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $fence = str_repeat(chr(96), 3);
        $lessonmark = $this->getDataGenerator()->create_module('lessonmark', [
            'course' => $course->id,
            'name' => 'Code font regression',
            'markdownsource' => $fence . "mermaid\nflowchart LR\n A[レビュー] --> B[公開]\n" . $fence . "\n\n"
                . $fence . "python\nprint('合格')\n" . $fence,
        ]);
        $cm = get_coursemodule_from_instance('lessonmark', $lessonmark->id, $course->id, false, MUST_EXIST);
        $bytes = (new pdf_exporter())->generate($lessonmark, $course, \context_module::instance($cm->id));
        // Inspect decompressed PDF text streams, not just the intermediate HTML.
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $bytes, $matches);
        $streams = '';
        foreach ($matches[1] as $stream) {
            $decoded = @gzuncompress($stream);
            $streams .= $decoded === false ? $stream : $decoded;
        }
        foreach (['レビュー', '公開', '合格'] as $label) {
            $this->assertStringContainsString(mb_convert_encoding($label, 'UTF-16BE', 'UTF-8'), $streams);
        }
    }
}
