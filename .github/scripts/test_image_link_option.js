const assert = require('node:assert/strict');
const path = require('node:path');
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.TEST_CHROME_CHANNEL ? { channel: process.env.TEST_CHROME_CHANNEL } : {}) });
    try {
        const page = await browser.newPage();
        await page.setContent('<input type="checkbox" id="imgurloff">');
        await page.evaluate(() => Object.assign(window, { EXTRAFUNC: {}, allowhtml: false, allowbbcode: true, allowimgcode: true, allowsmilies: false, STATICURL: '/static/' }));
        await page.addScriptTag({ path: path.resolve('static/js/common.js') });
        await page.addScriptTag({ path: path.resolve('static/js/bbcode.js') });
        const nestedFormatting = await page.evaluate(() => [
            html2bbcode('<span style="font-size:x-large">1. Statement</span>'),
            html2bbcode('<span style="color: #222222"><span style="font-size:x-large">1. Statement</span></span>'),
            html2bbcode('<span style="font-size:x-large"><span style="color:#222222">1. Statement</span></span>'),
            html2bbcode('<font color="#222222"><span style="font-size:x-large">1. Statement</span></font>'),
            html2bbcode('<span style="color:#222222">Before <span style="font-size:x-large">Large</span> after</span>')
        ]);
        assert.deepEqual(nestedFormatting, [
            '[size=5]1. Statement[/size]',
            '[color=#222222][size=5]1. Statement[/size][/color]',
            '[size=5][color=#222222]1. Statement[/color][/size]',
            '[color=#222222][size=5]1. Statement[/size][/color]',
            '[color=#222222]Before [size=5]Large[/size] after[/color]'
        ]);
        const result = await page.evaluate(() => {
            const url = 'https://example.com/photo.png';
            const control = document.getElementById('imgurloff');
            const enabled = [bbcode2html(url), html2bbcode(url)];
            control.checked = true;
            const disabled = [bbcode2html(url), html2bbcode(url)];
            const explicit = bbcode2html('[img]' + url + '[/img]');
            const defaultParser = parseurl(url, 'bbcode', false);
            control.remove();
            return { enabled, disabled, explicit, defaultParser, absent: bbcode2html(url) };
        });
        assert.ok(result.enabled[0].includes('<img'));
        assert.ok(result.enabled[1].includes('[img]'));
        assert.ok(!result.disabled[0].includes('<img'));
        assert.ok(!result.disabled[1].includes('[img]'));
        assert.ok(result.explicit.includes('<img'));
        assert.ok(result.defaultParser.includes('[img]'));
        assert.ok(!result.absent.includes('<img'));
        console.log('Image-link recognition respects editor checkbox and preserves shared parser defaults');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
