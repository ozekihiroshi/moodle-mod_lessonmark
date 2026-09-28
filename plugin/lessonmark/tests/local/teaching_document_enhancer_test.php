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
 * Tests for teaching-document HTML enhancement.
 *
 * @package   mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lessonmark\local;

/**
 * Tests safe teaching-document structure.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(teaching_document_enhancer::class)]
final class teaching_document_enhancer_test extends \advanced_testcase {
    /**
     * Tests heading IDs, duplicate handling, Unicode slugs, and TOC output.
     */
    public function test_enhances_headings_and_toc(): void {
        $enhancer = new teaching_document_enhancer();
        $document = $enhancer->enhance('<h1>Overview</h1><h2>日本語 見出し</h2><h2>日本語 見出し</h2>');

        $this->assertSame([
            ['id' => 'lessonmark-overview', 'level' => 1, 'text' => 'Overview'],
            ['id' => 'lessonmark-日本語-見出し', 'level' => 2, 'text' => '日本語 見出し'],
            ['id' => 'lessonmark-日本語-見出し-2', 'level' => 2, 'text' => '日本語 見出し'],
        ], $document->get_toc());
        $html = $document->get_content_html();
        $this->assertStringContainsString('class="mod_lessonmark-toc"', $html);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//nav/details[not(@open)]/summary')->length);
        $this->assertSame(3, $xpath->query('//nav/details/ol/li/a')->length);
        $this->assertStringContainsString(
            'href="#lessonmark-' . rawurlencode('日本語-見出し-2') . '"',
            $html
        );
        $this->assertStringContainsString('id="lessonmark-overview"', $html);
    }

    /**
     * Tests callouts, code languages, diagnostics, and responsive tables.
     */
    public function test_enhances_teaching_elements(): void {
        $enhancer = new teaching_document_enhancer();
        $document = $enhancer->enhance(
            '<blockquote><p>[!tip] Try <strong>this</strong>.</p></blockquote>'
            . '<pre><code class="py">print(&quot;Hi&quot;)</code></pre>'
            . '<pre><code class="brainfuck">+++</code></pre>'
            . '<table><tbody><tr><td>Cell</td></tr></tbody></table>'
        );
        $html = $document->get_content_html();

        $this->assertStringContainsString('mod_lessonmark-callout--tip', $html);
        $this->assertStringContainsString('role="note"', $html);
        $this->assertStringNotContainsString('[!tip]', $html);
        $this->assertStringContainsString('<strong>this</strong>', $html);
        $this->assertStringContainsString('class="mod_lessonmark-code language-python"', $html);
        $this->assertStringContainsString('class="language-python"', $html);
        $this->assertStringContainsString('class="mod_lessonmark-table-scroll"', $html);
        $this->assertStringContainsString('tabindex="0"', $html);
        $this->assertSame([
            ['type' => 'unsupportedlanguage', 'language' => 'brainfuck'],
        ], $document->get_diagnostics());
    }

    /**
     * Tests that formula markers remain available to the local browser renderer.
     */
    public function test_preserves_formula_markers_without_code_diagnostics(): void {
        $enhancer = new teaching_document_enhancer();
        $document = $enhancer->enhance(
            '<p>Inline <code>math:\\frac{a}{b}</code> and <code>asciimath:a/b</code>.</p>'
            . '<pre><code class="language-math">x^2</code></pre>'
            . '<pre><code class="language-latex">y^2</code></pre>'
            . '<pre><code class="language-asciimath">sqrt(x)</code></pre>'
        );
        $html = $document->get_content_html();

        $this->assertStringContainsString('<code>math:\\frac{a}{b}</code>', $html);
        $this->assertStringContainsString('class="mod_lessonmark-math-source language-math"', $html);
        $this->assertStringContainsString('class="language-latex"', $html);
        $this->assertStringContainsString('class="language-asciimath"', $html);
        $this->assertSame([], $document->get_diagnostics());
    }

    /**
     * Tests that Mermaid source is reserved for the local diagram renderer.
     */
    public function test_preserves_mermaid_marker_without_code_diagnostic(): void {
        $enhancer = new teaching_document_enhancer();
        $document = $enhancer->enhance(
            '<pre><code class="language-mermaid">flowchart LR; A--&gt;B</code></pre>'
        );
        $html = $document->get_content_html();

        $this->assertStringContainsString(
            'class="mod_lessonmark-mermaid-source language-mermaid"',
            $html
        );
        $this->assertStringContainsString('class="language-mermaid"', $html);
        $this->assertSame([], $document->get_diagnostics());
    }
    /**
     * Tests relative-image and alternative-text diagnostics.
     */
    public function test_reports_image_diagnostics(): void {
        $enhancer = new teaching_document_enhancer();
        $document = $enhancer->enhance(
            '<img src="images/local.png">'
            . '<img src="@@PLUGINFILE@@/managed.png" alt="Managed">'
            . '<img src="https://example.com/external.png" alt="">'
        );

        $this->assertSame([
            ['type' => 'missingimagealt', 'path' => 'images/local.png'],
            ['type' => 'unresolvedrelativeimage', 'path' => 'images/local.png'],
            ['type' => 'missingimagealt', 'path' => 'https://example.com/external.png'],
        ], $document->get_diagnostics());
        $this->assertStringContainsString(
            'src="@@PLUGINFILE@@/managed.png" alt="Managed"',
            $document->get_content_html()
        );
    }


    /**
     * Tests that Moodle-merged callout paragraphs become separate callouts.
     */
    public function test_splits_consecutive_callouts(): void {
        $enhancer = new teaching_document_enhancer();
        $document = $enhancer->enhance(
            '<blockquote><p>[!NOTE] First</p><p>[!TIP] Second</p><p>[!WARNING] Third</p></blockquote>'
        );
        $html = $document->get_content_html();
        $this->assertSame(3, substr_count($html, 'class="mod_lessonmark-callout '));
        $this->assertStringContainsString('mod_lessonmark-callout--note', $html);
        $this->assertStringContainsString('mod_lessonmark-callout--tip', $html);
        $this->assertStringContainsString('mod_lessonmark-callout--warning', $html);
    }

    /**
     * Tests ungraded response, choice, and answer disclosure blocks.
     */
    public function test_enhances_self_check_blocks(): void {
        $enhancer = new teaching_document_enhancer();
        $document = $enhancer->enhance(
            '<blockquote><p>[!RESPONSE] Explain your reasoning.</p>'
            . '<p>[!CHOICE] Select one.</p><ul><li>Alpha</li><li>Beta</li></ul>'
            . '<p>[!ANSWER]</p><p><strong>Official answer:</strong> Beta</p></blockquote>'
        );
        $html = $document->get_content_html();

        $this->assertStringContainsString('data-self-check="1"', $html);
        $this->assertStringContainsString('data-self-check-input="response"', $html);
        $this->assertStringContainsString('data-self-check="2"', $html);
        $this->assertSame(2, substr_count($html, 'data-self-check-input="choice"'));
        $this->assertLessThan(strpos($html, 'Select one.'), strpos($html, '<legend'));
        $this->assertStringContainsString('<details class="mod_lessonmark-selfcheck__answer">', $html);
        $this->assertStringContainsString('<summary>', $html);
        $this->assertStringContainsString('<strong>Official answer:</strong> Beta', $html);
        $this->assertStringNotContainsString('[!RESPONSE]', $html);
        $this->assertStringNotContainsString('[!CHOICE]', $html);
        $this->assertStringNotContainsString('[!ANSWER]', $html);
    }
}
