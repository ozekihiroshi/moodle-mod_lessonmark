<?php
/**
 * Verifies LessonMark release metadata and the installable ZIP.
 *
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$repositoryroot = dirname(__DIR__);
$pluginroot = $repositoryroot . '/plugin/lessonmark';
$versioncontents = file_get_contents($pluginroot . '/version.php');
if ($versioncontents === false) {
    fwrite(STDERR, "Unable to read plugin version.php.\n");
    exit(1);
}

$checks = [
    '/\$plugin->component\s*=\s*\'mod_lessonmark\';/' => 'component',
    '/\$plugin->version\s*=\s*\d{10};/' => 'build number',
    '/\$plugin->requires\s*=\s*2026042000;/' => 'Moodle requirement',
    '/\$plugin->supported\s*=\s*\[502,\s*502\];/' => 'support range',
];
foreach ($checks as $pattern => $description) {
    if (preg_match($pattern, $versioncontents) !== 1) {
        fwrite(STDERR, "Invalid {$description} in version.php.\n");
        exit(1);
    }
}
if (preg_match('/\$plugin->release\s*=\s*\'([^\']+)\';/', $versioncontents, $matches) !== 1) {
    fwrite(STDERR, "Unable to read the release version.\n");
    exit(1);
}
$actualrelease = $matches[1];
$expectedrelease = $argv[1] ?? $actualrelease;
if (!preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/D', $expectedrelease)) {
    fwrite(STDERR, "Expected a semantic release version.\n");
    exit(1);
}
if (!hash_equals($expectedrelease, $actualrelease)) {
    fwrite(STDERR, "Release version does not match version.php.\n");
    exit(1);
}
if (preg_match('/-rc(?:[.-]|\d|$)/i', $actualrelease) === 1) {
    $expectedmaturity = 'MATURITY_RC';
} elseif (str_contains($actualrelease, '-')) {
    $expectedmaturity = 'MATURITY_ALPHA';
} else {
    $expectedmaturity = 'MATURITY_STABLE';
}
if (preg_match('/\$plugin->maturity\s*=\s*' . $expectedmaturity . ';/', $versioncontents) !== 1) {
    fwrite(STDERR, "Release maturity does not match the semantic version.\n");
    exit(1);
}

$requiredfiles = [
    'course.php',
    'LICENSE',
    'db/services.php',
    'classes/external/render_preview.php',
    'amd/src/course-presentation.js',
    'amd/src/presentation.js',
    'amd/src/browser-print.js',
    'amd/build/course-presentation.min.js',
    'amd/build/presentation.min.js',
    'amd/build/browser-print.min.js',
    'classes/local/course_presentation.php',
    'README.md',
    'export_pdf.php',
    'classes/local/pdf_exporter.php',
    'CHANGELOG.md',
    'readme_moodle.txt',
    'thirdpartylibs.xml',
    'vendor/prism/LICENSE',
    'vendor/katex/LICENSE',
    'vendor/katex/katex.min.css',
    'vendor/katex/fonts/KaTeX_Main-Regular.woff2',
    'vendor/math/ASCIIMATH-LICENSE',
    'vendor/math/math-render.min.js',
    'vendor/mermaid/LICENSE',
    'vendor/mermaid/mermaid.min.js',
    'vendor/mermaid/mermaid-render.js',
    'amd/build/editor.min.js',
    'amd/src/prism-languages.js',
    'amd/build/prism-languages.min.js.map',
    'amd/build/prism-languages.min.js',
    'amd/build/self-check.min.js',
    'vendor/prism/readme_moodle.txt',
    'amd/build/syntax-highlighter.min.js',
];
foreach ($requiredfiles as $relativepath) {
    if (!is_file($pluginroot . '/' . $relativepath)) {
        fwrite(STDERR, "Required release file is missing: {$relativepath}\n");
        exit(1);
    }
}

// Prism's generated copies are third-party code too, including the embedded map source.
$declarations = simplexml_load_file($pluginroot . '/thirdpartylibs.xml');
if ($declarations === false) {
    fwrite(STDERR, "Unable to parse third-party declarations.\n");
    exit(1);
}
$prismcopies = [
    'vendor/prism',
    'amd/src/prism-languages.js',
    'amd/build/prism-languages.min.js',
    'amd/build/prism-languages.min.js.map',
];
foreach ($prismcopies as $location) {
    $matches = $declarations->xpath("/libraries/library[location='{$location}']");
    if (count($matches) !== 1 || (string)$matches[0]->license !== 'MIT' || (string)$matches[0]->version !== '1.29.0') {
        fwrite(STDERR, "Missing or incorrect Prism declaration: {$location}\n");
        exit(1);
    }
}
$prismsource = file_get_contents($pluginroot . '/amd/src/prism-languages.js');
$prismbuild = file_get_contents($pluginroot . '/amd/build/prism-languages.min.js');
$prismmap = json_decode(file_get_contents($pluginroot . '/amd/build/prism-languages.min.js.map'), true);
if (!str_contains($prismsource, 'Copyright (c) 2012 Lea Verou')
        || !str_contains($prismsource, 'Permission is hereby granted, free of charge')
        || !str_contains($prismbuild, 'Permission is hereby granted, free of charge')
        || !in_array($prismsource, $prismmap['sourcesContent'] ?? [], true)) {
    fwrite(STDERR, "Prism attribution is missing or its source map is stale.\n");
    exit(1);
}

$requiredhashes = [
    'vendor/katex/katex.min.css' => '5bc44ab327592b75fcf2d412a1b396ebf20203bfe826a1966fb8ab03f8b08bb4',
    'vendor/math/math-render.min.js' => '4d7aa10d349ebfe7fbba865ae262f26c00d0dc511b2ed13135b6986f7f0e4da0',
    'vendor/mermaid/mermaid.min.js' => 'c8db409be2b0c005779b367f9e948e9247b9148b8a8c82a15d787481bb33965d',
    'vendor/mermaid/mermaid-render.js' => '402bedf2b2d01f369c56dc13f29a9547a129a91e9cce4c0bd990322b2f6e2a3b',
];
foreach ($requiredhashes as $relativepath => $expectedhash) {
    $actualhash = hash_file('sha256', $pluginroot . '/' . $relativepath);
    if ($actualhash === false || !hash_equals($expectedhash, $actualhash)) {
        fwrite(STDERR, "Third-party browser asset hash mismatch: {$relativepath}\n");
        exit(1);
    }
}

$zippath = $argv[2] ?? $repositoryroot . '/build/mod_lessonmark.zip';
if (is_file($zippath)) {
    if (!class_exists(ZipArchive::class)) {
        fwrite(STDERR, "The ZIP extension is required to inspect the release artifact.\n");
        exit(1);
    }
    $zip = new ZipArchive();
    if ($zip->open($zippath) !== true) {
        fwrite(STDERR, "Unable to open the release ZIP.\n");
        exit(1);
    }
    $entries = [];
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $entry = $zip->getNameIndex($index);
        if ($entry === false) {
            $zip->close();
            fwrite(STDERR, "Unable to inspect a release ZIP entry.\n");
            exit(1);
        }
        if (!str_starts_with($entry, 'lessonmark/')
                || str_contains($entry, '../')
                || preg_match('#(^|/)(?:\.git|node_modules|vendor/bin)(?:/|$)#', $entry) === 1) {
            $zip->close();
            fwrite(STDERR, "Unsafe or development-only ZIP entry: {$entry}\n");
            exit(1);
        }
        $entries[$entry] = true;
    }
    $zip->close();
    foreach ($requiredfiles as $relativepath) {
        if (!isset($entries['lessonmark/' . $relativepath])) {
            fwrite(STDERR, "Required file is absent from the release ZIP: {$relativepath}\n");
            exit(1);
        }
    }
    $zip = new ZipArchive();
    $zip->open($zippath);
    foreach (array_merge(array_slice($prismcopies, 1), ['thirdpartylibs.xml', 'vendor/prism/LICENSE']) as $relativepath) {
        $contents = $zip->getFromName('lessonmark/' . $relativepath);
        if ($contents === false || $contents !== file_get_contents($pluginroot . '/' . $relativepath)) {
            $zip->close();
            fwrite(STDERR, "Prism declaration or attribution differs in release ZIP: {$relativepath}\n");
            exit(1);
        }
    }
    foreach ($requiredhashes as $relativepath => $expectedhash) {
        $contents = $zip->getFromName('lessonmark/' . $relativepath);
        if ($contents === false || !hash_equals($expectedhash, hash('sha256', $contents))) {
            $zip->close();
            fwrite(STDERR, "Third-party asset hash mismatch in release ZIP: {$relativepath}\n");
            exit(1);
        }
    }
    $zip->close();
}

echo "Verified mod_lessonmark {$actualrelease} release metadata and package contents.\n";
