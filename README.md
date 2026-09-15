# LessonMark

[![Moodle plugin CI](https://github.com/ozekihiroshi/moodle-mod_lessonmark/actions/workflows/moodle-plugin-ci.yml/badge.svg)](https://github.com/ozekihiroshi/moodle-mod_lessonmark/actions/workflows/moodle-plugin-ci.yml)
[![License: GPL v3 or later](https://img.shields.io/badge/License-GPLv3%2B-blue.svg)](LICENSE)

LessonMark is a Moodle course resource for authoring, previewing, and
publishing teaching material while keeping Markdown as the editable source of
truth.

It is designed for technical and text-rich lessons that should remain easy to
review, translate, compare in Git, generate with authoring tools, and reuse
outside one Moodle database. Teachers can still create and maintain the whole
resource in Moodle without requiring Git, an external editor, Composer, or
Node.js on the server.

The plugin component is `mod_lessonmark`. Version 0.3.0-alpha2 targets Moodle
5.2 on PHP 8.3 and 8.4. See GitHub Releases for published, verified installation
ZIPs. The current 0.3 build is a prerelease for evaluation.

![LessonMark Markdown editor and preview](docs/screenshots/lessonmark-authoring.png)

## What makes LessonMark different

### Markdown remains the source

LessonMark stores the editable lesson as Markdown instead of replacing it with
generated HTML. A teacher can write directly in Moodle, import an existing
UTF-8 `.md` file, preview it before publishing, and export the saved source
again. This keeps the material suitable for review, diff, translation, AI
assistance, and reuse in another publishing workflow.

Preview and student display use the same rendering boundary. Raw active HTML
is neutralised rather than executed, and author diagnostics identify such
problems as unresolved relative images, missing alternative text, and unknown
code languages.

### Practical for AI-assisted authoring

Markdown gives an AI system a compact, structured source that is easier to
generate and revise than Moodle-specific HTML or a sequence of manual editor
operations. A teacher or development team can ask an AI assistant to draft a
lesson, translate it, reorganise headings, create code examples, or review a
change, then inspect the plain-text diff and verify the actual Moodle preview
before publishing.

Because the Markdown remains exportable, the result is not trapped in an AI
conversation or in generated HTML. The same source can be versioned, reviewed
by another person or tool, corrected, and imported again. LessonMark itself
does not call an AI service or send lesson content outside Moodle; authors
choose if and where AI assistance is used.

### A teaching document, not only rendered Markdown

LessonMark adds a small, documented teaching dialect on top of ordinary
Markdown:

- automatic contents and stable links for headings;
- NOTE, TIP, and WARNING callouts;
- highlighted fenced code for common technical languages;
- readable, responsive tables and images; and
- Moodle File API images protected by the activity's context and capabilities.

The same source, settings, and managed images participate in Moodle activity
backup, restore, course duplication, and internal-link remapping.

### Practice and explanation stay on the same page

The 0.2 line adds lightweight self-check blocks for lessons in which reading,
answering, and checking an explanation should form one uninterrupted flow.
A prompt can be followed immediately by a free-text or single-choice working
area and a closed answer disclosure:

```markdown
> [!CHOICE]
> Which control most directly limits repeated password attempts?
>
> - A. Account lockout
> - B. File encryption
> - C. Data compression

> [!ANSWER]
> **Official answer: A**
>
> Account lockout limits repeated authentication attempts after failures.
```

Learners can enter an answer before opening the explanation, without leaving
the lesson page. Their working answer is retained only in the current browser,
scoped to the Moodle user and activity, and can be cleared by the learner. It
does not create an attempt, grade, submission, or teacher-visible response.
Use Moodle Quiz or Assignment when assessment, completion records, attempt
history, or teacher review is required.

### Saved lessons become portable PDFs

**Download saved PDF** creates a reading or distribution copy from the content
already saved in Moodle. The export:

- embeds Moodle-managed teaching images in the PDF;
- expands ANSWER disclosures so the explanation is present in the document;
- converts browser response controls into blank printable working areas;
- starts a new page at each explicit `<!-- slide -->` boundary;
- excludes unsaved edits and browser-local answers; and
- never fetches remote images into the generated file.

The download is capability checked and uses the same saved lesson that Moodle
serves to learners. This makes the PDF useful for review, printing, archival,
and distribution without turning it into a second editable source of truth.

![Published LessonMark teaching resource](docs/screenshots/lessonmark-student-view.png)

## Feature overview

- Markdown-first Moodle activity creation and editing;
- responsive side-by-side Edit/Preview interface with mobile tabs;
- locally rendered LaTeX and beginner-friendly AsciiMath formulas;
- locally rendered Mermaid diagrams with strict security and readable fallback;
- shared sanitised rendering for preview and student display;
- validated `.md` import and capability-protected Markdown export;
- same-page RESPONSE, CHOICE, and ANSWER learning blocks;
- access-controlled PDF export with embedded Moodle-managed images;
- automatic contents, stable heading links, callouts, code highlighting,
  responsive tables, and teaching typography;
- Moodle File API image management and author diagnostics; and
- activity backup/restore, course duplicate, and internal-link remapping.

## Requirements and installation

- Moodle 5.2
- PHP 8.3 or 8.4

Install the release ZIP through **Site administration > Plugins > Install
plugins**, or extract the `lessonmark` directory to Moodle's `mod` plugin
directory and complete Moodle's normal upgrade. The Moodle server does not
need the repository, Composer, or Node.js.

See [Installation and upgrade](docs/INSTALLATION.md) for the full lifecycle
procedure and [GitHub Releases](https://github.com/ozekihiroshi/moodle-mod_lessonmark/releases)
for published packages.

## Repository layout

```text
LessonMark/
├── .github/workflows/     GitHub Actions quality and release gates
├── docs/                  Product, technical, user, and release records
├── plugin/lessonmark/     Installable mod_lessonmark source
├── scripts/               Release, smoke, and local CI runners
└── LessonMark.code-workspace
```

Reusable Moodle Docker environments remain in the separate `moodle-rescue`
development repository. Its UI upload environment contains no LessonMark
source mount and can exercise the real ZIP installation and upgrade lifecycle.

## Development and release workflow

From WSL:

```sh
cd /mnt/d/workspace/LessonMark
scripts/run-ci-local.sh
scripts/build-release.sh
php scripts/verify-release.php
```

The local CI runner creates and removes only its own temporary Docker network,
database container, and PHP test container. It runs the Moodle PHP gates,
Grunt JavaScript/CSS checks, AMD generation consistency, and PHPUnit. Select a
supported PHP version with `LESSONMARK_CI_PHP_VERSION=8.4`. An informational
Moodle development-branch probe can be run with
`LESSONMARK_CI_MOODLE_BRANCH=main`; it is not a production support claim.

The release artifact is written to `build/mod_lessonmark.zip`. The builder
requires a clean Git worktree, archives only committed plugin files, validates
the ZIP layout, and produces byte-identical output for the same commit.

## Release status

Release 0.1.0 established the Markdown authoring, rendering, File API,
backup/restore, accessibility, security, and reproducible packaging base. The
0.2 release candidate adds same-page ungraded self-check blocks, saved-content
PDF, and locally bundled LaTeX, AsciiMath, and Mermaid rendering. Pinned Node
dependencies and build scripts reproduce the committed browser assets without
a CDN. These additions complement Moodle Quiz and Assignment.

GitHub Actions tests Moodle 5.2 on PHP 8.3 and 8.4, including PHP lint,
Moodle Code Checker, PHPDoc, plugin validation, upgrade savepoints, Grunt,
PHPUnit, Chrome Behat acceptance/accessibility checks, and a reproducible
release ZIP.

## Documentation

- [Authoring guide](docs/AUTHORING_GUIDE.md)
- [Installation and upgrade](docs/INSTALLATION.md)
- [Product requirements](docs/PRODUCT_REQUIREMENTS.md)
- [Technical decisions and milestones](docs/TECHNICAL_DECISIONS.md)
- [Release checklist](docs/RELEASE_CHECKLIST.md)
- [Security policy](SECURITY.md)
- [Marketplace listing copy](docs/MARKETPLACE_LISTING.md)
- [Publication audit](docs/PUBLICATION_AUDIT.md)
- [Contributing](CONTRIBUTING.md)
- [Documentation index](docs/README.md)

## Support and license

The repository is published at <https://github.com/ozekihiroshi/moodle-mod_lessonmark>.
Report reproducible defects through
[GitHub Issues](https://github.com/ozekihiroshi/moodle-mod_lessonmark/issues). Report
security vulnerabilities privately as described in
[SECURITY.md](SECURITY.md).

LessonMark is licensed under GNU GPL v3 or later. Bundled PrismJS, KaTeX,
AsciiMath parser, and Mermaid assets are MIT licensed; exact versions,
attribution, and license files are included in the plugin package. Rebuild
instructions are in [thirdparty-src/README.md](thirdparty-src/README.md).
