// Course shell/controller protocol tests, without a Moodle server.
// Copyright 2026 Hiroshi Ozeki. GNU GPL v3 or later.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const read = name => fs.readFileSync(path.join(__dirname, '../plugin/lessonmark/amd/src', name), 'utf8')
    .replace('export const init =', 'const init =') + '\ninit();';
const target = extra => Object.assign({handlers: {}, addEventListener(type, fn) { this.handlers[type] = fn; }}, extra);

async function main() {
    const previous = target({});
    const next = target({});
    const fullscreen = target({dataset: {enterLabel: 'Fullscreen', exitLabel: 'Exit fullscreen'}});
    const status = {};
    const sent = [];
    const frameWindow = {postMessage(message, origin) { sent.push({message, origin}); }};
    const frame = target({contentWindow: frameWindow});
    const outline = target({selectedIndex: 0, options: [11, 12, 13].map(id => ({value: String(id),
        dataset: {url: `https://example.test/view.php?id=${id}&present=1`}})),
        get value() { return this.options[this.selectedIndex].value; }});
    // Object.assign evaluates getters: restore the dynamic select value.
    Object.defineProperty(outline, 'value', {get() { return this.options[this.selectedIndex].value; }});
    const selectors = {'iframe': frame, 'select': outline, '[data-course-status]': status,
        '[data-course-action="previous"]': previous, '[data-course-action="next"]': next,
        '[data-course-action="fullscreen"]': fullscreen};
    const root = target({dataset: {loading: 'Loading', unavailable: 'Unavailable',
        position: '{lesson} / {lessons} · {slide} / {slides}'},
        querySelector: selector => selectors[selector], async requestFullscreen() { throw Error('Denied'); }});
    const document = {readyState: 'complete', handlers: {}, querySelector: () => root,
        addEventListener(type, fn) { this.handlers[type] = fn; }};
    const window = target({location: {origin: 'https://example.test'}});
    let timeout;
    vm.runInNewContext(read('course-presentation.js'), {document, window,
        setTimeout(fn) { timeout = fn; }, clearTimeout() {}});
    const receive = (data, overrides = {}) => window.handlers.message({origin: window.location.origin,
        source: frameWindow, data: {type: 'lessonmark-slide', action: 'state', token: 0, cmid: 11,
            position: 0, count: 2, ...data}, ...overrides});
    assert.equal(previous.disabled, true);
    assert.equal(next.disabled, true);
    receive({}, {origin: 'https://evil.test'});
    assert.equal(status.textContent, 'Loading');
    receive({}, {source: {}});
    assert.equal(status.textContent, 'Loading');
    receive({action: 'ready'});
    assert.equal(sent.at(-1).message.action, 'connect');
    receive({});
    assert.equal(status.textContent, '1 / 3 · 1 / 2');
    assert.equal(next.disabled, false);
    next.handlers.click();
    assert.equal(sent.at(-1).message.action, 'next');
    receive({position: 1});
    next.handlers.click();
    assert.equal(outline.selectedIndex, 1);
    assert.match(frame.src, /id=12/);
    assert.equal(next.disabled, true);
    receive({position: 1}); // Stale previous lesson cannot enable the controls.
    assert.equal(next.disabled, true);
    frame.handlers.load();
    assert.equal(sent.at(-1).message.action, 'connect');
    receive({token: 1, cmid: 12, count: 1});
    previous.handlers.click();
    frame.handlers.load();
    assert.equal(sent.at(-1).message.action, 'connect-last');
    assert.equal(outline.selectedIndex, 0);
    receive({token: 2, cmid: 11, position: 1});
    receive({token: 2, cmid: 11, position: -1});
    assert.equal(status.textContent, '1 / 3 · 2 / 2');
    outline.selectedIndex = 2;
    outline.handlers.change();
    frame.handlers.load();
    receive({token: 3, cmid: 13, position: 0, count: 1});
    assert.equal(next.disabled, true);
    root.requestFullscreen = async() => { document.fullscreenElement = root; };
    await fullscreen.handlers.click();
    document.handlers.fullscreenchange();
    assert.equal(fullscreen.textContent, 'Exit fullscreen');
    assert.equal(sent.at(-1).message.action, 'focus');
    assert.equal(sent.at(-1).message.token, 3);
    document.exitFullscreen = async() => { document.fullscreenElement = null; };
    await fullscreen.handlers.click();
    document.handlers.fullscreenchange();
    assert.equal(fullscreen.textContent, 'Fullscreen');
    assert.equal(sent.at(-1).message.action, 'focus');
    root.requestFullscreen = async() => { throw Error('Denied'); };
    await fullscreen.handlers.click();
    assert.equal(fullscreen.disabled, true);
    assert.equal(sent.at(-1).message.action, 'focus');
    outline.selectedIndex = 1;
    outline.handlers.change();
    timeout();
    assert.equal(status.textContent, 'Unavailable');
    assert.equal(next.disabled, true);

    // Run the real child controller and inspect its handshake and boundary messages.
    const childSent = [];
    const parent = {postMessage(data) { childSent.push(data); }};
    const childWindow = target({parent, location: {origin: window.location.origin}});
    const childControls = {previous: target({}), next: target({}),
        fullscreen: target({dataset: {enterLabel: 'Fullscreen', exitLabel: 'Exit fullscreen'}})};
    const childStatus = {};
    const bar = {};
    let focused = null;
    const slides = Array.from({length: 2}, (_, index) => ({focus() { focused = index; }}));
    const childRoot = target({dataset: {cmid: '11'}, classList: {add() {}}, querySelectorAll: () => slides,
        querySelector(selector) {
            if (selector === '[data-presentation-status]') { return childStatus; }
            if (selector === '.mod_lessonmark-presentation-controls') { return bar; }
            return childControls[selector.match(/"(.*?)"/)[1]];
        }});
    const childDocument = {readyState: 'complete', querySelector: () => childRoot,
        addEventListener() {}};
    vm.runInNewContext(read('presentation.js'), {window: childWindow, document: childDocument});
    assert.equal(childSent.at(-1).action, 'ready');
    assert.equal(childSent.at(-1).cmid, '11');
    const command = (action, origin = window.location.origin) => childWindow.handlers.message({origin, source: parent,
        data: {type: 'lessonmark-course', token: 7, action}});
    const readyMessages = childSent.length;
    command('connect-last', 'https://evil.test');
    assert.equal(childSent.length, readyMessages);
    command('connect-last');
    assert.equal(childStatus.textContent, '2 / 2');
    assert.equal(bar.hidden, true);
    assert.equal(childSent.at(-1).token, 7);
    focused = 'button';
    command('focus', 'https://evil.test');
    assert.equal(focused, 'button');
    command('focus');
    assert.equal(focused, 1);
    assert.equal(childStatus.textContent, '2 / 2');
    command('next');
    assert.equal(childSent.at(-1).action, 'boundary');
    assert.equal(childSent.at(-1).direction, 1);
    command('previous');
    assert.equal(childStatus.textContent, '1 / 2');
    command('previous');
    assert.equal(childSent.at(-1).direction, -1);
    console.log('Course presentation: ordering, boundaries, last-slide return, selection, message validation and failure passed.');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
