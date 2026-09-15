LessonMark 0.3.0-alpha2
======================

LessonMark is a Moodle activity module for authoring, previewing, and publishing
teaching material while preserving Markdown as the source of truth.

Component: mod_lessonmark
Moodle: 5.2
PHP: 8.3 or 8.4
Maturity: alpha
License: GNU GPL v3 or later

The 0.3 alpha adds continuous course presentation while retaining individual
lesson presentation. Explicit <!-- slide --> boundaries also start new pages
in browser printing and saved PDF export. The 0.2 formula, diagram, same-page
practice, and PDF features remain available. All runtime assets and third-party
licenses are in the plugin package. Moodle servers do not need Node.js, a CDN,
or an external rendering service.

Install the ZIP through Moodle's plugin installer, or extract it so that
version.php is located at <moodle-root>/mod/lessonmark/version.php. Complete the
normal Moodle database upgrade. Composer and Node.js are not required on the
server.

Back up the database and moodledata before upgrading. Uninstalling removes the
activities and their Moodle-managed files.

Documentation, issue tracker, and security policy:
https://github.com/ozekihiroshi/moodle-mod_lessonmark
https://github.com/ozekihiroshi/moodle-mod_lessonmark/issues
https://github.com/ozekihiroshi/moodle-mod_lessonmark/security/policy
