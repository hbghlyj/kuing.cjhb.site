const assert = require('node:assert/strict');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

// Selected-text quotes must match the pre-wrapped post. Robot-classified
// requests omit mathjax-copy.js, so cover both serializer paths.
const root = path.join(__dirname, '..', '..');
const moduleCss = fs.readFileSync(path.join(root, 'template/default/common/module.css'), 'utf8');
const postCss = moduleCss.match(/\.t_f\[id\^="postmessage_"\]\s*\{[^}]*\}/)?.[0] || '';
assert.match(postCss, /white-space:\s*pre-wrap/, 'Desktop post bodies are no longer pre-wrapped; update this regression.');

const pageHtml = withCopy => `<!doctype html><html><head><meta charset="utf-8"><style>${postCss}</style>
<script>window.$L = key => key; window.fid = 2; window.tid = 3; window.showWindow = (key, url) => { window.__quoteUrl = url; };</script>
${withCopy ? '<script src="/static/js/mathjax-copy.js"></script>' : ''}
</head><body><table class="cp0"><tr><td class="t_f" id="postmessage_7">Initial post</td></tr></table>
<script src="/static/js/forum_viewthread.js"></script></body></html>`;

const cases = [
    ['nl2br break', 'one<br>\ntwo', 'one\ntwo'],
    ['nl2br blank line', 'one<br>\n<br>\ntwo', 'one\n\ntwo'],
    ['br break', 'one<br>two', 'one\ntwo'],
    ['br blank line', 'one<br><br>two', 'one\n\ntwo'],
    ['literal spaces', 'a   b', 'a   b'],
    ['TikZ indentation', '\\begin{tikzpicture}<br>  \\draw (0,0);<br>\\end{tikzpicture}', '\\begin{tikzpicture}\n  \\draw (0,0);\n\\end{tikzpicture}'],
    ['image', 'before<br><img src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt="pic">after', 'before\nafter'],
    ['quote block', '<div class="quote"><blockquote>quoted</blockquote></div>reply', 'quoted\nreply'],
];

const quoteFromSelection = async (page, html) => {
    const before = await page.evaluate(html => {
        const post = document.getElementById('postmessage_7');
        post.innerHTML = html;
        const range = document.createRange();
        range.selectNodeContents(post);
        getSelection().removeAllRanges();
        getSelection().addRange(range);
        document.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
        window.__quoteUrl = '';
        return post.innerHTML;
    }, html);
    await page.locator('.sshare.is-active button').click();
    const result = await page.evaluate(() => ({
        quote: new URL(window.__quoteUrl, location.href).searchParams.get('quote'),
        post: document.getElementById('postmessage_7').innerHTML,
    }));
    assert.equal(result.post, before, 'Selected-text quote changed the post DOM');
    return result.quote;
};

(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.TEST_CHROME_CHANNEL ? { channel: process.env.TEST_CHROME_CHANNEL } : {}) });
    try {
        for(const withCopy of [true, false]) {
            const label = withCopy ? 'with mathjax-copy.js' : 'without mathjax-copy.js';
            const page = await browser.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('http://quote.test/**', route => {
                const url = new URL(route.request().url());
                if(url.pathname.startsWith('/static/js/')) {
                    return route.fulfill({ contentType: 'application/javascript', body: fs.readFileSync(path.join(root, url.pathname)) });
                }
                return route.fulfill({ contentType: 'text/html', body: pageHtml(withCopy) });
            });
            await page.goto('http://quote.test/forum.php?mod=viewthread&tid=3');
            assert.equal(await page.evaluate(() => typeof rangeToPlainTextWithMathJax === 'function'), withCopy);

            // Same DOM and Selection/Range steps as the test_forum.js quote check.
            await page.evaluate(() => {
                const node = document.getElementById('postmessage_7');
                const firstLine = document.createTextNode('Selected quote line one');
                const secondLine = document.createTextNode('Selected quote line two');
                node.replaceChildren(firstLine, document.createElement('br'), document.createTextNode('\n'), secondLine);
                const range = document.createRange();
                range.setStart(firstLine, 0);
                range.setEnd(secondLine, secondLine.length);
                getSelection().removeAllRanges();
                getSelection().addRange(range);
                window.__quoteUrl = '';
                document.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
            });
            await page.locator('.sshare.is-active button').click();
            const ciQuote = await page.evaluate(() => new URL(window.__quoteUrl, location.href).searchParams.get('quote'));
            assert.equal(ciQuote, 'Selected quote line one\nSelected quote line two', `CI selected-text quote ${label}`);

            for(const [name, html, expected] of cases) {
                assert.equal(await quoteFromSelection(page, html), expected, `${name} ${label}`);
            }

            if(withCopy) {
                const math = await page.evaluate(() => {
                    const post = document.getElementById('postmessage_7');
                    post.innerHTML = 'a   <mjx-container>rendered</mjx-container><br>\n  b';
                    const container = post.querySelector('mjx-container');
                    window.MathJax = { startup: { document: { math: [{ typesetRoot: container, start: { delim: '$' }, end: { delim: '$' }, math: ' x+1 ' }] } } };
                    const range = document.createRange();
                    range.selectNodeContents(post);
                    getSelection().removeAllRanges();
                    getSelection().addRange(range);
                    const clipboardData = new DataTransfer();
                    document.dispatchEvent(new ClipboardEvent('copy', { clipboardData, bubbles: true, cancelable: true }));
                    return { quote: rangeToPlainTextWithMathJax(range), copy: clipboardData.getData('text/plain') };
                });
                assert.deepEqual(math, { quote: 'a   $x+1$\n  b', copy: 'a   $x+1$\n  b' }, 'MathJax source copy');
            }
            assert.deepEqual(errors, [], `Page errors ${label}`);
            await page.close();
        }
        console.log('Selected-text quotes keep one newline per break, blank lines, spaces and math');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
