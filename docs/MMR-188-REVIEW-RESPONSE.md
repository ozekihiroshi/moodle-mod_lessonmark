# MMR-188 review response

Prepared for the Moodle plugins directory review of LessonMark (`mod_lessonmark`).

## Changes by reported issue

| Issue | Resolution | Main evidence |
| --- | --- | --- |
| [#1 Incorrect repository name](https://github.com/ozekihiroshi/moodle-mod_lessonmark/issues/1) | The GitHub repository was renamed to `moodle-mod_lessonmark`, and the source, installation and Marketplace documentation use the canonical URL. | `README.md`, `plugin/lessonmark/README.md`, `plugin/lessonmark/readme_moodle.txt`, `docs/INSTALLATION.md`, `docs/MARKETPLACE_LISTING.md` |
| [#2 Missing plugin license file](https://github.com/ozekihiroshi/moodle-mod_lessonmark/issues/2) | Added the complete GNU GPL v3 text as `LICENSE` at the root of the installable plugin. | `plugin/lessonmark/LICENSE` and release-package verification |
| [#3 Update Ajax implementation to External Services](https://github.com/ozekihiroshi/moodle-mod_lessonmark/issues/3) | Replaced the direct `preview.php` AJAX endpoint with an AJAX-enabled Moodle External Service. The editor calls it through `core/ajax`; context, login and capability checks remain server-side. | `plugin/lessonmark/classes/external/render_preview.php`, `plugin/lessonmark/db/services.php`, `plugin/lessonmark/amd/src/editor.js`; obsolete `plugin/lessonmark/preview.php` removed |
| [#4 Missing File Boilerplate Headers](https://github.com/ozekihiroshi/moodle-mod_lessonmark/issues/4) | Migrated first-party JavaScript to Moodle AMD source files with the standard GPL boilerplate, copyright and licence tags. An audit of first-party PHP and JS files found no missing header fields. Bundled third-party files retain their upstream licences and are declared in `thirdpartylibs.xml`. | `plugin/lessonmark/amd/src/*.js`, `plugin/lessonmark/thirdpartylibs.xml` |
| [#5 Update JS implementation to ES6 JavaScript Modules](https://github.com/ozekihiroshi/moodle-mod_lessonmark/issues/5) | Replaced the three root-level legacy scripts with Moodle AMD ES6 modules and load them through `js_call_amd()`. Build files were generated with Moodle 5.2 Grunt. | `plugin/lessonmark/amd/src/presentation.js`, `course-presentation.js`, `browser-print.js`, plus `course.php` and `view.php` |

The browser migration also exposed a timing race when a presentation iframe was
replaced. The child now announces that its AMD listener is ready before the
parent sends the connection command. The cross-lesson Behat scenario verifies
initial connection, next-lesson navigation and return to the previous lesson.

## Verification

Run against Moodle 5.2.3 (Build 20260914), PHP 8.3.6 and MariaDB 11.8.8:

- PHP lint: 47/47 files passed.
- Moodle CodeSniffer: 47/47 files passed with zero warnings.
- Moodle PHPDoc checker, plugin validation and upgrade savepoint checks passed.
- Moodle Grunt: AMD ESLint/Rollup, Gherkin lint and CSS Stylelint passed with zero plugin warnings.
- PHPUnit: 43 tests and 171 assertions passed.
- Behat with Chrome and accessibility checks: 4 scenarios and 85 steps passed.
- Local installed-site integration: an administrator could render an unsaved preview; a student without activity-creation permission was rejected.
- Node controller regressions passed for presentation, continuous course presentation and browser print behavior.

## Reviewer reply draft

Hello,

Thank you for the detailed review. I have addressed the five reported issues in
LessonMark 0.3.0-alpha2:

- renamed the repository to `moodle-mod_lessonmark` and updated the documented URLs;
- added the complete GPL v3 `LICENSE` file to the plugin root;
- replaced the direct preview AJAX endpoint with an AJAX-enabled Moodle External Service called through `core/ajax`;
- completed the Moodle boilerplate headers for first-party PHP and JavaScript files; and
- migrated the legacy presentation and print scripts to Moodle AMD ES6 modules generated with Moodle Grunt.

The Moodle 5.2 checks pass: PHP lint, CodeSniffer with zero warnings, PHPDoc,
validation, savepoints, Grunt, 43 PHPUnit tests (171 assertions), and 4 Chrome
Behat scenarios (85 steps), including accessibility checks.

The updated release package is attached to the resubmission. Thank you for
reviewing it again.

## Marketplace resubmission

On 15 September 2026, `mod_lessonmark-0.3.0-alpha2.zip` was registered as
version `0.3.0-alpha2` (`2026091501`) for Moodle 5.2 in MMR-188. The Marketplace
description was updated to the canonical repository URLs and to describe
continuous course presentation accurately. The Marketplace automated test was
still in progress at the time of this record.

## Checks applied to the dual-learning plugins

The same five review points were checked against the current prototypes:

- their planned repository/package names already follow the component pattern:
  `moodle-theme_duallearning` and `moodle-format_duallearning`;
- both plugin roots contain a complete `LICENSE` file;
- the first-party PHP/JS header audit found no missing boilerplate, copyright or licence fields; and
- neither prototype currently has a direct AJAX endpoint or JavaScript that needs an AMD migration.

These checks should remain release gates when the prototypes gain AJAX or
JavaScript behavior.
