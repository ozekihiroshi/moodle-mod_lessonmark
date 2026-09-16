// Unit tests for the presentation controller; no browser or Moodle data needed.
// Copyright 2026 Hiroshi Ozeki. GNU GPL v3 or later.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

async function main() {
    const button = () => ({handlers: {}, addEventListener(type, fn) { this.handlers[type] = fn; }});
    const previous = button();
    const next = button();
    const fullscreen = button();
    fullscreen.dataset = {enterLabel: 'Fullscreen', exitLabel: 'Exit fullscreen'};
    const status = {};
    let focused = null;
    const slides = Array.from({length: 3}, (_, index) => ({hidden: false, focus() { focused = index; }}));
    const controls = {previous, next, fullscreen};
    const root = {
        handlers: {},
        querySelectorAll() { return slides; },
        querySelector(selector) {
            if (selector === '[data-presentation-status]') { return status; }
            return controls[selector.match(/"(.*?)"/)[1]];
        },
        addEventListener(type, fn) { this.handlers[type] = fn; },
        async requestFullscreen() { throw new Error('Not allowed'); },
    };
    const document = {readyState: 'complete', handlers: {}, querySelector() { return root; },
        addEventListener(type, fn) { this.handlers[type] = fn; }};
    const source = fs.readFileSync(
        path.join(__dirname, '../plugin/lessonmark/amd/src/presentation.js'),
        'utf8'
    ).replace('export const init =', 'const init =') + '\ninit();';
    vm.runInNewContext(source, {document});
    assert.equal(status.textContent, '1 / 3');
    assert.equal(previous.disabled, true);
    assert.deepEqual(slides.map(s => s.hidden), [false, true, true]);
    assert.equal(fullscreen.textContent, 'Fullscreen');
    next.handlers.click();
    assert.equal(status.textContent, '2 / 3');
    const key = (name, editing = false) => {
        let prevented = false;
        root.handlers.keydown({key: name, target: {closest() { return editing; }},
            preventDefault() { prevented = true; }});
        return prevented;
    };
    assert.equal(key('ArrowRight', true), false);
    assert.equal(status.textContent, '2 / 3');
    assert.equal(key('End'), true);
    assert.equal(status.textContent, '3 / 3');
    assert.equal(next.disabled, true);
    next.handlers.click();
    assert.equal(status.textContent, '3 / 3');
    key('Home');
    assert.equal(status.textContent, '1 / 3');
    root.requestFullscreen = async() => { document.fullscreenElement = root; };
    focused = 'button';
    await fullscreen.handlers.click();
    document.handlers.fullscreenchange();
    assert.equal(fullscreen.textContent, 'Exit fullscreen');
    assert.equal(focused, 0);
    key('ArrowRight');
    assert.equal(status.textContent, '2 / 3');
    document.exitFullscreen = async() => { document.fullscreenElement = null; };
    focused = 'button';
    await fullscreen.handlers.click();
    document.handlers.fullscreenchange();
    assert.equal(fullscreen.textContent, 'Fullscreen');
    assert.equal(focused, 1);
    root.requestFullscreen = async() => { throw Error('Denied'); };
    key('Home');
    await fullscreen.handlers.click();
    assert.equal(fullscreen.disabled, true);
    assert.equal(focused, 0);
    next.handlers.click();
    assert.equal(status.textContent, '2 / 3');
    console.log('Presentation controller: navigation, bounds, input keys, fullscreen denial passed.');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
