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
 * Persistent presentation shell; loads only the selected lesson.
 *
 * @module     mod_lessonmark/course-presentation
 * @copyright  2026 Hiroshi Ozeki
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialise the continuous course presentation shell.
 */
export const init = () => {
        const root = document.querySelector('.mod_lessonmark-course-presentation');
        if (!root) {
            return;
        }
        const frame = root.querySelector('iframe');
        const outline = root.querySelector('select');
        const status = root.querySelector('[data-course-status]');
        const previous = root.querySelector('[data-course-action="previous"]');
        const next = root.querySelector('[data-course-action="next"]');
        const fullscreen = root.querySelector('[data-course-action="fullscreen"]');
        const origin = window.location.origin;
        let ready = false;
        let position = 0;
        let count = 1;
        let last = false;
        let timer;
        let token = 0;
        const send = (action) => frame.contentWindow.postMessage({
            type: 'lessonmark-course', action, token,
        }, origin);
        const loading = () => {
            ready = false;
            previous.disabled = true;
            next.disabled = true;
            status.textContent = root.dataset.loading;
            clearTimeout(timer);
            timer = setTimeout(() => {
                if (!ready) {
                    status.textContent = root.dataset.unavailable;
                }
            }, 15000);
        };
        const open = (index, atEnd = false) => {
            if (index < 0 || index >= outline.options.length) {
                return;
            }
            outline.selectedIndex = index;
            last = atEnd;
            token++;
            loading();
            frame.src = outline.options[index].dataset.url;
        };
        const move = (direction) => {
            if (!ready) {
                return;
            }
            if (direction < 0 && position === 0) {
                open(outline.selectedIndex - 1, true);
            } else if (direction > 0 && position === count - 1) {
                open(outline.selectedIndex + 1);
            } else {
                send(direction < 0 ? 'previous' : 'next');
            }
        };
        window.addEventListener('message', event => {
            const message = event.data;
            if (event.origin !== origin || event.source !== frame.contentWindow ||
                    !message || message.type !== 'lessonmark-slide' || String(message.cmid) !== outline.value) {
                return;
            }
            if (message.action === 'ready') {
                send(last ? 'connect-last' : 'connect');
                return;
            }
            if (message.token !== token) {
                return;
            }
            if (message.action === 'boundary' && (message.direction === -1 || message.direction === 1)) {
                move(message.direction);
                return;
            }
            if (message.action !== 'state' || !Number.isInteger(message.position) || !Number.isInteger(message.count) ||
                    message.count < 1 || message.position < 0 || message.position >= message.count) {
                return;
            }
            ready = true;
            clearTimeout(timer);
            position = message.position;
            count = message.count;
            previous.disabled = outline.selectedIndex === 0 && position === 0;
            next.disabled = outline.selectedIndex === outline.options.length - 1 && position === count - 1;
            status.textContent = root.dataset.position.replace('{lesson}', outline.selectedIndex + 1)
                .replace('{lessons}', outline.options.length).replace('{slide}', position + 1).replace('{slides}', count);
        });
        frame.addEventListener('load', () => {
            // An error/login page never completes this handshake: do not silently skip it.
            loading();
            send(last ? 'connect-last' : 'connect');
        });
        outline.addEventListener('change', () => open(outline.selectedIndex));
        previous.addEventListener('click', () => move(-1));
        next.addEventListener('click', () => move(1));
        root.addEventListener('keydown', event => {
            if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey ||
                    event.target.closest('input, textarea, select, button, a, [contenteditable="true"]')) {
                return;
            }
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                move(event.key === 'ArrowLeft' ? -1 : 1);
            }
        });
        fullscreen.hidden = !root.requestFullscreen;
        const updateFullscreenLabel = () => {
            fullscreen.textContent = document.fullscreenElement === root ?
                fullscreen.dataset.exitLabel : fullscreen.dataset.enterLabel;
        };
        document.addEventListener('fullscreenchange', updateFullscreenLabel);
        updateFullscreenLabel();
        fullscreen.addEventListener('click', async() => {
            try {
                if (document.fullscreenElement) {
                    await document.exitFullscreen();
                } else {
                    await root.requestFullscreen();
                }
            } catch (error) {
                fullscreen.disabled = true;
            } finally {
                // Leave the toolbar button so arrow keys immediately reach the lesson.
                if (ready) {
                    send('focus');
                }
            }
        });
        loading();
        // Also covers a cached child that completed before this listener was installed.
        send('connect');
};
