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
        const scripts = await page.evaluate(() => {
            const html = 'x<sup>2</sup> + H<sub>2</sub>O <sup><strong>bold</strong><sub>nested</sub></sup>';
            const bbcode = html2bbcode(html);
            return { bbcode, html: bbcode2html(bbcode), roundTrip: html2bbcode(bbcode2html(bbcode)) };
        });
        const scriptBbcode = 'x[sup]2[/sup] + H[sub]2[/sub]O [sup][b]bold[/b][sub]nested[/sub][/sup]';
        assert.equal(scripts.bbcode, scriptBbcode);
        assert.equal(scripts.html, 'x<sup>2</sup> + H<sub>2</sub>O <sup><strong>bold</strong><sub>nested</sub></sup>');
        assert.equal(scripts.roundTrip, scriptBbcode);
        const captionedTable = await page.evaluate(() => html2bbcode('<table><caption><strong>Results</strong></caption><tbody><tr><td>42</td></tr></tbody></table>'));
        assert.match(captionedTable, /^\[b\]Results\[\/b\]\n+\[table\][\s\S]*42[\s\S]*\[\/table\]$/);
        assert.equal(await page.evaluate(() => html2bbcode('<a href="https://en.wikipedia.org/wiki/Abstract_algebra"> abstract algebra</a>')),
            '[url=https://en.wikipedia.org/wiki/Abstract_algebra] abstract algebra[/url]');
        const codePaste = await page.evaluate(() => {
            const html = '<pre><code class="language-js"><span>  if (x &lt; 2)</span> {&#10;\t$x = &quot;&amp;lt;&quot;;<br>  }</code></pre>';
            const bbcode = html2bbcode(html);
            return [
                bbcode,
                html2bbcode(bbcode2html(bbcode)),
                html2bbcode('<pre>  first\n  second</pre>'),
                html2bbcode('<div class="blockcode"><pre>editor block</pre></div>'),
                html2bbcode('<code>inline</code>'),
                html2bbcode('<code><span>first</span><br>  second &lt; 3</code>'),
                html2bbcode('<code>first&#10;second</code>'),
                html2bbcode('<code>inline&#10;</code>').startsWith('\x60')
            ];
        });
        const block = '[code]  if (x < 2) {\n\t$x = "&lt;";\n  }[/code]';
        assert.deepEqual(codePaste, [
            block, block, '[code]  first\n  second[/code]', '[code]editor block[/code]', '\x60inline\x60',
            '[code]first\n  second < 3[/code]', '[code]first\nsecond[/code]', true
        ]);
        const hslColors = await page.evaluate(() => [
            html2bbcode('<span style="background-color:hsl(none 0% 100%)">White</span>'),
            html2bbcode('<span style="background-color:hsl(60 100% 50%)">Yellow</span>'),
            html2bbcode('<span style="background-color:hsla(60,100%,50%,0)">Transparent</span>'),
            html2bbcode('<span style="color:hsl(120 100% 50%)">Green</span>')
        ]);
        assert.deepEqual(hslColors, ['White', '[backcolor=#ffff00]Yellow[/backcolor]', 'Transparent', '[color=#00ff00]Green[/color]']);
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
        const cssSizeUnits = await page.evaluate(() => [
            '[size=1.25em]em[/size]', '[size=.875rem]rem[/size]',
            '[size=2vw]vw[/size]', '[size=110%]percent[/size]', '[size=12px][/size]'
        ].map(bbcode2html));
        assert.deepEqual(cssSizeUnits, [
            '<span style="font-size: 1.25em">em</span>',
            '<span style="font-size: .875rem">rem</span>',
            '<span style="font-size: 2vw">vw</span>',
            '<span style="font-size: 110%">percent</span>', ''
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
