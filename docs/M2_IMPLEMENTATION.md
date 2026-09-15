# M2 implementation result

## Status

M2 is complete. LessonMark now provides a dedicated Markdown source editor
with a save-free preview that uses the same renderer as the student view.

## Scope completed

- plain Markdown textarea; no HTML WYSIWYG conversion
- two-pane source and preview layout on desktop
- Edit and Preview tabs on narrow screens
- 400 ms debounced preview and an explicit refresh action
- stale response suppression when requests complete out of order
- Moodle core unsaved-form change detection
- one preview route for both existing activities and unsaved new activities
- server-side POST, sesskey, capability, context, UTF-8, and 512 KiB checks
- safe JSON response containing rendered HTML, TOC data, and diagnostics only
- Moodle Grunt-generated AMD module and source map
- English and Japanese editor status text

## Rendering and authorisation

The Preview External Service and `view.php` both instantiate
`moodle_markdown_renderer`; there is no client-side Markdown renderer and no
second rendering pipeline. Existing activities require
`mod/lessonmark:edit` in module context. A new, not-yet-saved activity uses
course context and requires `mod/lessonmark:addinstance`.

The endpoint does not write draft data. The Moodle form remains the only
save path, and Markdown remains the stored source of truth.

## Verification

The final Moodle 5.2 / PHP 8.3 / MariaDB 11.8 local CI run completed with:

- PHP lint: 19/19 files
- Moodle CodeSniffer: no errors or warnings
- Moodle PHPDoc Checker: passed
- plugin validation and savepoint checks: passed
- ESLint, Rollup AMD build, and Stylelint: passed
- PHPUnit: 7 tests, 15 assertions

The `moodle-rescue` UI upload environment was updated with the CI-built
files. Authenticated HTTP smoke coverage verified both preview contexts,
Markdown rendering, raw-script neutralisation, unchanged stored source after
preview, generated AMD delivery, and anonymous/invalid-sesskey rejection.

Visual automation in the Codex in-app browser remains unavailable because
its Windows sandbox cannot apply the required deny-read ACLs. This is a host
tooling limitation; the Moodle form, endpoint, asset, and responsive CSS
contracts were tested without relying on a source-mounted Moodle instance.

## Next milestone

M3 adds the formal teaching-material dialect and presentation: heading IDs,
automatic TOC, fenced-code presentation, tables, NOTE/TIP/WARNING callouts,
and teaching-focused typography.
