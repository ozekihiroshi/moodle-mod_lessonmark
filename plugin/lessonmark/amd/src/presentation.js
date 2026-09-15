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
 * Progressive enhancement for classroom presentation.
 *
 * @module     mod_lessonmark/presentation
 * @copyright  2026 Hiroshi Ozeki
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialise a LessonMark presentation.
 */
export const init = () => {
        const root = document.querySelector('.mod_lessonmark-presentation');
        if (!root) {
            return;
        }
        const slides = Array.from(root.querySelectorAll('.mod_lessonmark-slide'));
        const previous = root.querySelector('[data-presentation-action="previous"]');
        const next = root.querySelector('[data-presentation-action="next"]');
        const fullscreen = root.querySelector('[data-presentation-action="fullscreen"]');
        const status = root.querySelector('[data-presentation-status]');
        let current = 0;
        let connected = false;
        let token = 0;
        const notify = (action, direction = 0) => {
            if (connected) {
                window.parent.postMessage({type: 'lessonmark-slide', action, direction, token,
                    cmid: root.dataset.cmid, position: current, count: slides.length}, window.location.origin);
            }
        };
        const show = (index, focus = false) => {
            current = Math.max(0, Math.min(index, slides.length - 1));
            slides.forEach((slide, i) => {
                slide.hidden = i !== current;
            });
            previous.disabled = current === 0;
            next.disabled = current === slides.length - 1;
            status.textContent = `${current + 1} / ${slides.length}`;
            notify('state');
            if (focus) {
                slides[current].focus();
            }
        };
        const move = (direction) => {
            if (connected && (current + direction < 0 || current + direction >= slides.length)) {
                notify('boundary', direction);
            } else {
                show(current + direction, true);
            }
        };
        previous.addEventListener('click', () => move(-1));
        next.addEventListener('click', () => move(1));
        if (typeof window !== 'undefined' && window.parent !== window) {
            window.addEventListener('message', event => {
                const message = event.data;
                if (event.origin !== window.location.origin || event.source !== window.parent ||
                        !message || message.type !== 'lessonmark-course') {
                    return;
                }
                if (message.action === 'connect' || message.action === 'connect-last') {
                    connected = true;
                    token = message.token;
                    root.classList.add('mod_lessonmark-presentation-framed');
                    root.querySelector('.mod_lessonmark-presentation-controls').hidden = true;
                    show(message.action === 'connect-last' ? slides.length - 1 : 0, true);
                } else if (connected && message.token === token && message.action === 'focus') {
                    slides[current].focus({preventScroll: true});
                } else if (connected && message.token === token &&
                        (message.action === 'previous' || message.action === 'next')) {
                    move(message.action === 'previous' ? -1 : 1);
                }
            });
            window.parent.postMessage({
                type: 'lessonmark-slide',
                action: 'ready',
                cmid: root.dataset.cmid,
            }, window.location.origin);
        }
        root.addEventListener('keydown', event => {
            if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey ||
                    event.target.closest('input, textarea, select, button, a, [contenteditable="true"]')) {
                return;
            }
            const destinations = {ArrowLeft: current - 1, ArrowRight: current + 1, Home: 0, End: slides.length - 1};
            if (Object.prototype.hasOwnProperty.call(destinations, event.key)) {
                event.preventDefault();
                if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                    move(event.key === 'ArrowLeft' ? -1 : 1);
                } else {
                    show(destinations[event.key], true);
                }
            }
        });
        fullscreen.hidden = !root.requestFullscreen;
        fullscreen.addEventListener('click', async() => {
            try {
                if (document.fullscreenElement) {
                    await document.exitFullscreen();
                } else {
                    await root.requestFullscreen();
                }
            } catch (error) {
                // The ordinary presentation stays usable if fullscreen is denied.
                fullscreen.disabled = true;
            } finally {
                slides[current].focus({preventScroll: true});
            }
        });
        // Mermaid uses its own off-screen measurement; do not rerender author source here.
        show(0);
        slides[0].focus({preventScroll: true});
};
