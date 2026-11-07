const assert = require('node:assert/strict');
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.TEST_CHROME_CHANNEL ? { channel: process.env.TEST_CHROME_CHANNEL } : {}) });
    try {
        const page = await browser.newPage();
        await page.setContent('<iframe id="editor"></iframe>');
        await page.evaluate(() => { window.EXTRAFUNC = {}; });
        await page.addScriptTag({ path: 'static/js/editor.js' });
        await page.evaluate(() => {
            window.wysiwyg = 0;
            window.editdoc = document.getElementById('editor').contentDocument;
            window.editwin = document.getElementById('editor').contentWindow;
            window.MathJax = { typesetPromise: () => Promise.resolve(), typesetClear: () => {} };
        });
        await page.addScriptTag({ path: 'static/js/mathjax-full-editor.js' });
        for(const barrier of ['<img id="barrier" src="data:,">', '<span id="barrier" contenteditable="false">Widget</span>']) {
            const result = await page.evaluate(barrier => {
                editdoc.body.innerHTML = 'See $X$.<br>' + barrier + '<br><strong>Proof.</strong>';
                wysiwyg = 1;
                renderMathEditorContent();
                const node = editdoc.getElementById('barrier');
                const before = node.previousSibling?.nodeName;
                const after = node.nextSibling?.nodeName;
                renderMathEditorContent();
                return { before, after, afterAgain: node.nextSibling?.nodeName, math: editdoc.querySelector('[data-math-source]')?.getAttribute('data-math-source') };
            }, barrier);
            assert.deepEqual(result, { before: 'BR', after: 'BR', afterAgain: 'BR', math: '$X$' });
        }
        for(const position of ['inside', 'after']) {
            await page.evaluate(position => {
                editdoc.body.contentEditable = 'true';
                editdoc.body.innerHTML = '<div class="quote"><blockquote><div><br></div></blockquote></div><p id="keep">Keep this text</p>';
                window.doane = event => { event.preventDefault(); event.stopPropagation(); };
                editdoc.body.onkeydown = keyDownNormalizeBlockMerge;
                const range = editdoc.createRange();
                range.setStart(position === 'inside' ? editdoc.querySelector('blockquote div') : editdoc.getElementById('keep'), 0);
                range.collapse(true);
                editwin.getSelection().removeAllRanges();
                editwin.getSelection().addRange(range);
                editdoc.body.focus();
            }, position);
            await page.frameLocator('#editor').locator('body').press('Backspace');
            assert.equal(await page.frameLocator('#editor').locator('.quote').count(), 0);
            assert.equal(await page.frameLocator('#editor').locator('#keep').innerText(), 'Keep this text');
        }
        console.log('Math boundaries and empty quote Backspace preserve surrounding content');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
