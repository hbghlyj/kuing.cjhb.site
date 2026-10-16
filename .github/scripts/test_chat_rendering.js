const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.TEST_CHROME_CHANNEL ? { channel: process.env.TEST_CHROME_CHANNEL } : {}) });
    try {
        const page = await browser.newPage();
        const message = (id, body) => ({ message_time: id, body, published: '2026-10-15T12:00:00Z', actor: { id: 1, displayName: 'Member', image: '' } });
        const history = [message('1', '/data/attachment/chat/stalled.jpg'), message('2', 'History text after image')];
        let imageRoute;
        await page.route('http://chat.test/**', route => {
            const url = route.request().url();
            if(url.includes('stalled.jpg')) { imageRoute = route; return; }
            if(url.includes('/history.php')) return route.fulfill({ json: { messages: history, total: 2 } });
            if(url.includes('/chat.php')) return route.fulfill({ json: { message: message('3', 'Saved without a live echo') } });
            return route.fulfill({ contentType: 'text/html', body: '<html><body></body></html>' });
        });
        await page.goto('http://chat.test/');
        await page.addStyleTag({ content: '.activity-stream {height:100px;width:250px;overflow:auto;overflow-anchor:none} .message-item {min-height:60px} .text img {width:150px}' });
        await page.evaluate(() => {
            window.$L = key => key;
            window._i18n_ = 'EN';
            window.FORMHASH = 'fixture';
            window.discuz_uid = 1;
            window.renderInitialAvatar = image => { image.removeAttribute('src'); };
        });
        const source = fs.readFileSync('chat/PusherChatWidget.js', 'utf8');
        const startup = source.indexOf('  const startPusherChat =');
        assert.ok(startup > 0);
        await page.addScriptTag({ content: source.slice(0, startup) + '\nwindow.ChatRenderingFixture = PusherChatWidget;})();' });
        await page.evaluate(() => {
            const callbacks = {};
            const channel = { bind: (event, fn) => { callbacks[event] = fn; } };
            new window.ChatRenderingFixture({ subscribe: () => channel, connection: { bind() {} } }, { forceOpen: true });
            callbacks['pusher:subscription_succeeded']();
        });
        await page.locator('.message-item[data-message-time="2"]').waitFor({ timeout: 3000 });
        assert.equal(await page.locator('.message-item[data-message-time="1"] img[loading="lazy"]').count(), 1);
        await page.locator('textarea').fill('Saved without a live echo');
        await page.locator('.pusher-chat-widget-send-btn').click();
        await page.locator('.message-item[data-message-time="3"]').waitFor({ timeout: 3000 });
        assert.equal(await page.locator('.message-item').count(), 3);
        await page.waitForFunction(() => {
            const list = document.querySelector('.activity-stream');
            return list.scrollHeight - list.clientHeight - list.scrollTop <= 4;
        });
        assert.ok(imageRoute, 'Lazy image request must start after insertion');
        await imageRoute.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="300"><rect width="150" height="300" fill="blue"/></svg>' });
        await page.waitForFunction(() => {
            const list = document.querySelector('.activity-stream');
            const image = list.querySelector('.text img');
            return image.naturalHeight === 300 && list.scrollHeight - list.clientHeight - list.scrollTop <= 4;
        });
        await page.evaluate(() => {
            const list = document.querySelector('.activity-stream');
            list.scrollTop = 0;
            list.dispatchEvent(new Event('scroll'));
            list.querySelector('.text img').style.height = '450px';
        });
        await page.waitForFunction(() => document.querySelector('.text img').getBoundingClientRect().height === 450);
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
        assert.equal(await page.locator('.activity-stream').evaluate(list => list.scrollTop), 0, 'Image reflow must not pull a reader back to the bottom');
        console.log('Chat history and sent messages render despite a stalled lazy image');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
