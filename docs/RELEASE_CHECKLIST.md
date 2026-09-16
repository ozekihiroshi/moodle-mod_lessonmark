# Release checklist

## Scope and metadata

- Confirm the product requirements and supported authoring syntax are unchanged.
- Set a unique ten-digit plugin version, semantic release, maturity, Moodle
  requirement, and supported range in `version.php`.
- Update `CHANGELOG.md`, user documentation, and third-party attribution.

## Automated gates

- Run `npm ci`, `npm audit --audit-level=high`, and
  `npm run build:assets`; require no diff in `plugin/lessonmark/vendor`.
- Confirm the release verifier accepts the exact KaTeX, AsciiMath, and Mermaid
  files, licenses, and SHA-256 hashes.
- Run Moodle Plugin CI on PHP 8.3 and PHP 8.4 against Moodle 5.2.
- Generate AMD files in the same Moodle checkout used by CI, installing its
  dependencies with `npm ci` and the committed `npm-shrinkwrap.json` intact.
  Matching only the top-level Grunt/Babel versions is insufficient: different
  transitive dependencies can produce different minified files and source maps.
  Keep `moodle-plugin-ci grunt --max-lint-warnings 0` as the consistency gate.
- Pass PHP lint, Moodle Code Checker, PHPDoc, plugin validation, savepoints,
  Grunt, and PHPUnit.
- Pass the Chrome Behat flow for authoring, preview, publishing, malformed
  browser-rendered source, and automated accessibility checks.
- Treat Moodle `main` results as informational until the corresponding stable
  release becomes a supported target.

## Security, privacy, and accessibility

- Verify raw active HTML and dangerous URL schemes remain inert.
- Verify preview, export, File API delivery, and editing enforce sesskey and
  capabilities as applicable.
- Re-audit the Privacy API declaration whenever user-linked storage,
  preferences, logging, or external data transfer is added.
- Test keyboard-only editor tab operation, focus visibility, labels, semantic
  headings, callouts, code, tables, responsive layout, and screen-reader names.
- Verify valid and malformed LaTeX, AsciiMath, and Mermaid on Preview and
  the student page. A failure must keep readable source rather than empty output.
- Verify formulas expose semantic MathML and diagrams expose an accessible name.
- Verify narrow-screen, touch, supported Moodle theme, and print behavior.
- Confirm formula and diagram assets load only when their fixed rendered
  markers are present and that no CDN or external renderer request occurs.

## Package and lifecycle

- Build twice from the same clean commit and compare the ZIP bytes.
- Run `php scripts/verify-release.php <version> build/mod_lessonmark.zip`.
- Record the commit and SHA-256 digest.
- Install the ZIP through Moodle's plugin upload UI in a non-source-mounted
  environment with developer debugging enabled.
- Test a clean install, upgrade from the preceding version, activity creation,
  preview, student display, import/export, image access, backup/restore, course
  duplicate, and uninstall/reinstall when the release changes storage.
- Include formula and Mermaid source in import/export, backup/restore, and
  duplicate tests; confirm Markdown remains the source of truth.
- Export a saved-content PDF and confirm formulas and diagrams remain readable
  as source when browser rendering is unavailable.
- Confirm no source repository, Composer install, or Node.js build is needed on
  the Moodle server.

## Publication

- Review the Git diff and GitHub Actions result for the exact commit.
- Attach only the verified ZIP and publish its SHA-256 digest.
- Retain test evidence and document any deferred compatibility work.
