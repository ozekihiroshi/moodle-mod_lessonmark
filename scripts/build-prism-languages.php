<?php
/**
 * Builds the LessonMark Prism language AMD source from pinned upstream files.
 *
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = dirname(__DIR__);
$components = [
    'prism-bash.js' => '6c67db1a4c86269dc754b588d0ad3a0cdb295044fd466ea6f66bbf01dec306bd',
    'prism-json.js' => '835c44857c3f295f2c5bd70316006e455779da76287b0e14d93bbc995f658e4b',
    'prism-sql.js' => 'c208fdd212ff69c123c252290d5c325375bfefb0f6c26523b463909606cf3567',
];
$source = <<<'JS'
/*!
 * PrismJS 1.29.0: Bash, JSON and SQL language definitions (MIT).
 *
 * Upstream: https://github.com/PrismJS/prism/tree/v1.29.0/components
 * The definitions between the eslint markers are copied without modification
 * from vendor/prism/components. Only the Moodle import/export adapter is added.
 * Regenerate from the repository root: php scripts/build-prism-languages.php
 * Then build with Moodle's locked Grunt AMD toolchain. See
 * vendor/prism/readme_moodle.txt for checksums, licences and build instructions.
 *
 * MIT LICENSE
 *
 * Copyright (c) 2012 Lea Verou
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @module mod_lessonmark/prism-languages
 * @copyright 2012 Lea Verou
 * @license https://opensource.org/license/mit MIT
 */

import Prism from 'filter_codehighlighter/prism';

/* eslint-disable */
JS;

foreach ($components as $filename => $expectedhash) {
    $path = $root . '/plugin/lessonmark/vendor/prism/components/' . $filename;
    $actualhash = hash_file('sha256', $path);
    if ($actualhash === false || !hash_equals($expectedhash, $actualhash)) {
        fwrite(STDERR, "Prism component checksum mismatch: {$filename}\n");
        exit(1);
    }
    $component = file_get_contents($path);
    if ($component === false) {
        fwrite(STDERR, "Unable to read Prism component: {$filename}\n");
        exit(1);
    }
    $source .= "\n" . rtrim(str_replace("\r\n", "\n", $component)) . "\n";
}
$source .= <<<'JS'
/* eslint-enable */

export default Prism;
JS;
$source .= "\n";

$output = $root . '/plugin/lessonmark/amd/src/prism-languages.js';
if (!is_dir(dirname($output))) {
    mkdir(dirname($output), 0777, true);
}
if (file_put_contents($output, $source) === false) {
    fwrite(STDERR, "Unable to write generated Prism language module.\n");
    exit(1);
}
echo "Generated {$output}\n";
