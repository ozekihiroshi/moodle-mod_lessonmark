# LessonMark 0.3.0-alpha5

Moodle 5.2 evaluation release. Build: 2026091900. Maturity: Alpha.

## Changes

- Fix continuous course presentation order when a course uses subsections. Lessons follow their displayed course order, including after backup and restore, while retaining activity visibility and access checks.
- Address review issue #8: declare all bundled Prism AMD copies as third-party code and preserve the upstream MIT attribution in source, compiled JavaScript and source maps.
- Strengthen reproducible-build and package checks for third-party declarations, licence notices and generated assets.

## Compatibility and upgrade

Supports Moodle 5.2. Upgrade the existing mod_lessonmark plugin; this is not a new plugin submission. No database schema change or conversion of saved Markdown is required. This remains an alpha release for evaluation.

## Validation

The functional changes passed Moodle 5.2 CI on PHP 8.3 and 8.4: 45 PHPUnit tests / 184 assertions each; Chrome Behat: 6 scenarios / 141 steps, including accessibility. Exact release-commit checks are linked from the GitHub release.

Review and regression references: #6, #7, #8, #9.