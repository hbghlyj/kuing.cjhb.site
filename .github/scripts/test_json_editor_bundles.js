// Editor.js 2.31.7 + editorjs-undo (MitFrame 9bf00903) in a real browser: undo/redo of a
// text edit must not throw (the previous build called an undefined setTextContent), and
// the retired RAW tool (badcea04) must still load saved blocks inertly while staying out
// of the toolbox.
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

const EDITORJS = process.env.EDITORJS_BUNDLE || 'static/js/editorjs/editorjs.umd.js';
const UNDO = process.env.EDITORJS_UNDO_BUNDLE || 'static/js/editorjs/tools/editorjs-undo/editorjs-undo.js';

(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.TEST_CHROME_CHANNEL ? { channel: process.env.TEST_CHROME_CHANNEL } : {}) });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<!doctype html><html><body><div id="editorjs"></div></body></html>');
        await page.addScriptTag({ path: EDITORJS });
        await page.addScriptTag({ path: UNDO });
        await page.addScriptTag({ path: 'static/js/editorjs/tools/raw/raw.js' });

        const rawHtml = '<img src="x" onerror="window.pwned=1"><script>window.pwned=2</script>';
        const paragraph = 'Hello <b>world</b> $a&lt;b$';
        const version = await page.evaluate(async ({ rawHtml, paragraph }) => {
            window.pwned = 0;
            // Same tool configuration as editorblock_raw::getConfig().
            window.editor = new EditorJS({
                holder: 'editorjs',
                tools: { raw: { class: RawTool, toolbox: false, placeholder: 'html', tunes: [] } },
                data: { blocks: [
                    { id: 'p1', type: 'paragraph', data: { text: paragraph } },
                    { id: 'r1', type: 'raw', data: { html: rawHtml } },
                ] },
            });
            await editor.isReady;
            window.undo = new Undo({ editor, config: { shortcuts: { undo: ['CMD+Z'], redo: ['CMD+Y', 'CMD+SHIFT+Z'] } } });
            undo.initialize((await editor.save()).blocks);
            return EditorJS.version;
        }, { rawHtml, paragraph });
        assert.equal(version, '2.31.7');

        // Saved RAW content loads into its textarea without executing and survives saving.
        assert.equal(await page.inputValue('.ce-rawtool__textarea'), rawHtml);
        assert.equal(await page.evaluate(() => window.pwned), 0);
        const saved = await page.evaluate(async () => (await editor.save()).blocks.map(block => [block.type, block.data.html ?? block.data.text]));
        assert.deepEqual(saved, [['paragraph', paragraph], ['raw', rawHtml]]);

        // The toolbox offers built-in blocks but no longer RAW.
        await page.click('.ce-paragraph');
        await page.evaluate(() => editor.toolbar.toggleToolbox(true));
        await page.waitForSelector('.ce-popover-item[data-item-name]', { state: 'attached' });
        const toolbox = await page.$$eval('.ce-popover-item[data-item-name]', items => items.map(item => item.dataset.itemName));
        assert.ok(toolbox.includes('paragraph'), `toolbox: ${toolbox}`);
        assert.ok(!toolbox.includes('raw'), `RAW still offered: ${toolbox}`);
        await page.evaluate(() => editor.toolbar.toggleToolbox(false));

        // Edit, undo, redo.
        await page.click('.ce-paragraph');
        await page.keyboard.press('End');
        await page.keyboard.type(' more');
        await page.waitForFunction(() => undo.stack.length > 1, null, { timeout: 5000 });
        const undoError = await page.evaluate(async () => {
            try { await undo.undo(); return null; } catch(error) { return String(error && error.message || error); }
        });
        assert.equal(undoError, null, 'undo() threw');
        await page.waitForFunction(text => document.querySelector('.ce-paragraph').innerHTML === text, paragraph, { timeout: 5000 });
        assert.equal((await page.evaluate(async () => (await editor.save()).blocks[0].data.text)), paragraph);
        const redoError = await page.evaluate(async () => {
            try { await undo.redo(); return null; } catch(error) { return String(error && error.message || error); }
        });
        assert.equal(redoError, null, 'redo() threw');
        await page.waitForFunction(() => document.querySelector('.ce-paragraph').innerHTML.endsWith(' more'), null, { timeout: 5000 });
        await page.waitForTimeout(100);
        assert.deepEqual(errors, []);
        assert.equal(await page.evaluate(() => window.pwned), 0);
        console.log('Editor.js 2.31.7 undo/redo works and the RAW tool is load-only');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
