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
 * Slide boundary tests.
 * @package mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lessonmark\local;

/**
 * Tests structural markers without interpreting HTML.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(presentation_source::class)]
final class presentation_source_test extends \basic_testcase {
    /**
     * Standalone markers split, while ordinary horizontal rules do not.
     */
    public function test_boundaries(): void {
        $this->assertSame(['# A', '# B'], presentation_source::split("# A\n<!-- slide -->\n# B"));
        $this->assertCount(1, presentation_source::split("# A\n---\n# B"));
        $this->assertSame([''], presentation_source::split("<!-- slide -->\n<!-- slide -->"));
    }

    /**
     * Literal code, indentation and quoted markers remain source text.
     */
    public function test_literal_markers(): void {
        $fence = str_repeat(chr(96), 3);
        $sources = [
            "~~~\n<!-- slide -->\n~~~",
            $fence . chr(96) . "\n" . $fence . "\n<!-- slide -->\n" . $fence . chr(96),
            '    <!-- slide -->',
            '> <!-- slide -->',
            'Text <!-- slide -->',
        ];
        foreach ($sources as $source) {
            $this->assertSame([$source], presentation_source::split($source));
        }
    }
}
