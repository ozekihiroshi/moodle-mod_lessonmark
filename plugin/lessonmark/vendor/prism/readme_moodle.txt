PrismJS components bundled by LessonMark
==========================================

Upstream project: https://prismjs.com/
Upstream source: https://github.com/PrismJS/prism
Pinned version: 1.29.0
License: MIT (see LICENSE in this directory)

LessonMark vendors the upstream Bash, JSON, and SQL language components used by
its syntax highlighter. The component files in this directory are unmodified
upstream files:

- components/prism-bash.js
- components/prism-json.js
- components/prism-sql.js

To reconstruct the generated Moodle AMD source:

1. Download the PrismJS 1.29.0 source archive from the upstream repository.
2. Copy the three component files above and the upstream LICENSE into this
   directory.
3. From the LessonMark repository root, run:

       php scripts/build-prism-languages.php

   This writes plugin/lessonmark/amd/src/prism-languages.js with its provenance
   and MIT license header.
4. Use Moodle's normal Grunt AMD build to generate amd/build files, or run the
   repository CI/build process.

No network download, package manager, or build tool is required on a Moodle
production server. The release ZIP includes the generated AMD build.

Third-party declaration coverage
-------------------------------
thirdpartylibs.xml declares vendor/prism, amd/src/prism-languages.js,
amd/build/prism-languages.min.js and amd/build/prism-languages.min.js.map.
The source includes the full upstream MIT notice. The verified Moodle AMD build
preserves this licence comment in the minified file; the source map also retains
the attributed sourcesContent. The distribution includes this directory's MIT LICENSE.

The generator checks the SHA-256 of each pinned component before copying it.
Run php scripts/build-prism-languages.php and verify that regenerating a second
time produces no diff. Build AMD artifacts with npm ci using the Moodle checkout's
npm-shrinkwrap.json, then npx grunt amd --root=public/mod/lessonmark.
For the strict CI check, run bash scripts/check-moodle-assets.sh followed by the
absolute path to the installed Moodle public/mod/lessonmark directory. This runs
Moodle's ignorefiles and Rollup tasks, ESLint with zero warnings on first-party
source, CSS and Gherkin lint, and compares every rebuilt AMD file and source map.
It handles individually declared build files that Plugin CI 4.5.11's Grunt wrapper
otherwise deletes before Moodle reads thirdpartylibs.xml.
Run php scripts/verify-release.php to check all Prism copies are declared and the
source map contains the current source, including the upstream licence notice.
