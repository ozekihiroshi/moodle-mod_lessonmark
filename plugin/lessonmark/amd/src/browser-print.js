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
 * Temporary print preparation; never stores learner responses or disclosure state.
 *
 * @module     mod_lessonmark/browser-print
 * @copyright  2026 Hiroshi Ozeki
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Register print lifecycle handlers.
 */
export const init = () => {
    let answers = null;
    let groups = [];
    const prepare = () => {
        if (answers !== null) {
            return;
        }
        answers = Array.from(document.querySelectorAll('.mod_lessonmark-selfcheck__answer'))
            .map(element => ({element, open: element.open}));
        answers.forEach(({element}) => {
            element.open = true;
        });
        document.querySelectorAll('.mod_lessonmark-content p').forEach(paragraph => {
            if (paragraph.textContent.trim() || paragraph.querySelectorAll('img').length !== 1) {
                return;
            }
            const nodes = [paragraph];
            let previous = paragraph.previousElementSibling;
            while (previous && /^H[1-6]$/.test(previous.tagName)) {
                nodes.unshift(previous);
                previous = previous.previousElementSibling;
            }
            if (nodes.length === 1) {
                return;
            }
            const group = document.createElement('div');
            group.className = 'mod_lessonmark-print-figure';
            nodes[0].before(group);
            nodes.forEach(node => group.appendChild(node));
            groups.push(group);
        });
    };
    const restore = () => {
        if (answers === null) {
            return;
        }
        answers.forEach(({element, open}) => {
            element.open = open;
        });
        groups.forEach(group => group.replaceWith(...group.childNodes));
        answers = null;
        groups = [];
    };
    window.addEventListener('beforeprint', prepare);
    window.addEventListener('afterprint', restore);
};
