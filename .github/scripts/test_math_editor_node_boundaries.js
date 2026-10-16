const assert = require('node:assert/strict');
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.TEST_CHROME_CHANNEL ? { channel: process.env.TEST_CHROME_CHANNEL } : {}) });
    try {
        const page = await browser.newPage();
        await page.setContent('<iframe id="editor"></iframe>');
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
        console.log('Math editor preserves line breaks around intervening non-text nodes');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
