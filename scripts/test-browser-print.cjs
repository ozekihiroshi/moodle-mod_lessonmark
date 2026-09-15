const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const css = fs.readFileSync(path.join(__dirname, '../plugin/lessonmark/styles.css'), 'utf8');
assert.match(css, /\.mod_lessonmark-print-page-break\s*\{[^}]*break-before:\s*page;/s);
assert.match(css, /\.mod_lessonmark-slide \+ \.mod_lessonmark-slide\s*\{[^}]*break-before:\s*page;/s);

const listeners = {};
const answers = [{open: false}, {open: true}];
const heading = {tagName: 'H3', previousElementSibling: null, before: group => { wrapper = group; }};
const paragraph = {textContent: '', previousElementSibling: heading, querySelectorAll: () => [{}]};
let wrapper;
let restored;
const context = {
    window: {addEventListener: (type, callback) => { listeners[type] = callback; }},
    document: {
        querySelectorAll: selector => selector.includes('selfcheck') ? answers : [paragraph],
        createElement: () => ({childNodes: [], appendChild(node) { this.childNodes.push(node); },
            replaceWith(...nodes) { restored = nodes; }}),
    },
};
const source = fs.readFileSync(
    path.join(__dirname, '../plugin/lessonmark/amd/src/browser-print.js'),
    'utf8'
).replace('export const init =', 'const init =') + '\ninit();';
vm.runInNewContext(source, context);
assert.deepEqual(answers.map(answer => answer.open), [false, true]);
listeners.beforeprint();
assert.deepEqual(answers.map(answer => answer.open), [true, true]);
assert.equal(wrapper.className, 'mod_lessonmark-print-figure');
assert.equal(wrapper.childNodes[0], heading);
assert.equal(wrapper.childNodes[1], paragraph);
listeners.beforeprint();
listeners.afterprint();
assert.deepEqual(answers.map(answer => answer.open), [false, true]);
assert.equal(restored[0], heading);
assert.equal(restored[1], paragraph);
listeners.afterprint();
listeners.beforeprint();
listeners.afterprint();
assert.deepEqual(answers.map(answer => answer.open), [false, true]);
console.log('Browser print: disclosure restoration, heading/image grouping and repeated print passed.');
