<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Local integration check for the LessonMark Preview external service.
 *
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';

if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('Local test environment only.');
}

\core\session\manager::set_user(get_admin());
$result = \mod_lessonmark\external\render_preview::execute('# External preview', 0, 23, 0);
if (!str_contains($result['html'], 'External preview')) {
    throw new RuntimeException('The external service did not return rendered preview HTML.');
}
echo "PASS: authorised course preview\n";

$student = $DB->get_record('user', ['id' => 8], '*', MUST_EXIST);
\core\session\manager::set_user($student);
try {
    \mod_lessonmark\external\render_preview::execute('# Rejected preview', 0, 23, 0);
    throw new RuntimeException('A learner without add-instance capability was accepted.');
} catch (required_capability_exception $exception) {
    echo "PASS: unauthorised course preview rejected\n";
}

\core\session\manager::set_user(get_admin());
echo "External Preview checks passed.\n";
