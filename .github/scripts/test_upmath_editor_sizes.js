const assert = require('node:assert/strict');
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.TEST_CHROME_CHANNEL ? { channel: process.env.TEST_CHROME_CHANNEL } : {}) });
    try {
        const page = await browser.newPage();
        await page.route('https://i.upmath.me/**', route => route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>' }));
        await page.setContent('<iframe id="editor"></iframe>');
        await page.evaluate(() => {
            window.EXTRAFUNC = {};
            window.editdoc = document.getElementById('editor').contentDocument;
            window.editwin = document.getElementById('editor').contentWindow;
            window.fetches = 0;
            window.fetch = () => {
                fetches++;
                return new Promise(resolve => { window.finishSizes = () => resolve({ ok: true, text: () => Promise.resolve('postMessage("85.875|666|171.75")') }); });
            };
            window.MathJax = {
                _: { input: { tex: {
                    Configuration: { Configuration: { create() {} } },
                    ParseMethods: { default: { environment() {} } },
                    TokenMap: { CommandMap: function() {}, EnvironmentMap: function() {} },
                    TexError: { default: Error }
                } } },
                typesetClear() {},
                typesetPromise(nodes) {
                    nodes[0].firstChild.innerHTML = '<mjx-container><mjx-mglyph><img src="https://i.upmath.me/svgb/fixture" style="width:1em;height:1em"></mjx-mglyph></mjx-container>';
                    return Promise.resolve();
                }
            };
        });
        await page.addScriptTag({ path: 'static/js/editor.js' });
        await page.addScriptTag({ path: 'static/img.js' });
        await page.addScriptTag({ path: 'static/js/mathjax-full-editor.js' });
        await page.evaluate(() => {
            window.editdoc = document.getElementById('editor').contentDocument;
            window.editwin = document.getElementById('editor').contentWindow;
            const rendered = editdoc.createElement('span');
            rendered.className = 'math-editor-rendered';
            editdoc.body.appendChild(rendered);
            renderMathBatch([{ rendered, math: '\\begin{tikzpicture}x\\end{tikzpicture}' }]);
        });
        await page.waitForFunction(() => editdoc.querySelector('img') && window.finishSizes);
        assert.equal(await page.evaluate(() => editdoc.querySelector('img').style.width), '1em');
        await page.evaluate(() => {
            const image = document.createElement('img');
            image.id = 'mainImage';
            image.src = 'https://i.upmath.me/svgb/fixture';
            document.body.appendChild(image);
            const custom = editdoc.querySelector('img').cloneNode();
            custom.id = 'customImage';
            custom.style.cssText = 'width:50px;height:30px';
            editdoc.body.appendChild(custom);
        });
        await page.evaluate(() => finishSizes());
        await page.waitForFunction(() => editdoc.querySelector('img').style.width === '666pt');
        assert.deepEqual(await page.evaluate(() => {
            const style = editdoc.querySelector('img').style;
            return [style.width, style.height, style.verticalAlign];
        }), ['666pt', '171.75pt', '-85.875pt']);
        assert.equal(await page.evaluate(() => document.getElementById('mainImage').style.width), '666pt');
        assert.equal(await page.evaluate(() => editdoc.getElementById('customImage').style.width), '50px');
        // A newly copied image gets cached dimensions without a second fetch.
        await page.evaluate(() => {
            const image = editdoc.createElement('img');
            image.src = 'https://i.upmath.me/svgb/fixture';
            image.style.cssText = 'width:1em;height:1em';
            editdoc.body.appendChild(image);
            MathJaxUpmathImages.scan(editdoc.body);
        });
        assert.equal(await page.evaluate(() => editdoc.body.lastChild.style.width), '666pt');
        assert.equal(await page.evaluate(() => fetches), 1);
        console.log('Late UpMath dimensions reach iframe copies; subsequent copies reuse cached sizes');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
