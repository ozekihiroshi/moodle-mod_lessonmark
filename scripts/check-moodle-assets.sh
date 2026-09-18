#!/usr/bin/env bash
# Copyright 2026 Hiroshi Ozeki
# License: GNU GPL v3 or later, https://www.gnu.org/copyleft/gpl.html
set -euo pipefail

# Run against the plugin installed in a Moodle checkout with its locked npm dependencies.
pluginroot=$(realpath "${1:?Usage: check-moodle-assets.sh /path/to/moodle/public/mod/lessonmark}")
test -f "$pluginroot/version.php"
test -f "$pluginroot/thirdpartylibs.xml"
test -d "$pluginroot/amd/src"
test -d "$pluginroot/amd/build"
backupdir=$(mktemp -d)
cp -a "$pluginroot/amd/build" "$backupdir/build"
restore() {
    rm -rf -- "$pluginroot/amd/build"
    cp -a "$backupdir/build" "$pluginroot/amd/build"
    rm -rf -- "$backupdir"
}
trap restore EXIT

# Moodle's ignorefiles task stats every declared third-party path before building.
# Plugin CI 4.5.11 deletes amd/build first, so its grunt wrapper cannot handle
# individually declared generated files. Empty placeholders allow ignorefiles to
# inspect those paths, but MUST be rebuilt to pass the byte-for-byte comparison.
find "$pluginroot/amd/build" -mindepth 1 -maxdepth 1 -exec rm -rf -- {} +
: > "$pluginroot/amd/build/prism-languages.min.js"
: > "$pluginroot/amd/build/prism-languages.min.js.map"
cd "$pluginroot"
npx grunt ignorefiles
# Passing the source directory lets ESLint omit declared third-party code without
# the "explicitly requested file is ignored" warning from grunt eslint:amd.
moodleroot=$(realpath "$pluginroot/../../..")
(cd "$moodleroot" && npx eslint public/mod/lessonmark/amd/src --max-warnings=0)
npx grunt rollup gherkinlint stylelint --max-lint-warnings=0
diff -ru "$backupdir/build" "$pluginroot/amd/build"
echo 'All AMD files and source maps were regenerated without differences.'
