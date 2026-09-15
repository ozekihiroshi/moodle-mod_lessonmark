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
 * Dedicated Markdown editor with import and save-free server preview.
 *
 * @module     mod_lessonmark/editor
 * @copyright  2026 Hiroshi Ozeki
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import Ajax from 'core/ajax';
import {getStrings} from 'core/str';
import {watchForm} from 'core_form/changechecker';
import {highlight} from './syntax-highlighter';

const DEBOUNCE_MS = 400;
const OPTIONAL_RENDERER_WAIT_MS = 3000;
const OPTIONAL_RENDERER_POLL_MS = 50;
const STRING_DEFINITIONS = [
    {name: 'loading', key: 'previewloading', component: 'mod_lessonmark'},
    {name: 'ready', key: 'previewready', component: 'mod_lessonmark'},
    {name: 'previewLabel', key: 'previewmode', component: 'mod_lessonmark'},
    {name: 'error', key: 'previewerror', component: 'mod_lessonmark'},
    {name: 'empty', key: 'previewempty', component: 'mod_lessonmark'},
    {name: 'importConfirm', key: 'importconfirm', component: 'mod_lessonmark'},
    {name: 'importContinue', key: 'continue', component: 'moodle'},
    {name: 'importTitle', key: 'confirmation', component: 'admin'},
    {name: 'importInvalidUtf8', key: 'importinvalidutf8', component: 'mod_lessonmark'},
    {name: 'importReady', key: 'importready', component: 'mod_lessonmark'},
    {name: 'importTooLarge', key: 'importtoolarge', component: 'mod_lessonmark'},
    {name: 'importWrongType', key: 'importwrongtype', component: 'mod_lessonmark'},
    {name: 'importError', key: 'importerror', component: 'mod_lessonmark'},
];

/**
 * Load editor strings through Moodle's string service instead of AMD arguments.
 *
 * @returns {Promise<Object>} Strings keyed for editor use.
 */
const loadStrings = async() => {
    const values = await getStrings(STRING_DEFINITIONS.map(({key, component}) => ({key, component})));
    return Object.fromEntries(STRING_DEFINITIONS.map(({name}, index) => [name, values[index]]));
};

/**
 * Wait briefly for a non-AMD browser renderer requested by Moodle.
 *
 * Moodle may start the AMD editor while a large local script is still being
 * evaluated. Poll only when the rendered document contains a matching feature.
 *
 * @param {string} name Window property containing the renderer function.
 * @returns {Promise<Function|null>} Available renderer or null after timeout.
 */
const waitForOptionalRenderer = name => new Promise(resolve => {
    if (typeof window[name] === 'function') {
        resolve(window[name]);
        return;
    }

    const started = Date.now();
    const timer = window.setInterval(() => {
        if (typeof window[name] === 'function') {
            window.clearInterval(timer);
            resolve(window[name]);
            return;
        }
        if (Date.now() - started >= OPTIONAL_RENDERER_WAIT_MS) {
            window.clearInterval(timer);
            resolve(null);
        }
    }, OPTIONAL_RENDERER_POLL_MS);
});

/**
 * Initialise one LessonMark editor.
 *
 * @param {Object} config Editor configuration.
 */
export const init = async config => {
    const source = document.querySelector(config.sourceSelector);
    const container = document.querySelector(config.containerSelector);
    if (!source || !container) {
        return;
    }
    const strings = await loadStrings();

    const files = config.filesSelector ? document.querySelector(config.filesSelector) : null;
    const sourceField = source.closest('.fitem');
    const toolbar = container.querySelector('.mod_lessonmark-editor__toolbar');
    const preview = container.querySelector('[data-region="preview-content"]');
    const status = container.querySelector('[data-region="preview-status"]');
    const editButton = document.getElementById(config.editTabId);
    const previewButton = document.getElementById(config.previewTabId);
    const refreshButton = container.querySelector('[data-action="refresh-preview"]');
    const importButton = container.querySelector('[data-action="import-markdown"]');
    const importFile = container.querySelector('[data-action="import-file"]');
    if (!sourceField || !toolbar || !preview || !status || !editButton || !previewButton || !refreshButton) {
        return;
    }
    const shell = document.createElement('div');
    shell.className = 'mod_lessonmark-editor__panes';

    sourceField.parentNode.insertBefore(shell, sourceField);
    shell.append(toolbar, sourceField, container);
    sourceField.classList.add('mod_lessonmark-editor__source');
    sourceField.id = config.sourcePanelId;
    watchForm(source);

    let timer = null;
    let requestNumber = 0;
    let activeMode = 'edit';
    const mobileQuery = window.matchMedia('(max-width: 767.98px)');

    const setStatus = (message, type = '') => {
        status.textContent = message;
        status.classList.toggle('text-warning', type === 'warning');
        status.classList.toggle('text-danger', type === 'error');
    };

    const updatePanelVisibility = () => {
        if (mobileQuery.matches) {
            const showPreview = activeMode === 'preview';
            sourceField.hidden = showPreview;
            container.hidden = !showPreview;
            sourceField.setAttribute('role', 'tabpanel');
            sourceField.setAttribute('aria-labelledby', config.editTabId);
            preview.setAttribute('role', 'tabpanel');
            preview.setAttribute('aria-labelledby', config.previewTabId);
            preview.removeAttribute('aria-label');
            return;
        }
        sourceField.hidden = false;
        container.hidden = false;
        sourceField.removeAttribute('role');
        sourceField.removeAttribute('aria-labelledby');
        preview.setAttribute('role', 'region');
        preview.setAttribute('aria-label', strings.previewLabel);
        preview.removeAttribute('aria-labelledby');
    };

    const setMode = mode => {
        const showPreview = mode === 'preview';
        activeMode = mode;
        shell.dataset.mobileMode = mode;
        editButton.classList.toggle('is-active', !showPreview);
        previewButton.classList.toggle('is-active', showPreview);
        editButton.setAttribute('aria-selected', String(!showPreview));
        previewButton.setAttribute('aria-selected', String(showPreview));
        editButton.tabIndex = showPreview ? -1 : 0;
        previewButton.tabIndex = showPreview ? 0 : -1;
        updatePanelVisibility();
    };

    const handleTabKeydown = event => {
        const tabs = [editButton, previewButton];
        let target = null;
        switch (event.key) {
            case 'Home':
                target = tabs[0];
                break;
            case 'End':
                target = tabs[1];
                break;
            case 'ArrowLeft':
            case 'ArrowUp':
            case 'ArrowRight':
            case 'ArrowDown':
                target = event.currentTarget === editButton ? previewButton : editButton;
                break;
        }
        if (!target) {
            return;
        }
        event.preventDefault();
        target.focus();
        target.click();
    };

    const render = async() => {
        const currentRequest = ++requestNumber;
        setStatus(strings.loading);
        refreshButton.disabled = true;
        preview.setAttribute('aria-busy', 'true');
        const args = {
            markdownsource: source.value,
            cmid: config.cmid,
            courseid: config.courseid,
            draftitemid: files ? Number(files.value) : 0,
        };

        try {
            const result = await Ajax.call([{methodname: 'mod_lessonmark_render_preview', args}])[0];
            if (currentRequest !== requestNumber) {
                return;
            }
            if (typeof result.html !== 'string') {
                throw new Error('Preview request failed');
            }
            if (result.html) {
                preview.innerHTML = result.html;
                preview.classList.remove('text-muted');
            } else {
                preview.textContent = strings.empty;
                preview.classList.add('text-muted');
            }
            highlight(preview);
            if (typeof window.ozmdRenderMath === 'function') {
                await window.ozmdRenderMath(preview);
            }
            if (preview.querySelector('code.language-mermaid')) {
                const renderMermaid = await waitForOptionalRenderer('ozmdRenderMermaid');
                if (renderMermaid) {
                    await renderMermaid(preview);
                }
            }
            const messages = Array.isArray(result.diagnostics) ? result.diagnostics
                .map(diagnostic => diagnostic.message)
                .filter(message => typeof message === 'string' && message) : [];
            setStatus([strings.ready, ...messages].join(' '), messages.length > 0 ? 'warning' : '');
        } catch (error) {
            if (currentRequest === requestNumber) {
                setStatus(strings.error, 'error');
            }
        } finally {
            if (currentRequest === requestNumber) {
                refreshButton.disabled = false;
                preview.setAttribute('aria-busy', 'false');
            }
        }
    };

    const scheduleRender = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(render, DEBOUNCE_MS);
    };

    const importMarkdown = async file => {
        if (!/\.md$/iu.test(file.name)) {
            throw new Error('wrongtype');
        }
        if (file.size > config.maxSourceBytes + 3) {
            throw new Error('toolarge');
        }
        let text;
        try {
            text = new TextDecoder('utf-8', {fatal: true}).decode(await file.arrayBuffer());
        } catch (error) {
            throw new Error('invalidutf8');
        }
        text = text.replace(/^\uFEFF/u, '').replace(/\r\n?/gu, '\n');
        if (new TextEncoder().encode(text).length > config.maxSourceBytes) {
            throw new Error('toolarge');
        }
        return text;
    };

    const applyImport = async file => {
        importButton.disabled = true;
        try {
            source.value = await importMarkdown(file);
            setMode('edit');
            setStatus(strings.importReady);
            source.dispatchEvent(new Event('input', {bubbles: true}));
            source.focus();
        } catch (error) {
            const messages = {
                invalidutf8: strings.importInvalidUtf8,
                toolarge: strings.importTooLarge,
                wrongtype: strings.importWrongType,
            };
            setStatus(messages[error.message] || strings.importError, 'error');
        } finally {
            importButton.disabled = false;
            importFile.value = '';
        }
    };

    source.addEventListener('input', scheduleRender);
    refreshButton.addEventListener('click', render);
    editButton.addEventListener('click', () => setMode('edit'));
    editButton.addEventListener('keydown', handleTabKeydown);
    previewButton.addEventListener('keydown', handleTabKeydown);
    mobileQuery.addEventListener('change', updatePanelVisibility);
    previewButton.addEventListener('click', () => {
        setMode('preview');
        render();
    });
    if (importButton && importFile) {
        importButton.addEventListener('click', () => {
            importFile.value = '';
            importFile.click();
        });
        importFile.addEventListener('change', () => {
            const file = importFile.files[0];
            if (!file) {
                return;
            }
            if (source.value === '') {
                applyImport(file);
                return;
            }
            Notification.confirm(
                strings.importTitle,
                strings.importConfirm,
                strings.importContinue,
                null,
                () => applyImport(file),
                () => {
                    importFile.value = '';
                }
            );
        });
    }
    setMode('edit');
    render();
};
