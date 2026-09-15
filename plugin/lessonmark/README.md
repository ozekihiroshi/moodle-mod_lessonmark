# LessonMark for Moodle

LessonMark is a Moodle activity module for creating and publishing teaching
material while Markdown remains the source of truth.

The portable plain-text source also supports AI-assisted authoring: teachers
can draft, translate, reorganise, or review a lesson with a chosen tool, inspect
the change, and verify Moodle's actual Preview before publishing. LessonMark
does not itself contact an AI service or send lesson content outside Moodle.

## Features

Development note: 0.3.0-alpha2 adds **Course presentation** and incorporates the
Marketplace review fixes. Start from a visible
LessonMark activity; use Left/Right to navigate slides and lessons in course order.
Use `<!-- slide -->` on its own line to split a lesson. Hidden, stealth and inaccessible
activities and other activity types are not included. Only the current lesson is loaded.
Course-wide printing/export is not provided; use the existing individual lesson view.
This alpha is intended for evaluation before a stable 0.3 release.

- create and edit Markdown inside Moodle without converting the saved source to HTML;
- responsive split Edit/Preview UI with keyboard-operable mobile tabs;
- one-time validated UTF-8 `.md` import, source export, and saved-content PDF download;
- one safe rendering path for preview and student display;
- stable heading links, automatic contents, NOTE/TIP/WARNING callouts,
  highlighted fenced code, responsive tables, and teaching typography;
- ungraded RESPONSE and CHOICE working-answer blocks with browser-local draft
  retention, followed by native ANSWER disclosures on the same page;
- local LaTeX, AsciiMath, and Mermaid rendering with accessible, readable
  source fallback and no CDN dependency;
- Moodle File API images with access control and author diagnostics; and
- backup, restore, course duplicate, and internal-link remapping.

Upload teaching images with the activity file manager and reference them as
`![Alternative text](@@PLUGINFILE@@/image.png)`. Subfolders are supported.
Import creates an unsaved editor change. Markdown export downloads only the
source already saved in Moodle and does not include images. PDF download uses
the saved source, embeds Moodle-managed images, expands answer disclosures,
and leaves browser-local working answers blank.

Raw HTML is not supported authoring syntax. It is neutralised before Moodle's
HTML cleaning boundary rather than executed.

## Requirements

- Moodle 5.2
- PHP 8.3 or 8.4

## Installation

Install the release ZIP through **Site administration > Plugins > Install
plugins**, or extract this directory to `<moodle-root>/mod/lessonmark`. Complete
Moodle's normal database upgrade. The installed server does not need Composer
or Node.js.

Back up the database and `moodledata` before an upgrade. Uninstalling removes
LessonMark records and Moodle-managed activity files, so retain a course backup
or exported source first.

See the source repository for the authoring guide, installation details,
security policy, release checklist, and reproducible build scripts:
<https://github.com/ozekihiroshi/moodle-mod_lessonmark>.

Use <https://github.com/ozekihiroshi/moodle-mod_lessonmark/issues> for reproducible
non-security defects and feature discussions. Security reports must use the
private process in the repository's `SECURITY.md`.

## License

GNU GPL v3 or later. Bundled PrismJS, KaTeX, AsciiMath parser, and Mermaid
assets are MIT licensed. Exact versions and attribution are recorded in
`thirdpartylibs.xml`; corresponding license files are included under `vendor/`.
