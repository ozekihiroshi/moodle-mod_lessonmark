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
 * Teaching-document HTML enhancement.
 *
 * @package   mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lessonmark\local;

/**
 * Adds fixed, safe teaching-document structure to Moodle-rendered HTML.
 */
final class teaching_document_enhancer {
    /** Supported callout types and their language strings. */
    private const CALLOUTS = [
        'note' => 'calloutnote',
        'tip' => 'callouttip',
        'warning' => 'calloutwarning',
    ];

    /** Supported language aliases and their canonical Prism language. */
    private const LANGUAGE_ALIASES = [
        'text' => 'plaintext',
        'plain' => 'plaintext',
        'plaintext' => 'plaintext',
        'bash' => 'bash',
        'sh' => 'bash',
        'shell' => 'bash',
        'css' => 'css',
        'html' => 'markup',
        'xml' => 'markup',
        'javascript' => 'javascript',
        'js' => 'javascript',
        'json' => 'json',
        'php' => 'php',
        'python' => 'python',
        'py' => 'python',
        'sql' => 'sql',
    ];

    /** Display names for canonical code languages. */
    private const LANGUAGE_LABELS = [
        'plaintext' => 'Plain text',
        'bash' => 'Bash',
        'css' => 'CSS',
        'markup' => 'HTML/XML',
        'javascript' => 'JavaScript',
        'json' => 'JSON',
        'php' => 'PHP',
        'python' => 'Python',
        'sql' => 'SQL',
    ];

    /**
     * Enhances safe HTML and returns the resulting document.
     *
     * @param string $html Moodle-rendered and cleaned HTML.
     * @return rendered_document Enhanced document.
     */
    public function enhance(string $html): rendered_document {
        if (trim($html) === '') {
            return new rendered_document('');
        }

        $previouserrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="lessonmark-document-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previouserrors);
        if (!$loaded) {
            return new rendered_document($html, [], [['type' => 'htmlenhancementfailed']]);
        }

        $root = $dom->getElementById('lessonmark-document-root');
        if (!$root instanceof \DOMElement) {
            return new rendered_document($html, [], [['type' => 'htmlenhancementfailed']]);
        }

        $toc = $this->enhance_headings($dom, $root);
        $this->enhance_self_checks($dom, $root);
        $this->enhance_callouts($dom, $root);
        $diagnostics = $this->enhance_code_blocks($root);
        $this->enhance_tables($dom, $root);
        $diagnostics = array_merge($diagnostics, $this->diagnose_images($root));
        if (count($toc) > 1) {
            $this->prepend_toc($dom, $root, $toc);
        }

        $content = '';
        foreach ($root->childNodes as $child) {
            $content .= $dom->saveHTML($child);
        }
        return new rendered_document($content, $toc, $diagnostics);
    }

    /**
     * Adds stable heading IDs and builds TOC metadata.
     *
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $root Root element.
     * @return array TOC entries.
     */
    private function enhance_headings(\DOMDocument $dom, \DOMElement $root): array {
        $xpath = new \DOMXPath($dom);
        $headings = $xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6', $root);
        if ($headings === false) {
            return [];
        }

        $toc = [];
        $usedids = [];
        foreach ($headings as $heading) {
            if (!$heading instanceof \DOMElement) {
                continue;
            }
            $text = preg_replace('/\s+/u', ' ', trim($heading->textContent)) ?? '';
            $base = $this->heading_slug($text);
            $usedids[$base] = ($usedids[$base] ?? 0) + 1;
            $id = 'lessonmark-' . $base;
            if ($usedids[$base] > 1) {
                $id .= '-' . $usedids[$base];
            }
            $heading->setAttribute('id', $id);
            $heading->setAttribute('class', trim($heading->getAttribute('class') . ' mod_lessonmark-heading'));
            $toc[] = [
                'id' => $id,
                'level' => (int) substr($heading->tagName, 1),
                'text' => $text,
            ];
        }
        return $toc;
    }

    /**
     * Builds a Unicode-safe heading slug.
     *
     * @param string $text Heading text.
     * @return string Slug without the component prefix.
     */
    private function heading_slug(string $text): string {
        $slug = \core_text::strtolower($text);
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        return $slug === '' ? 'section' : $slug;
    }

    /**
     * Converts fixed Markdown blockquote markers into safe self-check controls.
     *
     * The controls are deliberately ungraded. Learner state is handled only in
     * the browser and is never submitted as a Moodle quiz attempt.
     *
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $root Root element.
     */
    private function enhance_self_checks(\DOMDocument $dom, \DOMElement $root): void {
        $blockquotes = iterator_to_array($root->getElementsByTagName('blockquote'));
        $controlindex = 0;
        foreach ($blockquotes as $blockquote) {
            if (!$blockquote instanceof \DOMElement || !$blockquote->parentNode instanceof \DOMNode) {
                continue;
            }
            $markers = [];
            foreach ($blockquote->childNodes as $child) {
                if (!$child instanceof \DOMElement || \core_text::strtolower($child->tagName) !== 'p') {
                    continue;
                }
                $firstnode = $child->firstChild;
                if (
                    $firstnode instanceof \DOMText
                    && preg_match('/^\s*\[!(RESPONSE|CHOICE|ANSWER)\]\s*/iu', $firstnode->data, $matches) === 1
                ) {
                    $markers[] = [
                        'paragraph' => $child,
                        'type' => \core_text::strtolower($matches[1]),
                    ];
                }
            }
            $firstparagraph = $this->first_direct_child($blockquote, 'p');
            if ($markers === [] || $markers[0]['paragraph'] !== $firstparagraph) {
                continue;
            }

            $blocks = [0 => $blockquote];
            $insertionpoint = $blockquote->nextSibling;
            for ($markerindex = count($markers) - 1; $markerindex >= 1; $markerindex--) {
                $block = $dom->createElement('blockquote');
                $node = $markers[$markerindex]['paragraph'];
                while ($node !== null) {
                    $nextnode = $node->nextSibling;
                    $block->appendChild($node);
                    $node = $nextnode;
                }
                $blockquote->parentNode?->insertBefore($block, $insertionpoint);
                $insertionpoint = $block;
                $blocks[$markerindex] = $block;
            }

            ksort($blocks);
            foreach ($blocks as $markerindex => $block) {
                $paragraph = $markers[$markerindex]['paragraph'];
                $firstnode = $paragraph->firstChild;
                if (!$firstnode instanceof \DOMText) {
                    continue;
                }
                $type = $markers[$markerindex]['type'];
                $firstnode->data = preg_replace(
                    '/^\s*\[!(?:RESPONSE|CHOICE|ANSWER)\]\s*/iu',
                    '',
                    $firstnode->data
                ) ?? '';
                if (trim($paragraph->textContent) === '') {
                    $block->removeChild($paragraph);
                }
                if ($type === 'answer') {
                    $this->replace_with_answer_disclosure($dom, $block);
                } else {
                    $controlindex++;
                    $this->replace_with_response_control($dom, $block, $type, $controlindex);
                }
            }
        }
    }

    /**
     * Replaces an ANSWER block with a native accessible disclosure.
     *
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $blockquote Source blockquote.
     */
    private function replace_with_answer_disclosure(\DOMDocument $dom, \DOMElement $blockquote): void {
        $details = $dom->createElement('details');
        $details->setAttribute('class', 'mod_lessonmark-selfcheck__answer');
        $summary = $dom->createElement('summary', get_string('selfcheckreveal', 'mod_lessonmark'));
        $details->appendChild($summary);
        while ($blockquote->firstChild !== null) {
            $details->appendChild($blockquote->firstChild);
        }
        $blockquote->parentNode?->replaceChild($details, $blockquote);
    }

    /**
     * Replaces a RESPONSE or CHOICE block with an ungraded learner control.
     *
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $blockquote Source blockquote.
     * @param string $type response or choice.
     * @param int $index Stable sequence within the rendered document.
     */
    private function replace_with_response_control(
        \DOMDocument $dom,
        \DOMElement $blockquote,
        string $type,
        int $index
    ): void {
        $container = $dom->createElement($type === 'choice' ? 'fieldset' : 'div');
        $container->setAttribute('class', 'mod_lessonmark-selfcheck mod_lessonmark-selfcheck--' . $type);
        $container->setAttribute('data-self-check', (string) $index);

        $label = get_string($type === 'choice' ? 'selfcheckchoice' : 'selfcheckresponse', 'mod_lessonmark');
        if ($type === 'choice') {
            $legend = $dom->createElement('legend', $label);
            $legend->setAttribute('class', 'mod_lessonmark-selfcheck__label');
            $container->appendChild($legend);
            $items = $blockquote->getElementsByTagName('li');
            foreach (iterator_to_array($items) as $itemindex => $item) {
                if (!$item instanceof \DOMElement) {
                    continue;
                }
                $option = $dom->createElement('label');
                $option->setAttribute('class', 'mod_lessonmark-selfcheck__option');
                $input = $dom->createElement('input');
                $input->setAttribute('type', 'radio');
                $input->setAttribute('name', 'lessonmark-selfcheck-' . $index);
                $input->setAttribute('value', (string) $itemindex);
                $input->setAttribute('data-self-check-input', 'choice');
                $option->appendChild($input);
                $option->appendChild($dom->createTextNode(' ' . trim($item->textContent)));
                $container->appendChild($option);
            }
        } else {
            $id = 'lessonmark-selfcheck-response-' . $index;
            $labelnode = $dom->createElement('label', $label);
            $labelnode->setAttribute('class', 'mod_lessonmark-selfcheck__label');
            $labelnode->setAttribute('for', $id);
            $container->appendChild($labelnode);
            $textarea = $dom->createElement('textarea');
            $textarea->setAttribute('id', $id);
            $textarea->setAttribute('class', 'form-control mod_lessonmark-selfcheck__textarea');
            $textarea->setAttribute('rows', '4');
            $textarea->setAttribute('data-self-check-input', 'response');
            $container->appendChild($textarea);
        }

        $instructionpoint = $type === 'choice'
            ? $container->firstChild?->nextSibling
            : $container->firstChild;
        foreach (iterator_to_array($blockquote->childNodes) as $child) {
            if ($child instanceof \DOMElement && \core_text::strtolower($child->tagName) === 'ul') {
                continue;
            }
            $container->insertBefore($child, $instructionpoint);
        }
        $note = $dom->createElement('p', get_string('selfchecklocalnote', 'mod_lessonmark'));
        $note->setAttribute('class', 'mod_lessonmark-selfcheck__note');
        $container->appendChild($note);
        $button = $dom->createElement('button', get_string('selfcheckclear', 'mod_lessonmark'));
        $button->setAttribute('type', 'button');
        $button->setAttribute('class', 'btn btn-secondary btn-sm');
        $button->setAttribute('data-action', 'clear-self-check');
        $container->appendChild($button);
        $blockquote->parentNode?->replaceChild($container, $blockquote);
    }

    /**
     * Converts supported blockquotes into accessible teaching callouts.
     *
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $root Root element.
     */
    private function enhance_callouts(\DOMDocument $dom, \DOMElement $root): void {
        $blockquotes = iterator_to_array($root->getElementsByTagName('blockquote'));
        foreach ($blockquotes as $blockquote) {
            if (!$blockquote instanceof \DOMElement) {
                continue;
            }
            $markers = [];
            foreach ($blockquote->childNodes as $child) {
                if (!$child instanceof \DOMElement || \core_text::strtolower($child->tagName) !== 'p') {
                    continue;
                }
                $firstnode = $child->firstChild;
                if (
                    $firstnode instanceof \DOMText
                    && preg_match('/^\s*\[!(NOTE|TIP|WARNING)\]\s*/iu', $firstnode->data, $matches) === 1
                ) {
                    $markers[] = [
                        'paragraph' => $child,
                        'type' => \core_text::strtolower($matches[1]),
                    ];
                }
            }
            $firstparagraph = $this->first_direct_child($blockquote, 'p');
            if ($markers === [] || $markers[0]['paragraph'] !== $firstparagraph) {
                continue;
            }

            $callouts = [0 => $blockquote];
            $insertionpoint = $blockquote->nextSibling;
            for ($index = count($markers) - 1; $index >= 1; $index--) {
                $callout = $dom->createElement('blockquote');
                $node = $markers[$index]['paragraph'];
                while ($node !== null) {
                    $nextnode = $node->nextSibling;
                    $callout->appendChild($node);
                    $node = $nextnode;
                }
                $blockquote->parentNode?->insertBefore($callout, $insertionpoint);
                $insertionpoint = $callout;
                $callouts[$index] = $callout;
            }

            ksort($callouts);
            foreach ($callouts as $index => $callout) {
                $this->style_callout($dom, $callout, $markers[$index]['paragraph'], $markers[$index]['type']);
            }
        }
    }

    /**
     * Applies fixed callout structure and removes its source marker.
     *
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $blockquote Callout blockquote.
     * @param \DOMElement $paragraph Marker paragraph.
     * @param string $type Callout type.
     */
    private function style_callout(
        \DOMDocument $dom,
        \DOMElement $blockquote,
        \DOMElement $paragraph,
        string $type
    ): void {
        $firstnode = $paragraph->firstChild;
        if (!$firstnode instanceof \DOMText) {
            return;
        }
        $firstnode->data = preg_replace('/^\s*\[!(?:NOTE|TIP|WARNING)\]\s*/iu', '', $firstnode->data) ?? '';
        if ($firstnode->data === '') {
            $paragraph->removeChild($firstnode);
        }

        $label = get_string(self::CALLOUTS[$type], 'mod_lessonmark');
        $blockquote->setAttribute('class', 'mod_lessonmark-callout mod_lessonmark-callout--' . $type);
        $blockquote->setAttribute('role', 'note');
        $blockquote->setAttribute('aria-label', $label);
        $title = $dom->createElement('p');
        $title->setAttribute('class', 'mod_lessonmark-callout__title');
        $icon = $dom->createElement('span', $this->callout_icon($type));
        $icon->setAttribute('class', 'mod_lessonmark-callout__icon');
        $icon->setAttribute('aria-hidden', 'true');
        $title->appendChild($icon);
        $title->appendChild($dom->createTextNode(' ' . $label));
        $blockquote->insertBefore($title, $paragraph);
    }

    /**
     * Returns a fixed visible callout icon.
     *
     * @param string $type Callout type.
     * @return string Icon text.
     */
    private function callout_icon(string $type): string {
        return match ($type) {
            'tip' => '✓',
            'warning' => '!',
            default => 'i',
        };
    }

    /**
     * Normalises fenced-code classes for the bundled Prism integration.
     *
     * @param \DOMElement $root Root element.
     * @return array Diagnostics for unsupported language names.
     */
    private function enhance_code_blocks(\DOMElement $root): array {
        $diagnostics = [];
        $blocks = iterator_to_array($root->getElementsByTagName('pre'));
        foreach ($blocks as $pre) {
            if (!$pre instanceof \DOMElement) {
                continue;
            }
            $code = $this->first_direct_child($pre, 'code');
            if (!$code instanceof \DOMElement) {
                continue;
            }
            $sourceclass = trim(\core_text::strtolower($code->getAttribute('class')));
            // Newer Moodle 5.2 puts the fenced-code language on pre; older
            // versions put it on code. Accept both before applying our allowlist.
            if ($sourceclass === '') {
                $sourceclass = trim(\core_text::strtolower($pre->getAttribute('class')));
            }
            $sourceclass = preg_replace('/^language-/', '', $sourceclass) ?? $sourceclass;
            $sourceclass = preg_split('/\s+/', $sourceclass, 2)[0] ?? '';
            if (in_array($sourceclass, ['math', 'latex', 'asciimath'], true)) {
                $languageclass = 'language-' . $sourceclass;
                $pre->setAttribute('class', 'mod_lessonmark-math-source ' . $languageclass);
                $pre->setAttribute('aria-label', get_string('mathsourceblock', 'mod_lessonmark'));
                $code->setAttribute('class', $languageclass);
                continue;
            }
            if ($sourceclass === 'mermaid') {
                $code->setAttribute('class', 'language-mermaid');
                $pre->setAttribute('class', 'mod_lessonmark-mermaid-source language-mermaid');
                $pre->setAttribute('aria-label', get_string('mermaidsourceblock', 'mod_lessonmark'));
                continue;
            }
            $language = self::LANGUAGE_ALIASES[$sourceclass] ?? null;
            $pre->setAttribute('class', 'mod_lessonmark-code');
            $code->removeAttribute('class');

            if ($sourceclass !== '' && $language === null) {
                $diagnostics[] = ['type' => 'unsupportedlanguage', 'language' => $sourceclass];
            }
            if ($language === null || $language === 'plaintext') {
                $pre->setAttribute('aria-label', get_string('codeblocklabel', 'mod_lessonmark', 'Plain text'));
                continue;
            }
            $languageclass = 'language-' . $language;
            $pre->setAttribute('class', 'mod_lessonmark-code ' . $languageclass);
            $pre->setAttribute('aria-label', get_string(
                'codeblocklabel',
                'mod_lessonmark',
                self::LANGUAGE_LABELS[$language]
            ));
            $code->setAttribute('class', $languageclass);
        }
        return $diagnostics;
    }

    /**
     * Wraps tables in keyboard-accessible horizontal scrolling regions.
     *
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $root Root element.
     */
    private function enhance_tables(\DOMDocument $dom, \DOMElement $root): void {
        $tables = iterator_to_array($root->getElementsByTagName('table'));
        foreach ($tables as $table) {
            if (!$table instanceof \DOMElement || !$table->parentNode instanceof \DOMNode) {
                continue;
            }
            $table->setAttribute('class', 'table mod_lessonmark-table');
            $wrapper = $dom->createElement('div');
            $wrapper->setAttribute('class', 'mod_lessonmark-table-scroll');
            $wrapper->setAttribute('role', 'region');
            $wrapper->setAttribute('tabindex', '0');
            $wrapper->setAttribute('aria-label', get_string('scrollabletable', 'mod_lessonmark'));
            $table->parentNode->replaceChild($wrapper, $table);
            $wrapper->appendChild($table);
        }
    }

    /**
     * Reports image references that cannot be published safely as-is.
     *
     * @param \DOMElement $root Root element.
     * @return array Image diagnostics.
     */
    private function diagnose_images(\DOMElement $root): array {
        $diagnostics = [];
        foreach ($root->getElementsByTagName('img') as $image) {
            if (!$image instanceof \DOMElement) {
                continue;
            }
            $source = trim($image->getAttribute('src'));
            if (!$image->hasAttribute('alt') || trim($image->getAttribute('alt')) === '') {
                $diagnostics[] = [
                    'type' => 'missingimagealt',
                    'path' => $source,
                ];
            }
            if ($source === '') {
                continue;
            }
            $isresolved = preg_match(
                '~^(?:[a-z][a-z0-9+.-]*:|//|/|#|@@PLUGINFILE@@/)~i',
                $source
            ) === 1;
            if (!$isresolved) {
                $diagnostics[] = [
                    'type' => 'unresolvedrelativeimage',
                    'path' => $source,
                ];
            }
        }
        return $diagnostics;
    }
    /**
     * Prepends an automatic table of contents.
     *
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $root Root element.
     * @param array $toc TOC entries.
     */
    private function prepend_toc(\DOMDocument $dom, \DOMElement $root, array $toc): void {
        $nav = $dom->createElement('nav');
        $nav->setAttribute('class', 'mod_lessonmark-toc');
        $nav->setAttribute('aria-label', get_string('tableofcontents', 'mod_lessonmark'));
        $details = $dom->createElement('details');
        $title = $dom->createElement('summary', get_string('tableofcontents', 'mod_lessonmark'));
        $title->setAttribute('class', 'mod_lessonmark-toc__title');
        $details->appendChild($title);
        $list = $dom->createElement('ol');
        $list->setAttribute('class', 'mod_lessonmark-toc__list');
        foreach ($toc as $entry) {
            $item = $dom->createElement('li');
            $item->setAttribute('class', 'mod_lessonmark-toc__level--' . $entry['level']);
            $link = $dom->createElement('a');
            $link->setAttribute('href', '#' . $entry['id']);
            $link->appendChild($dom->createTextNode($entry['text']));
            $item->appendChild($link);
            $list->appendChild($item);
        }
        $details->appendChild($list);
        $nav->appendChild($details);
        $root->insertBefore($nav, $root->firstChild);
    }

    /**
     * Finds the first direct child element with a tag name.
     *
     * @param \DOMElement $parent Parent element.
     * @param string $tagname Lowercase tag name.
     * @return \DOMElement|null Matching child.
     */
    private function first_direct_child(\DOMElement $parent, string $tagname): ?\DOMElement {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof \DOMElement && \core_text::strtolower($child->tagName) === $tagname) {
                return $child;
            }
        }
        return null;
    }
}
