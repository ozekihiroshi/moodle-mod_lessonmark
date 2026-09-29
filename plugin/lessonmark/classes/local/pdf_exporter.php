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
 * Creates a portable PDF from one saved LessonMark activity.
 *
 * @package   mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lessonmark\local;

/**
 * Server-side PDF export using Moodle's bundled TCPDF library.
 */
final class pdf_exporter {
    /**
     * Creates a safe PDF filename from an activity name.
     *
     * @param string $name Formatted activity name.
     * @return string Filename ending in .pdf.
     */
    public static function export_filename(string $name): string {
        $filename = clean_filename($name);
        $basename = preg_replace('/\.pdf$/iu', '', $filename) ?? '';
        $basename = trim($basename, " .\t\n\r\0\x0B");
        return ($basename === '' ? 'lessonmark' : $basename) . '.pdf';
    }

    /**
     * Generates one self-contained PDF from saved content.
     *
     * Browser-local RESPONSE and CHOICE values are deliberately not included.
     * ANSWER disclosures are expanded for print.
     *
     * @param \stdClass $lessonmark LessonMark record.
     * @param \stdClass $course Course record.
     * @param \context_module $context Module context.
     * @return string PDF bytes.
     */
    public function generate(\stdClass $lessonmark, \stdClass $course, \context_module $context): string {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->libdir . '/pdflib.php');

        $renderer = new moodle_markdown_renderer();
        $document = $renderer->render_with_print_breaks((string) $lessonmark->markdownsource, $context);
        $title = format_string($lessonmark->name, true, ['context' => $context]);
        $coursename = format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]);
        $html = $this->prepare_html($document->get_content_html(), $title, $coursename, $context);

        $pdf = new \pdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Moodle LessonMark');
        $pdf->SetAuthor(format_string($course->fullname));
        $pdf->SetTitle($title);
        $pdf->SetSubject($coursename);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('kozminproregular', '', 10);
        // TCPDF switches preformatted blocks to Courier unless this is set.
        // Use its bundled Japanese font so code and diagram source retain CJK text.
        $pdf->setDefaultMonospacedFont('kozminproregular');
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        return $pdf->Output('', 'S');
    }

    /**
     * Converts interactive HTML into a stable printable document.
     *
     * @param string $contenthtml Safe LessonMark HTML.
     * @param string $title Activity title.
     * @param string $coursename Course title.
     * @param \context_module $context Module context.
     * @return string TCPDF-compatible HTML.
     */
    public function prepare_html(
        string $contenthtml,
        string $title,
        string $coursename,
        \context_module $context
    ): string {
        $previouserrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="lessonmark-pdf-root">' . $contenthtml . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previouserrors);
        if (!$loaded) {
            throw new \coding_exception('LessonMark PDF HTML could not be prepared.');
        }
        $root = $dom->getElementById('lessonmark-pdf-root');
        if (!$root instanceof \DOMElement) {
            throw new \coding_exception('LessonMark PDF root is missing.');
        }

        $this->remove_duplicate_title($root, $title);
        $this->expand_answers($dom, $root);
        $this->replace_response_controls($dom, $root);
        $this->remove_nonprint_controls($root);
        $this->replace_print_page_breaks($dom, $root);
        $this->localise_images($dom, $root, $context);
        $this->keep_image_headings_together($dom, $root);
        $this->remove_unsafe_links($root);

        $body = '';
        foreach ($root->childNodes as $child) {
            $body .= $dom->saveHTML($child);
        }
        $escapetitle = s($title);
        $escapecourse = s($coursename);
        return '<style>'
            . 'body{font-size:10pt;line-height:1.45;color:#202124;}'
            . 'h1{font-size:20pt;color:#172b4d;}h2{font-size:16pt;color:#17365d;}'
            . 'h3{font-size:13pt;color:#244b74;}h4{font-size:11pt;color:#244b74;}'
            . 'h1,h2,h3,h4{page-break-after:avoid;}'
            . 'p{margin:0 0 6pt;}table{border-collapse:collapse;width:100%;}'
            . 'th,td{border:0.4pt solid #8a94a3;padding:4pt;}th{background-color:#edf2f7;}'
            . 'pre{border:0.4pt solid #bcc5d0;background-color:#f6f8fa;padding:6pt;font-size:8.5pt;}'
            . '.lessonmark-pdf-answer{border-left:2pt solid #245ca6;background-color:#f2f6fc;padding:7pt;}'
            . '.lessonmark-pdf-response{border:0.5pt solid #8a94a3;background-color:#fafafa;padding:7pt;}'
            . '.lessonmark-pdf-meta{font-size:8.5pt;color:#5f6368;border-bottom:0.5pt solid #c8cdd3;}'
            . 'img{max-width:170mm;height:auto;}'
            . '</style>'
            . '<h1>' . $escapetitle . '</h1>'
            . '<p class="lessonmark-pdf-meta">' . $escapecourse . '<br>'
            . s(get_string('pdfsavedcontentnote', 'mod_lessonmark')) . '</p>'
            . $body;
    }

    /**
     * Removes the source H1 when it repeats the Moodle activity title.
     *
     * @param \DOMElement $root Printable document root.
     * @param string $title Formatted activity title.
     * @return void
     */
    private function remove_duplicate_title(\DOMElement $root, string $title): void {
        foreach ($root->childNodes as $child) {
            if (!$child instanceof \DOMElement || strtolower($child->tagName) !== 'h1') {
                continue;
            }
            if (trim($child->textContent) === trim($title)) {
                $root->removeChild($child);
            }
            return;
        }
    }

    /**
     * Expands native ANSWER disclosures for print.
     *
     * @param \DOMDocument $dom Printable document.
     * @param \DOMElement $root Printable document root.
     * @return void
     */
    private function expand_answers(\DOMDocument $dom, \DOMElement $root): void {
        foreach (iterator_to_array($root->getElementsByTagName('details')) as $details) {
            if (!$details instanceof \DOMElement || !$details->parentNode instanceof \DOMNode) {
                continue;
            }
            $istoc = $details->parentNode instanceof \DOMElement
                && str_contains(' ' . $details->parentNode->getAttribute('class') . ' ', ' mod_lessonmark-toc ');
            $replacement = $dom->createElement('div');
            $replacement->setAttribute('class', $istoc ? 'lessonmark-pdf-toc' : 'lessonmark-pdf-answer');
            $heading = $dom->createElement(
                $istoc ? 'p' : 'h4',
                get_string($istoc ? 'tableofcontents' : 'pdfanswerheading', 'mod_lessonmark')
            );
            $replacement->appendChild($heading);
            foreach (iterator_to_array($details->childNodes) as $child) {
                if ($child instanceof \DOMElement && strtolower($child->tagName) === 'summary') {
                    continue;
                }
                $replacement->appendChild($child);
            }
            // Keep short text answers together; long answers must remain splittable.
            if (
                !$istoc && \core_text::strlen($replacement->textContent) <= 600
                && $replacement->getElementsByTagName('img')->length === 0
                && $replacement->getElementsByTagName('table')->length === 0
                && $replacement->getElementsByTagName('pre')->length === 0
            ) {
                $replacement->setAttribute('nobr', 'true');
            }
            $details->parentNode->replaceChild($replacement, $details);
        }
    }

    /**
     * Replaces browser inputs with blank printable working areas.
     *
     * @param \DOMDocument $dom Printable document.
     * @param \DOMElement $root Printable document root.
     * @return void
     */
    private function replace_response_controls(\DOMDocument $dom, \DOMElement $root): void {
        $xpath = new \DOMXPath($dom);
        $containers = $xpath->query('.//*[@data-self-check]', $root);
        if ($containers === false) {
            return;
        }
        foreach (iterator_to_array($containers) as $container) {
            if (!$container instanceof \DOMElement) {
                continue;
            }
            $container->setAttribute('class', 'lessonmark-pdf-response');
            if (\core_text::strlen($container->textContent) <= 300) {
                $container->setAttribute('nobr', 'true');
            }
            $container->removeAttribute('data-self-check');
            foreach (iterator_to_array($container->getElementsByTagName('input')) as $input) {
                if (!$input instanceof \DOMElement || !$input->parentNode instanceof \DOMNode) {
                    continue;
                }
                $input->parentNode->replaceChild($dom->createTextNode('[ ]'), $input);
            }
            foreach (iterator_to_array($container->getElementsByTagName('textarea')) as $textarea) {
                if (!$textarea instanceof \DOMElement || !$textarea->parentNode instanceof \DOMNode) {
                    continue;
                }
                $space = $dom->createElement(
                    'p',
                    "____________________________________________________________\n\n"
                        . '____________________________________________________________'
                );
                $textarea->parentNode->replaceChild($space, $textarea);
            }
            $xpath = new \DOMXPath($dom);
            $options = $xpath->query(
                './/label[contains(concat(" ", normalize-space(@class), " "), '
                    . '" mod_lessonmark-selfcheck__option ")]',
                $container
            );
            if ($options !== false) {
                foreach (iterator_to_array($options) as $option) {
                    if ($option->parentNode instanceof \DOMNode) {
                        $option->parentNode->insertBefore($dom->createElement('br'), $option->nextSibling);
                    }
                }
            }
        }
    }

    /**
     * Removes controls and browser-only status text that have no print meaning.
     *
     * @param \DOMElement $root Printable document root.
     * @return void
     */
    private function remove_nonprint_controls(\DOMElement $root): void {
        foreach (['button', 'script', 'style'] as $tagname) {
            foreach (iterator_to_array($root->getElementsByTagName($tagname)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }
        $xpath = new \DOMXPath($root->ownerDocument);
        $notes = $xpath->query(
            './/*[contains(concat(" ", normalize-space(@class), " "), " mod_lessonmark-selfcheck__note ")]',
            $root
        );
        if ($notes !== false) {
            foreach (iterator_to_array($notes) as $note) {
                $note->parentNode?->removeChild($note);
            }
        }
    }

    /**
     * Converts browser print boundaries into TCPDF's explicit page-break form.
     *
     * @param \DOMDocument $dom Printable document.
     * @param \DOMElement $root Printable document root.
     * @return void
     */
    private function replace_print_page_breaks(\DOMDocument $dom, \DOMElement $root): void {
        $xpath = new \DOMXPath($dom);
        $breaks = $xpath->query(
            './/*[contains(concat(" ", normalize-space(@class), " "), '
                . '" mod_lessonmark-print-page-break ")]',
            $root
        );
        if ($breaks === false) {
            return;
        }
        foreach (iterator_to_array($breaks) as $break) {
            if (!$break instanceof \DOMElement || !$break->parentNode instanceof \DOMNode) {
                continue;
            }
            $replacement = $dom->createElement('br');
            $replacement->setAttribute('pagebreak', 'true');
            $break->parentNode->replaceChild($replacement, $break);
        }
    }

    /**
     * Embeds Moodle-managed images as data URIs for consistent web and CLI PDF output.
     *
     * @param \DOMDocument $dom Printable document.
     * @param \DOMElement $root Printable document root.
     * @param \context_module $context Activity context.
     * @return void
     */
    private function localise_images(\DOMDocument $dom, \DOMElement $root, \context_module $context): void {
        foreach (iterator_to_array($root->getElementsByTagName('img')) as $image) {
            if (!$image instanceof \DOMElement || !$image->parentNode instanceof \DOMNode) {
                continue;
            }
            $file = $this->stored_file_from_url($image->getAttribute('src'), $context);
            if (!$file) {
                $alt = trim($image->getAttribute('alt'));
                $replacement = $dom->createElement(
                    'p',
                    '[' . ($alt === '' ? get_string('pdfimageomitted', 'mod_lessonmark') : $alt) . ']'
                );
                $image->parentNode->replaceChild($replacement, $image);
                continue;
            }
            $mimetype = strtolower(trim($file->get_mimetype()));
            if (!str_starts_with($mimetype, 'image/')) {
                throw new \coding_exception('A LessonMark PDF image has an invalid MIME type.');
            }
            $bytes = $file->get_content();
            $size = @getimagesizefromstring($bytes);
            if ($size !== false && $size[0] > 0 && $size[1] > 0) {
                // TCPDF does not honour CSS max-width. Reserve space for headings
                // as well as the image, and never enlarge small raster originals.
                $scale = min(25.4 / 96, 170 / $size[0], 210 / $size[1]);
                $image->setAttribute('width', sprintf('%.3Fmm', $size[0] * $scale));
                $image->setAttribute('height', sprintf('%.3Fmm', $size[1] * $scale));
            }
            $image->setAttribute(
                'src',
                'data:' . $mimetype . ';base64,' . base64_encode($bytes)
            );
            $image->removeAttribute('srcset');
            $image->removeAttribute('loading');
        }
    }

    /**
     * Keeps an image-only paragraph with its immediately preceding headings.
     *
     * TCPDF supports nobr, but ignores page-break-after:avoid. Do not group
     * arbitrary text or tables, which may themselves require several pages.
     *
     * @param \DOMDocument $dom Printable document.
     * @param \DOMElement $root Printable document root.
     */
    private function keep_image_headings_together(\DOMDocument $dom, \DOMElement $root): void {
        foreach (iterator_to_array($root->getElementsByTagName('p')) as $paragraph) {
            if (trim($paragraph->textContent) !== '' || $paragraph->getElementsByTagName('img')->length !== 1) {
                continue;
            }
            $nodes = [$paragraph];
            $previous = $paragraph->previousSibling;
            $headings = 0;
            while ($previous !== null) {
                if ($previous instanceof \DOMText && trim($previous->textContent) === '') {
                    array_unshift($nodes, $previous);
                } else if ($previous instanceof \DOMElement && preg_match('/^h[1-6]$/i', $previous->tagName)) {
                    array_unshift($nodes, $previous);
                    $headings++;
                } else {
                    break;
                }
                $previous = $previous->previousSibling;
            }
            if ($headings === 0) {
                continue;
            }
            $group = $dom->createElement('div');
            $group->setAttribute('nobr', 'true');
            $group->setAttribute('class', 'lessonmark-pdf-figure');
            $paragraph->parentNode->insertBefore($group, $nodes[0]);
            foreach ($nodes as $node) {
                $group->appendChild($node);
            }
        }
    }

    /**
     * Resolves only this activity's Moodle File API URLs; remote URLs are never fetched.
     *
     * @param string $url Image URL.
     * @param \context_module $context Activity context.
     * @return \stored_file|null Resolved file or null.
     */
    private function stored_file_from_url(string $url, \context_module $context): ?\stored_file {
        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        $marker = "/pluginfile.php/{$context->id}/mod_lessonmark/" . content_files::FILEAREA . '/0/';
        $position = strpos($path, $marker);
        if ($position === false) {
            return null;
        }
        $relative = ltrim(substr($path, $position + strlen($marker)), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }
        $parts = explode('/', $relative);
        $filename = array_pop($parts);
        $filepath = '/' . ($parts === [] ? '' : implode('/', $parts) . '/');
        $file = get_file_storage()->get_file(
            $context->id,
            'mod_lessonmark',
            content_files::FILEAREA,
            content_files::ITEMID,
            $filepath,
            $filename
        );
        return $file && !$file->is_directory() ? $file : null;
    }

    /**
     * Keeps ordinary links but prevents TCPDF from following unsafe URI schemes.
     *
     * @param \DOMElement $root Printable document root.
     * @return void
     */
    private function remove_unsafe_links(\DOMElement $root): void {
        foreach ($root->getElementsByTagName('a') as $link) {
            if (!$link instanceof \DOMElement) {
                continue;
            }
            $href = trim($link->getAttribute('href'));
            if ($href !== '' && preg_match('~^(?:https?://|#)~i', $href) !== 1) {
                $link->removeAttribute('href');
            }
        }
    }
}
