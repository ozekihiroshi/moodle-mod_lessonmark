# Change log

## Unreleased

- Fix continuous presentation order across course subsections, including after backup/restore.
- Declare every bundled Prism AMD copy and source map as third-party MIT code,
  and preserve upstream attribution in the source generator (review issue #8).

## 0.3.0-alpha4 - 2026-09-18

- Use neutral sample-answer labels in answer reveal controls and PDF exports (English and Japanese).

## 0.3.0-alpha3 - 2026-09-16

- Clarified the individual and continuous presentation launch labels in English
  and Japanese, and made the fullscreen button indicate whether it enters or
  exits fullscreen.
- Updated browser acceptance tests for the new labels.
- Rebuilt AMD assets using Moodle's locked dependencies and documented the
  reproducible build procedure. Standard Moodle Plugin CI checks are retained.

## 0.3.0-alpha2 - 2026-09-15

- Replaced the dedicated Preview AJAX endpoint with a Moodle External Service
  called through `core/ajax`, retaining context and capability validation.
- Migrated presentation, continuous course presentation and print preparation
  to Moodle AMD ES6 modules.
- Added a child-ready handshake to continuous course presentation so iframe
  navigation does not depend on AMD initialisation timing.
- Added the complete GPL v3 licence to the plugin root and completed Moodle
  boilerplate headers for first-party JavaScript sources.
- Prepared the repository and release metadata for the Marketplace review fixes.

## 0.3.0-alpha1 - 2026-09-12

- Added continuous course presentation through accessible, visible, course-listed
  LessonMark activities, including explicit slides inside each activity.
- Added lesson selection, position labels and a persistent fullscreen shell.
- Restore slide focus after fullscreen requests so arrow navigation works immediately.
- Refined screen typography, tables, code and focus indicators without changing Markdown source.
- Use explicit `<!-- slide -->` boundaries as page breaks in browser printing
  and saved-content PDF export while retaining continuous on-screen reading.
- Added playlist access/completion tests and cross-lesson browser regression coverage.
- This alpha is intended for evaluation before a stable 0.3 release.

## 0.2.0 - 2026-09-09

- Hide Moodle's floating help/footer controls when printing a LessonMark page,
  preventing the question-mark button from covering diagrams in browser PDFs.
- Added locally bundled LaTeX, AsciiMath, and Mermaid rendering in Preview,
  saved student display, and classroom presentation, with readable source fallback.
- Added same-page ungraded RESPONSE, CHOICE, and ANSWER self-check blocks.
- Added saved-content PDF export and fixed Japanese text in code blocks.
  PDF retains formula and diagram source rather than browser-rendered graphics.
- Added classroom presentation with explicit slide markers, keyboard navigation,
  fullscreen request, and return to the teaching document.
- Verified alpha2-to-RC2 upgrades retaining existing source, identifiers and images,
  container-recreation persistence, and isolated full-backup recovery.
- Confirmed an AWS UI upgrade and cross-site course restore with Markdown and
  diagram display preserved.

## 0.2.0-rc2 - 2026-09-08

- Added classroom presentation of saved Markdown with explicit slide markers,
  previous/next and keyboard navigation, page count, fullscreen request and return.
- Presentation uses existing access controls and local formula/diagram renderers.
- Normal display, Preview and PDF omit structural slide markers outside code.
- Fixed Japanese code and Mermaid source text in saved-content PDFs.
- PHP 8.3/8.4 and Chrome Behat passed for the presentation implementation.
- Upgrade, rollback and manual release acceptance remain release gates.

## 0.2.0-rc1 - 2026-09-07

- Added a pinned, self-contained Node build for the local KaTeX, AsciiMath,
  and Mermaid browser assets and verified that it reproduces committed files.
- Added exact release-package checks for third-party assets and licenses.
- Tightened conditional browser-asset detection and added malformed-source and
  PDF source-fallback regression coverage.
- Extended the supported PHP matrix, browser acceptance, reproducible ZIP, and
  installation lifecycle release gates for the 0.2 feature set.
- Fixed the first author Preview so Mermaid rendering waits briefly for its
  conditionally loaded browser runtime and adapter instead of leaving source visible.

- Isolated Mermaid's bundled UMD dependencies from Moodle RequireJS to prevent
  anonymous AMD registration from interrupting browser initialization.
## 0.2.0-alpha4 - 2026-09-07

- Added locally bundled Mermaid diagram rendering to author Preview and
  student display while retaining fenced source as canonical Markdown.
- Added strict Mermaid security, disabled HTML labels, bounded text and edge
  processing, responsive output, and source-visible failure behavior.
- Loaded the Mermaid payload conditionally on student pages and retained
  readable diagram source in saved-content PDF export.

## 0.2.0-alpha3 - 2026-09-07

- Added locally bundled LaTeX and AsciiMath rendering in author Preview and
  student display while retaining Markdown as the source of truth.
- Added accessible HTML and MathML output, keyboard-operable LaTeX copy
  controls, bounded untrusted rendering, and readable invalid-source fallback.
- Kept saved-content PDF export deterministic by retaining formula source when
  browser-derived rendering is unavailable.

## 0.2.0-alpha2 - 2026-08-30

- Added access-controlled PDF download for the content saved in Moodle.
- Embedded Moodle-managed teaching images, expanded ANSWER disclosures, and
  converted browser-local working answers to blank printable response areas.
- Kept remote image fetching and browser-local answer export outside the PDF
  boundary.
## 0.2.0-alpha1 - 2026-08-30

- Added ungraded RESPONSE and CHOICE working-answer blocks that retain learner
  drafts only in the current browser.
- Added an ANSWER disclosure block for keeping official answers and commentary
  immediately below each practice prompt without page navigation.
- Kept Moodle Quiz, grading, submissions, and teacher-visible attempts outside
  the LessonMark self-check feature.

## 0.1.0 - 2026-08-29

- Published the first stable release for Moodle 5.2 on PHP 8.3 and 8.4.
- Completed Markdown authoring, shared safe preview/student rendering, teaching
  presentation, Moodle File API images, import/export, backup/restore, and
  course duplicate support.
- Completed keyboard and screen-reader authoring behavior, security and privacy
  regression coverage, upload lifecycle testing, reproducible packaging, and
  supported-matrix CI.
- Added the GPL license, third-party reconstruction instructions, public
  support and security routes, publication audit, and Marketplace copy.

## 0.1.0-rc4 - 2026-08-29

- Kept the Markdown source editor visible while teachers scroll through a long
  Preview on desktop layouts.

## 0.1.0-rc3 - 2026-08-29

- Kept the Markdown source field aligned to the top of its Preview column when
  rendered content is taller.

## 0.1.0-rc2 - 2026-08-29

- Removed the duplicate activity name from the student document body while
  retaining Moodle's standard activity header.
- Expanded the desktop Markdown authoring surface, enabled non-destructive soft
  wrapping, and reserved space above Moodle's fixed form actions.
- Allowed compact tables to fit their container without an unnecessary
  horizontal scrollbar while retaining overflow for genuinely wide content.

## 0.1.0-rc1 - 2026-08-28

- Added Markdown-first activity creation, editing, save-free preview, and
  student display through one rendering boundary.
- Added stable heading links, automatic contents, NOTE/TIP/WARNING callouts,
  syntax-highlighted code, responsive tables, and teaching typography.
- Added Moodle File API image management and author diagnostics.
- Added validated UTF-8 `.md` import and protected source export.
- Added backup, restore, course duplicate, and internal-link remapping.
- Added keyboard-operable responsive editor tabs, browser accessibility tests,
  security and privacy regression tests, expanded CI, and release verification.
