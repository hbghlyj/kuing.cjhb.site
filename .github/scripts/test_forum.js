const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const assert = require('assert');
const { execSync } = require('child_process');
const { reportCiFailure } = require('./report_ci_failure');
const testRunId = process.env.TEST_RUN_ID || Date.now().toString();
const standardSubject = `Standard User Thread ${testRunId}`;
const editedStandardSubject = `${standardSubject} (Edited)`;
const attachmentSubject = `Thread with Attachment ${testRunId}`;
const nonImageAttachmentSubject = `Thread with Non-Image Attachment ${testRunId}`;
const svgAttachmentSubject = `Thread with SVG Attachment ${testRunId}`;
const mathDraftSubject = `Thread with Restored Math Draft ${testRunId}`;
const existingMathSubject = `Thread with WYSIWYG Existing Math ${testRunId}`;

const PUSHER_STUB = `
(() => {
  function Emitter(){ this.listeners = Object.create(null); }
  Emitter.prototype.bind = function(event, callback){ (this.listeners[event] ||= []).push(callback); };
  Emitter.prototype.unbind = function(event, callback){ this.listeners[event] = (this.listeners[event] || []).filter(listener => listener !== callback); };
  Emitter.prototype.emit = function(event, data){ (this.listeners[event] || []).forEach(listener => listener(data)); };
  window.Pusher = function() {
    this.disconnected = false;
    this.connection = new Emitter();
    this.connection.socket_id = 'stub.' + (window.__pusherStubInstances?.length || 0);
    this.channels = Object.create(null);
    this.subscribe = function(name) { return this.channels[name] ||= new Emitter(); };
    this.unsubscribe = function() {};
    this.disconnect = function() { this.disconnected = true; };
    (window.__pusherStubInstances ||= []).push(this);
  };
})();
`;

const stubPusher = async targetContext => {
    await targetContext.route('**/chat/pusher.min.js', route => route.fulfill({
        contentType: 'application/javascript',
        body: PUSHER_STUB
    }));
};

const assertPusherMetadataOrder = () => {
    const templateFiles = fs.readdirSync('template', { recursive: true })
        .filter(file => /\.(?:htm|php)$/.test(file));
    for(const file of templateFiles) {
        const source = fs.readFileSync(path.join('template', file), 'utf8');
        const widgetIndex = source.indexOf('/chat/PusherChatWidget.js');
        if(widgetIndex === -1) continue;
        const metadataIndex = source.indexOf('/chat/PusherForumMetadata.js');
        assert.ok(metadataIndex !== -1 && metadataIndex < widgetIndex, `Assertion Error: ${file} loads PusherChatWidget.js without preceding PusherForumMetadata.js.`);
    }
};

const testPusherLeaderCoordination = async browser => {
    const isolatePusherChannel = () => {
        const channelName = 'kuing-pusher-events-v1';
        const nativePostMessage = BroadcastChannel.prototype.postMessage;
        const nativeAddEventListener = BroadcastChannel.prototype.addEventListener;
        BroadcastChannel.prototype.postMessage = function(message) {
            if(window.__freezePusherLeader && this.name === channelName) return;
            return nativePostMessage.call(this, message);
        };
        BroadcastChannel.prototype.addEventListener = function(type, listener, options) {
            if(this.name !== channelName || type !== 'message') {
                return nativeAddEventListener.call(this, type, listener, options);
            }
            return nativeAddEventListener.call(this, type, event => {
                if(!window.__freezePusherLeader) listener.call(this, event);
            }, options);
        };
    };
    const pusherCount = page => page.evaluate(() => (window.__pusherStubInstances || []).filter(instance => !instance.disconnected).length);
    const waitForSingleLeader = async (pages, label) => {
        for(let attempt = 0; attempt < 30; attempt++) {
            const counts = await Promise.all(pages.map(pusherCount));
            const leaderIndex = counts.findIndex(count => count === 1);
            if(leaderIndex !== -1 && counts.reduce((total, count) => total + count, 0) === 1) {
                return pages[leaderIndex];
            }
            await new Promise(resolve => setTimeout(resolve, 250));
        }
        assert.fail(`Assertion Error: ${label} did not converge on one Pusher leader.`);
    };
    const pusherContext = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        extraHTTPHeaders: { 'Accept-Language': 'en-US,en;q=0.9' }
    });
    await pusherContext.addCookies([{ name: 'isCollapsed', value: 'true', url: 'http://127.0.0.1:8080/forum.php' }]);
    await stubPusher(pusherContext);
    // Exercise heartbeat failover deterministically; Web Locks cannot be stolen
    // from a live but frozen browsing context.
    await pusherContext.addInitScript(() => { window.KK_PUSHER_FORCE_FALLBACK = true; });
    const pusherPages = await Promise.all([pusherContext.newPage(), pusherContext.newPage(), pusherContext.newPage()]);
    await Promise.all(pusherPages.map(page => page.addInitScript(isolatePusherChannel)));
    try {
        await Promise.all(pusherPages.map(page => page.goto('http://127.0.0.1:8080/forum.php', { waitUntil: 'networkidle' })));
        await Promise.all(pusherPages.map(page => page.waitForFunction(() => !!document.querySelector('.pusher-chat-widget'), null, { timeout: 5000 })));
        const firstLeader = await waitForSingleLeader(pusherPages, 'Three simultaneous tabs');
        const remainingPages = pusherPages.filter(page => page !== firstLeader);

        await firstLeader.close();
        const frozenLeader = await waitForSingleLeader(remainingPages, 'Close handover');
        const recoveryFollower = remainingPages.find(page => page !== frozenLeader);

        // Simulate a frozen renderer: its tab remains open but cannot send or receive heartbeats.
        await frozenLeader.evaluate(() => { window.__freezePusherLeader = true; });
        await recoveryFollower.waitForFunction(() => (window.__pusherStubInstances || []).some(instance => !instance.disconnected), null, { timeout: 15000 });
        await recoveryFollower.evaluate(() => {
            window.__pusherStubInstances.find(instance => !instance.disconnected).channels.Chat.emit('pusher:subscription_succeeded', {});
        });
        await recoveryFollower.waitForFunction(() => document.querySelector('.pusher-chat-widget-send-btn')?.disabled === false, null, { timeout: 5000 });
    } finally {
        await pusherContext.close();
    }
};

(async () => {
    assertPusherMetadataOrder();
    const browser = await chromium.launch();
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        extraHTTPHeaders: {
            'Accept-Language': 'en-US,en;q=0.9'
        }
    });
    await stubPusher(context);
    const page = await context.newPage();
    const scriptSources = new Map();

    page.on('response', async response => {
        if (response.request().resourceType() === 'script') {
            if (response.status() >= 400) console.log(`[SCRIPT ${response.status()}] ${response.url()}`);
            try {
                scriptSources.set(response.url(), await response.text());
            } catch (e) { }
        }
        if (response.status() >= 400) {
            try {
                const text = await response.text();
                console.error(`[HTTP ${response.status()}] ${response.url()}\nResponse Body:\n${text}\n---`);
            } catch (e) {
                console.error(`[HTTP ${response.status()}] ${response.url()} (Failed to read body: ${e.message})`);
            }
        }
    });

    page.on('pageerror', async exception => {
        console.error(`Uncaught Browser Exception at URL [${page.url()}]:\nMessage: ${exception.message}\nStack:\n${exception.stack || exception}`);
        const invalidScripts = [];
        let sourceContext = '';
        try {
            const html = await page.content();
            fs.writeFileSync('browser_error_page.html', html);
            const location = String(exception.stack || exception).match(/:(\d+):\d+(?:\s|$)/);
            if (location) {
                const line = Number(location[1]);
                const lines = html.split('\n');
                const start = Math.max(0, line - 4);
                const end = Math.min(lines.length, line + 3);
                sourceContext = '\nRendered source near failing line:\n' + lines
                    .slice(start, end)
                    .map((text, index) => `${start + index + 1}: ${text}`)
                    .join('\n');
            }
        } catch (e) { }
        for (const frame of page.frames()) {
            try {
                const scripts = await frame.evaluate(() => Array.from(document.scripts)
                    .filter(script => !script.src)
                    .map(script => script.textContent));
                scripts.forEach((source, index) => {
                    try {
                        if (source.includes('import ') || source.includes('import{') || source.includes('export ')) return;
                        new Function(source);
                    } catch (error) {
                        invalidScripts.push(`${frame.url()} inline script #${index + 1}: ${error.message}\n${source.slice(0, 1000)}`);
                    }
                });
            } catch (e) { }
        }
        for (const [url, source] of scriptSources) {
            try {
                if (source.includes('import ') || source.includes('import{') || source.includes('export ')) continue;
                new Function(source);
            } catch (error) {
                invalidScripts.push(`${url}: ${error.message}\n${source.slice(0, 1000)}`);
            }
        }
        const diagnostic = invalidScripts.length ? `\nInvalid scripts:\n${invalidScripts.join('\n\n')}` : '';
        const failure = `Uncaught exception in browser at [${page.url()}]: ${exception.message || exception}${sourceContext}${diagnostic}`;
        fs.writeFileSync('browser_error.txt', failure);
        throw new Error(failure);
    });

    page.on('console', msg => {
        if (msg.type() === 'error') {
            const text = msg.text();
            if(/pusher\.com.*WebSocket|WebSocket.*pusher\.com/i.test(text)) {
                return;
            }
            throw new Error(`Console error in browser: ${text}`);
        }
    });
    page.on('requestfailed', request => {
        const errorText = request.failure()?.errorText || 'unknown error';
        const requestPath = new URL(request.url()).pathname;
        if(errorText === 'net::ERR_ABORTED' && requestPath === '/static/image/common/none.gif') {
            return;
        }
        if(errorText === 'net::ERR_ABORTED' && request.isNavigationRequest()) {
            console.log(`Ignoring aborted navigation request: ${request.url()}`);
            return;
        }
        throw new Error(`Browser request failed: ${request.url()} (${errorText})`);
    });

    let report = "# DiscuzX Functional Test Report\n\n";
    const fillPostEditor = async (message, targetPage = page, root = targetPage) => {
        const editorFrame = root.locator('iframe[id$="_iframe"]:visible');
        if(await editorFrame.count()) {
            assert.strictEqual(await editorFrame.count(), 1, 'Assertion Error: More than one post editor iframe rendered.');
            const frameId = await editorFrame.getAttribute('id');
            assert.ok(frameId, 'Assertion Error: Visible post editor iframe has no ID.');
            await targetPage.frameLocator(`#${frameId}`).locator('body').fill(message);
            return;
        }

        const textEditor = root.locator('textarea[name="message"]:visible');
        assert.strictEqual(await textEditor.count(), 1, 'Assertion Error: Visible post editor did not render.');
        await textEditor.fill(message);
    };
    const appendToQuotedPostEditor = async (message, quotedText, targetPage = page, root = targetPage) => {
        const editorFrame = root.locator('iframe[id$="_iframe"]:visible');
        if(await editorFrame.count()) {
            assert.strictEqual(await editorFrame.count(), 1, 'Assertion Error: More than one quoted post editor iframe rendered.');
            const frameId = await editorFrame.getAttribute('id');
            const editorBody = targetPage.frameLocator(`#${frameId}`).locator('body');
            const existingText = await editorBody.innerText();
            assert.ok(existingText.includes(quotedText), 'Assertion Error: Quote action did not preserve the quoted post text.');
            assert.strictEqual(
                await editorBody.locator('blockquote, [class*="quote"]').count(),
                1,
                'Assertion Error: Quote action did not render a quote container.'
            );
            await editorBody.press('Control+End');
            await editorBody.press('Enter');
            await editorBody.type(message);
            return;
        }

        const textEditor = root.locator('textarea[name="message"]:visible');
        assert.strictEqual(await textEditor.count(), 1, 'Assertion Error: Visible quoted post editor did not render.');
        const existingSource = await textEditor.inputValue();
        const hiddenQuote = root.locator('input[name="noticetrimstr"]');
        const quoteSource = existingSource || (await hiddenQuote.count() ? await hiddenQuote.inputValue() : '');
        assert.match(quoteSource, /\[quote(?:=[^\]]+)?\][\s\S]*\[\/quote\]/i, 'Assertion Error: Quote action did not insert BBCode quote markup.');
        assert.ok(quoteSource.includes(quotedText), 'Assertion Error: Quote BBCode did not preserve the quoted post text.');
        await textEditor.fill(existingSource ? `${existingSource}\n${message}` : message);
    };
    const solveSecurityQuestion = async (targetPage = page, root = targetPage) => {
        const input = root.locator('input[name*="secanswer"]:visible');
        let count = await input.count();
        if(!count && await root.locator('[id^="secqaa_q"]').count()) {
            await input.waitFor({ state: 'visible', timeout: 5000 });
            count = await input.count();
        }
        if(!count) {
            return false;
        }

        assert.strictEqual(count, 1, 'Assertion Error: More than one visible security-answer input rendered.');
        const hashInput = root.locator('input[name="secqaahash"]');
        assert.strictEqual(await hashInput.count(), 1, 'Assertion Error: Security-question hash input did not render.');
        const hash = await hashInput.inputValue();
        assert.ok(hash, 'Assertion Error: Security-question hash is empty.');
        await input.fill('2');
        const [response] = await Promise.all([
            targetPage.waitForResponse(item =>
                item.url().includes('misc.php?mod=secqaa') &&
                item.url().includes('action=check')
            ),
            input.press('Tab')
        ]);
        const result = await response.text();
        assert.ok(result.includes('succeed'), `Assertion Error: Security answer was rejected. Response: ${result}`);
        await targetPage.locator(`#checksecqaaverify_${hash} .fico-check_right`).waitFor({
            state: 'visible',
            timeout: 5000
        });
        return true;
    };
    const sendPrivateMessage = async (senderPage, recipientUid, message) => {
        await senderPage.goto(`http://127.0.0.1:8080/home.php?mod=space&uid=${recipientUid}&do=profile`);
        await senderPage.waitForLoadState('domcontentloaded');
        const sendPmLink = senderPage.locator(`#a_sendpm_${recipientUid}`);
        assert.strictEqual(await sendPmLink.count(), 1, 'Assertion Error: Send message link did not render on the space page.');
        const showmsgResponsePromise = senderPage.waitForResponse(response =>
            response.url().includes('home.php?mod=spacecp&ac=pm&op=showmsg')
        );
        await sendPmLink.click();
        const showmsgResponse = await showmsgResponsePromise;
        assert.ok(showmsgResponse.ok(), `Assertion Error: PM float window load failed with HTTP ${showmsgResponse.status()}.`);
        const floatForm = senderPage.locator('#fwin_showMsgBox form[id^="pmform_"]:visible');
        await floatForm.waitFor({ state: 'visible', timeout: 10000 });
        await senderPage.evaluate(() => {
            if (typeof refresh !== 'undefined') {
                refresh = false;
            }
            if (typeof refreshHandle !== 'undefined') {
                window.clearInterval(refreshHandle);
            }
        });
        const messageInput = floatForm.locator('textarea[name="message"]');
        const submitButton = floatForm.locator('#pmsubmit_btn');
        assert.strictEqual(await messageInput.count(), 1, 'Assertion Error: PM message field in float window did not render.');
        assert.strictEqual(await submitButton.count(), 1, 'Assertion Error: PM submit button in float window did not render.');
        await messageInput.fill(message);
        const msgListPmd = senderPage.locator('#fwin_showMsgBox #msglist li .pmd');
        const beforeMsgCount = await msgListPmd.count();
        const responsePromise = senderPage.waitForResponse(response =>
            response.request().method() === 'POST' &&
            response.url().includes('home.php?mod=spacecp&ac=pm&op=send')
        );
        await submitButton.click();
        const response = await responsePromise;
        const status = response.status();
        let responseText = '';
        if (status < 300 || status >= 400) {
            try {
                responseText = await response.text();
            } catch (e) {
                responseText = `[Failed to read body: ${e.message}]`;
            }
        } else {
            responseText = `[Redirect response to ${response.headers()['location'] || 'unknown'}]`;
        }
        assert.ok(response.ok() || (status >= 300 && status < 400), `Assertion Error: PM send request failed: status=${status}; body=${responseText.slice(0, 2000)}`);
        await senderPage.waitForFunction(
            ([before, text]) => {
                const pmds = Array.from(document.querySelectorAll('#fwin_showMsgBox #msglist li .pmd'));
                const matching = pmds.filter(el => el.textContent.includes(text));
                return pmds.length === before + 1 && matching.length === 1;
            },
            [beforeMsgCount, message],
            { timeout: 10000 }
        );
    };
    const openPmFromNotice = async (targetPage, targetUid) => {
        await targetPage.goto('http://127.0.0.1:8080/forum.php');
        await targetPage.waitForLoadState('domcontentloaded');
        const beforePromptState = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT newprompt, newpm FROM pre_common_member WHERE uid='${targetUid}';"`).toString().trim();
        const [beforePrompt, beforePm] = beforePromptState.split('\t').map(value => parseInt(value || '0', 10));
        assert.ok(beforePrompt > 0, 'Assertion Error: Test user did not have an unread notification before opening the notice menu.');
        const x5Notice = targetPage.locator('.header-notice:has(.notice-dropdown)');
        let pmLink;
        if(await x5Notice.count()) {
            const noticeBadge = x5Notice.locator('.notice-icon > .dot:not(.dot-pm)');
            await noticeBadge.waitFor({ state: 'visible', timeout: 10000 });
            assert.strictEqual(
                (await noticeBadge.textContent()).trim(),
                String(beforePrompt > 99 ? 99 : beforePrompt),
                'Assertion Error: X5 bell badge must show notice unread (newprompt) only, not newprompt+newpm.'
            );
            const noticeResponse = targetPage.waitForResponse(response =>
                response.url().includes('forum.php?mod=ajax&action=markAsRead') &&
                response.request().method() === 'GET'
            );
            await x5Notice.locator('.notice-icon').hover();
            const response = await noticeResponse;
            assert.ok(response.ok(), `Assertion Error: X5 notice request failed with HTTP ${response.status()}.`);
            const noticeItems = targetPage.locator('#myprompt_menu li');
            await noticeItems.first().waitFor({ state: 'visible', timeout: 10000 });
            assert.ok(await noticeItems.count() > 0, 'Assertion Error: X5 notice dropdown did not render notification entries.');
            await targetPage.waitForFunction(expectedPm => {
                const noticeDot = document.querySelector('.header-notice .notice-icon > .dot:not(.dot-pm)');
                const pmPip = document.querySelector('.header-notice .notice-icon > .dot-pm, .header-notice .notice-item .dot');
                return !noticeDot && (expectedPm > 0 ? !!pmPip : true);
            }, beforePm, { timeout: 10000 });
            await targetPage.screenshot({ path: 'screenshot_desktop_notice_dropdown.png' });
            pmLink = targetPage.locator('.header-notice:has(.notice-dropdown) .notice-dropdown a[href*="home.php?mod=space&do=pm"]');
        } else {
            const noticeLink = targetPage.locator('#myprompt');
            assert.strictEqual(await noticeLink.count(), 1, 'Assertion Error: Notice control did not render.');
            const noticeResponse = targetPage.waitForResponse(response =>
                response.url().includes('forum.php?mod=ajax&action=markAsRead') &&
                response.request().method() === 'GET'
            );
            await noticeLink.hover();
            const response = await noticeResponse;
            assert.ok(response.ok(), `Assertion Error: Notice request failed with HTTP ${response.status()}.`);
            await targetPage.locator('#myprompt_menu').waitFor({ state: 'visible', timeout: 10000 });
            await targetPage.screenshot({ path: 'screenshot_desktop_notice_dropdown.png' });
            pmLink = targetPage.locator('#myprompt_menu a#pm_ntc, #pm_ntc');
            assert.strictEqual(
                await noticeLink.evaluate(element => !element.classList.contains('new') && !/\(\s*\d+\s*\)/.test(element.textContent)),
                true,
                'Assertion Error: Default notice control still displayed an unread notification badge.'
            );
            if(beforePm > 0) {
                assert.match(
                    (await targetPage.locator('#pm_ntc').getAttribute('class')) || '',
                    /\bnew\b/,
                    'Assertion Error: After markAsRead the default PM control must keep unread private-message state; leftover newpm is not notice unread.'
                );
            }
        }
        const afterPromptState = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT newprompt, newpm FROM pre_common_member WHERE uid='${targetUid}';"`).toString().trim();
        const [afterPrompt, afterPm] = afterPromptState.split('\t').map(value => parseInt(value || '0', 10));
        assert.strictEqual(afterPrompt, 0, 'Assertion Error: Opening the notice menu did not mark the notification as read.');
        assert.strictEqual(afterPm, beforePm, 'Assertion Error: Opening the notice menu unexpectedly changed the unread PM count.');
        await pmLink.waitFor({ state: 'visible', timeout: 10000 });
        await Promise.all([
            targetPage.waitForURL(url => url.href.includes('home.php?mod=space&do=pm')),
            pmLink.click()
        ]);
        await targetPage.waitForLoadState('networkidle');
    };
    console.log("Starting functional tests...");

    try {
        const timestamp = Math.floor(Date.now() / 1000).toString().slice(-6);
        const username = 'u' + timestamp;
        const email = username + '@example.com';
        const password = 'Testpassword123!';

        console.log("Phase 1: Unprivileged User Registration and Posting");

        console.log("Testing UI Registration...");
        await page.goto('http://127.0.0.1:8080/member.php?mod=register');
        await page.waitForLoadState('domcontentloaded');

        const registrationForm = page.locator('#registerform');
        assert.strictEqual(await registrationForm.count(), 1, 'Assertion Error: Desktop registration form did not render.');
        // reginput can rename the DOM id and name; the first text field is the username.
        const usernameInput = registrationForm.locator('input[name="username"], input[type="text"]').first();
        assert.ok(await usernameInput.count() > 0, 'Assertion Error: Desktop registration username field did not render.');
        await usernameInput.fill(username);

        const passwordInputs = registrationForm.locator('input[type="password"]');
        assert.strictEqual(await passwordInputs.count(), 2, 'Assertion Error: Desktop registration password and confirmation fields did not render.');
        await passwordInputs.nth(0).fill(password);
        await passwordInputs.nth(1).fill(password);
        const emailInput = registrationForm.locator('input[name="email"], input[type="email"]');
        assert.strictEqual(await emailInput.count(), 1, 'Assertion Error: Desktop registration email field did not render.');
        await emailInput.fill(email);

        const agreeCheckbox = registrationForm.locator('input[name="agree"]');
        if (await agreeCheckbox.count()) await agreeCheckbox.check();

        const secqaaInput = registrationForm.locator('input[name="secanswer"]');
        await secqaaInput.waitFor({ state: 'attached', timeout: 5000 });
        assert.strictEqual(await secqaaInput.count(), 1, 'Assertion Error: Desktop registration security-answer field did not render.');
        const secqaaQuestion = registrationForm.locator('span[id^="secqaa_"]');
        await secqaaQuestion.waitFor({ state: 'visible', timeout: 5000 });
        assert.ok(
            (await secqaaQuestion.innerText()).includes('1+1=?'),
            'Assertion Error: Desktop registration security question was not visible.'
        );
        const secqaaHash = await registrationForm.locator('input[name="secqaahash"]').inputValue();
        const secqaaCookies = await context.cookies();
        const secqaaCookie = secqaaCookies.find(cookie => cookie.name.endsWith('secqaa' + secqaaHash));
        assert.ok(secqaaCookie, `Assertion Error: Desktop registration security-answer cookie was not set for ${secqaaHash}.`);
        const secqaaSsid = secqaaCookie.value.split('.')[0];
        const secqaaCode = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT code FROM pre_common_seccheck WHERE ssid='${secqaaSsid}';"`).toString().trim();
        const secqaaRows = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT CONCAT(ssid, ':', HEX(code), ':', succeed, ':', verified) FROM pre_common_seccheck ORDER BY ssid;"`).toString().trim();
        assert.strictEqual(secqaaCode, 'c81e72', `Assertion Error: Desktop registration security-answer challenge stored an unexpected code; cookie=${secqaaCookie.value}; rows=${secqaaRows}.`);
        await secqaaInput.fill('2');
        const [secqaaResponse] = await Promise.all([
            page.waitForResponse(response =>
                response.url().includes('misc.php?mod=secqaa') &&
                response.url().includes('action=check')
            ),
            secqaaInput.press('Tab')
        ]);
        const secqaaResult = await secqaaResponse.text();
        assert.ok(
            secqaaResult.includes('succeed'),
            `Assertion Error: Desktop registration security answer was rejected. Response: ${secqaaResult}`
        );

        const regSubmitBtn = registrationForm.locator('#registerformsubmit');
        assert.strictEqual(await regSubmitBtn.count(), 1, 'Assertion Error: Desktop registration submit button did not render.');
        const registrationAnswer = await registrationForm.locator('input[name="secanswer"]').inputValue();
        const registrationHash = await registrationForm.locator('input[name="secqaahash"]').inputValue();
        assert.strictEqual(registrationAnswer, '2', 'Assertion Error: Desktop registration form omitted the security answer.');
        assert.ok(registrationHash, 'Assertion Error: Desktop registration form omitted the security-answer hash.');
        await page.screenshot({ path: 'screenshot_desktop_registration_filled.png', fullPage: true });

        const [registrationRequest] = await Promise.all([
            page.waitForRequest(request =>
                request.method() === 'POST' &&
                request.url().includes('member.php?mod=register')
            ),
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            regSubmitBtn.click()
        ]);
        assert.ok(registrationRequest.postData(), 'Assertion Error: Desktop registration POST had no request body.');
        await page.waitForTimeout(1000);

        console.log("Checking if user exists in DB...");
        const dbCheck = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT COUNT(*) FROM pre_common_member WHERE username='" + username + "';\"").toString().trim();
        console.log("DB count for user:", dbCheck);

        if (dbCheck !== '1') {
            console.log("Registration failed. Page source:");
            console.log(await page.innerHTML('body'));
        }
        assert.strictEqual(dbCheck, '1', 'Assertion Error: Registered user does not exist in database.');

        await page.waitForURL(url => url.pathname === '/' || url.href.includes('home.php?mod=spacecp'));
        const spaceUrl = await page.url();
        const registeredUrl = new URL(spaceUrl);
        assert.ok((registeredUrl.pathname === '/' || spaceUrl.includes('mod=spacecp')) && !spaceUrl.includes('mod=logging'), 'Assertion Error: Registration did not establish an authenticated session.');
        const domContent = await page.textContent('body');
        assert.ok(domContent.includes(username), 'Assertion Error: Registered username was not rendered in the authenticated account page.');

        console.log("Testing login with the registered email address...");
        const emailLoginContext = await browser.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            extraHTTPHeaders: { 'Accept-Language': 'en-US,en;q=0.9' }
        });
        const emailLoginPage = await emailLoginContext.newPage();
        try {
            await emailLoginPage.goto('http://127.0.0.1:8080/member.php?mod=logging&action=login');
            await emailLoginPage.waitForLoadState('domcontentloaded');
            const emailLoginForm = emailLoginPage.locator('form[id^="loginform_"]:visible');
            assert.strictEqual(await emailLoginForm.count(), 1, 'Assertion Error: Email login form did not render.');
            assert.strictEqual(
                await emailLoginForm.locator('input[name="fastloginfield"][value="auto"]').count(),
                1,
                'Assertion Error: Login form did not enable automatic username/email detection.'
            );
            await emailLoginForm.locator('input[name="username"]').fill(email);
            await emailLoginForm.locator('input[name="password"]').fill(password);
            await solveSecurityQuestion(emailLoginPage, emailLoginForm);
            const emailLoginSubmit = emailLoginForm.locator('button[name="loginsubmit"], button[type="submit"], input[type="submit"]');
            assert.strictEqual(await emailLoginSubmit.count(), 1, 'Assertion Error: Email login submit control did not render.');
            const [emailLoginResponse] = await Promise.all([
                emailLoginPage.waitForResponse(response =>
                    response.request().method() === 'POST' &&
                    response.url().includes('member.php?mod=logging')
                ),
                emailLoginSubmit.click()
            ]);
            assert.ok(
                emailLoginResponse.ok() || (emailLoginResponse.status() >= 300 && emailLoginResponse.status() < 400),
                `Assertion Error: Email login POST failed with HTTP ${emailLoginResponse.status()}.`
            );
            await emailLoginPage.waitForTimeout(1000);
            await emailLoginPage.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
            await emailLoginPage.waitForLoadState('domcontentloaded');
            assert.strictEqual(
                await emailLoginPage.locator('form[id^="loginform_"]:visible').count(),
                0,
                'Assertion Error: Email login did not establish an authenticated session.'
            );
            assert.ok(
                (await emailLoginPage.textContent('body')).includes(username),
                'Assertion Error: Email login authenticated the wrong account or did not render the username.'
            );
        } finally {
            await emailLoginContext.close();
        }
        report += '### 1. User Registration & Login\n- **Status**: Checked\n- **Username**: ' + username + '\n- **Email login**: Verified\n- **Filled Registration Form**: `screenshot_desktop_registration_filled.png`\n\n';

        // Pre-setup Avatar before advanced editor screenshot & posting tests
        console.log("Setting up user avatar via UI...");

        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp&ac=avatar');
        await page.waitForLoadState('networkidle');

        const avatarFixture = 'static/image/smiley/BQ2/alu1.jpg';
        const avatarInputs = page.locator('.choose-file');
        assert.strictEqual(await avatarInputs.count(), 3, 'Assertion Error: HTML5 avatar controls did not render.');
        assert.ok(fs.existsSync(avatarFixture), 'Assertion Error: Avatar fixture is missing.');
        for(let i = 0; i < 3; i++) {
            await avatarInputs.nth(i).setInputFiles(avatarFixture);
        }
        const avatarSubmit = page.locator('.submit-btn');
        assert.strictEqual(await avatarSubmit.count(), 1, 'Assertion Error: Avatar submit control did not render.');
        const [avatarResponse] = await Promise.all([
            page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('/api/avatar/index.php') &&
                response.url().includes('a=rectavatar')
            ),
            avatarSubmit.click()
        ]);
        const avatarPostData = avatarResponse.request().postData() || '';
        assert.ok(
            avatarPostData.includes('name="avatar1"') &&
            avatarPostData.includes('name="avatar2"') &&
            avatarPostData.includes('name="avatar3"') &&
            avatarPostData.includes('name="formhash"'),
            'Assertion Error: Avatar form did not submit all image payloads and formhash.'
        );
        assert.ok(
            avatarResponse.ok() || (avatarResponse.status() >= 300 && avatarResponse.status() < 400),
            `Assertion Error: Avatar upload POST failed with HTTP ${avatarResponse.status()}.`
        );
        await page.waitForTimeout(500);

        const userUid = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT uid FROM pre_common_member WHERE username='" + username + "';\"").toString().trim();

        const avatarStatus = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT avatarstatus FROM pre_common_member WHERE uid='${userUid}';"`).toString().trim();
        assert.strictEqual(avatarStatus, '1', 'Assertion Error: User avatarstatus in database was not 1.');

        console.log("Testing Desktop Forum Front Page (forum.php)...");
        await page.goto('http://127.0.0.1:8080/forum.php');
        await page.waitForLoadState('networkidle');
        assert.strictEqual(await page.locator('#ct').count(), 1, 'Assertion Error: Desktop forum index content container did not render.');
        assert.ok(await page.locator('#category_grid, .fl').count() > 0, 'Assertion Error: Desktop forum index did not render a forum list.');
        await page.screenshot({ path: 'screenshot_desktop_forum_index.png', fullPage: true });
        console.log("✅ Desktop Forum Front Page loaded successfully.");
        report += '### Desktop Forum Front Page (forum.php)\n- **Status**: Checked\n- **Front Page Load**: Success\n- **Screenshot**: `screenshot_desktop_forum_index.png`\n\n';

        console.log('Testing desktop online member list toggle...');
        const onlinePanel = page.locator('#online_index_panel');
        const onlineToggle = page.locator('a[href="#online"][onclick*="online_index_panel"]:visible').first();
        assert.strictEqual(await onlinePanel.count(), 1, 'Assertion Error: Desktop online member panel did not render.');
        if(await onlineToggle.count() === 0) {
            console.log('Online member toggle is disabled by the current session settings; skipping interaction check.');
            report += '- **Online Member List**: Panel rendered; toggle disabled by session settings\n\n';
        } else {
            // Verify that the cookie-backed collapsed state survives a full page reload.
            if(await onlinePanel.isVisible()) {
                await onlineToggle.click();
                await onlinePanel.waitFor({ state: 'hidden' });
            }
            assert.ok(await onlinePanel.isHidden(), 'Assertion Error: Online member panel did not collapse.');
            await page.reload({ waitUntil: 'networkidle' });
            const reloadedOnlinePanel = page.locator('#online_index_panel');
            assert.ok(await reloadedOnlinePanel.isHidden(), 'Assertion Error: Collapsed online member panel reopened after reload.');

            const reloadedOnlineToggle = page.locator('a[href="#online"][onclick*="online_index_panel"]:visible').first();
            const onlineResponsePromise = page.waitForResponse(response =>
                response.request().method() === 'GET' &&
                response.url().includes('forum.php?mod=ajax&action=getOnlineUserListHtml')
            );
            await reloadedOnlineToggle.click();
            const onlineResponse = await onlineResponsePromise;
            assert.ok(onlineResponse.ok(), `Assertion Error: Online member list request failed with HTTP ${onlineResponse.status()}.`);
            await page.waitForFunction(() => {
                const panel = document.getElementById('online_index_panel');
                const list = panel && panel.querySelector('#whosonline_list_container');
                return panel && panel.getAttribute('data-loading') !== '1' &&
                    panel.getAttribute('data-loaded') === '1' && list &&
                    !/Loading\.\.\./i.test(list.textContent);
            }, null, { timeout: 15000 });
            assert.ok(await reloadedOnlinePanel.isVisible(), 'Assertion Error: Online member panel did not open.');
            await page.reload({ waitUntil: 'networkidle' });
            assert.ok(await page.locator('#online_index_panel').isVisible(), 'Assertion Error: Open online member panel collapsed after reload.');
            report += '- **Online Member List**: Toggle and AJAX loading verified\n\n';
        }

        console.log('Testing Pusher leader coordination across tabs...');
        await testPusherLeaderCoordination(browser);

        const requestSubmitMetadata = await page.evaluate(() => {
            const form = document.createElement('form');
            form.action = 'forum.php?mod=post&action=reply';
            document.body.appendChild(form);
            let metadata = '';
            form.addEventListener('submit', event => {
                event.preventDefault();
                metadata = form.querySelector('input[name="pusher_tab_id"]')?.value || '';
            });
            form.requestSubmit();
            form.remove();
            return metadata;
        });
        assert.ok(requestSubmitMetadata, 'Assertion Error: requestSubmit did not add Pusher tab metadata before forum form submission.');
        assert.strictEqual(requestSubmitMetadata, await page.evaluate(() => window.KK_PUSHER_TAB_ID), 'Assertion Error: requestSubmit Pusher metadata did not match the current tab token.');

        console.log("Testing footer locale switcher and localized forum names...");
        const scLocaleLink = page.locator('#lang_selector_dropdown a[href="misc.php?mod=i18n&key=SC"]');
        // Open the dropdown first so the link is clickable
        await page.locator('#lang_select_btn').click();
        assert.strictEqual(await scLocaleLink.count(), 1, 'Assertion Error: DiscuzX5 footer SC locale switch did not render.');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            scLocaleLink.click()
        ]);
        assert.strictEqual(await page.evaluate(() => DISCUZ_I18N), 'SC', 'Assertion Error: Footer locale switch did not select SC.');
        assert.strictEqual(await page.getByText('默认版块', { exact: true }).count(), 1, 'Assertion Error: SC locale switch did not localize the forum name.');

        const enLocaleLink = page.locator('#lang_selector_dropdown a[href="misc.php?mod=i18n&key=EN"]');
        await page.locator('#lang_select_btn').click();
        assert.strictEqual(await enLocaleLink.count(), 1, 'Assertion Error: DiscuzX5 footer EN locale switch did not render.');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            enLocaleLink.click()
        ]);
        assert.strictEqual(await page.evaluate(() => DISCUZ_I18N), 'EN', 'Assertion Error: Footer locale switch did not restore EN.');
        assert.strictEqual(await page.getByText('Default Forum', { exact: true }).count(), 1, 'Assertion Error: EN locale switch did not localize the forum name.');

        const tcContext = await browser.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            extraHTTPHeaders: {
                'Accept-Language': 'zh-CN;q=0.6,zh-TW;q=0.9,en;q=0.8'
            }
        });
        await stubPusher(tcContext);
        const tcPage = await tcContext.newPage();
        await tcPage.goto('http://127.0.0.1:8080/forum.php', { waitUntil: 'networkidle' });
        assert.strictEqual(await tcPage.evaluate(() => DISCUZ_I18N), 'TC', 'Assertion Error: Accept-Language did not default a clean browser to TC.');
        assert.strictEqual(await tcPage.getByText('默認版塊', { exact: true }).count(), 1, 'Assertion Error: Accept-Language TC default did not localize the forum name.');
        await tcContext.close();
        report += '### Footer Locale Switcher\n- **Status**: Checked\n- **Forum Names**: SC and EN switch with the UI locale\n- **Browser Default**: Weighted zh-TW Accept-Language selects TC\n\n';

        // Discover a real postable sub-board (type='forum') — never a group (type='group').
        const forumFid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT fid FROM pre_forum_forum WHERE type='forum' LIMIT 1;"`).toString().trim();
        assert.ok(forumFid, 'Assertion Error: No postable sub-board (type=forum) found in pre_forum_forum.');

        console.log("Attempting to post normal thread as unprivileged user...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=forumdisplay&fid=${forumFid}`);
        await page.waitForLoadState('networkidle');
        const postNewThreadBtn = page.locator('#newspecial');
        assert.strictEqual(await postNewThreadBtn.count(), 1, 'Assertion Error: Desktop new-thread control did not render.');
        await postNewThreadBtn.click();
        await page.waitForLoadState('networkidle');

        console.log("Capturing Advanced Editor Screenshot...");
        await page.screenshot({ path: 'screenshot_advanced_editor.png', fullPage: true });

        const subjectInput = page.locator('input[name="subject"]');
        assert.strictEqual(await subjectInput.count(), 1, 'Assertion Error: Desktop thread subject field did not render.');
        await subjectInput.fill(standardSubject);

        const smilieButton = page.locator('#e_sml');
        assert.strictEqual(await smilieButton.count(), 1, 'Assertion Error: Desktop smiley control did not render.');
        await smilieButton.click();
        const firstSmilie = page.locator('#smiliesdiv_data td[id*="smilie_"]').first();
        await firstSmilie.waitFor({ state: 'visible' });
        await firstSmilie.click();
        const editorAfterSmilie = page.locator('textarea[name="message"]:visible');
        assert.ok(
            (await editorAfterSmilie.inputValue()).length > 0,
            'Assertion Error: Clicking a smiley did not insert its code into the editor.'
        );

		await fillPostEditor('Body text from unprivileged account.');

        await solveSecurityQuestion(page);

        const postSubmitBtn = page.locator('button[name="topicsubmit"][type="submit"]');
        assert.strictEqual(await postSubmitBtn.count(), 1, 'Assertion Error: Desktop thread submit button did not render.');
        const expectedPusherTabId = await page.evaluate(() => window.KK_PUSHER_TAB_ID || '');
        assert.ok(expectedPusherTabId, 'Assertion Error: Desktop forum page did not provide a Pusher tab token.');
        const [threadPostResponse] = await Promise.all([
            page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('forum.php?mod=post')
            ),
            postSubmitBtn.click()
        ]);
        assert.ok(
            threadPostResponse.ok() || (threadPostResponse.status() >= 300 && threadPostResponse.status() < 400),
            `Assertion Error: Desktop thread POST failed with HTTP ${threadPostResponse.status()}.`
        );
        const threadPostBody = threadPostResponse.request().postData() || '';
        assert.ok(threadPostBody.includes('pusher_tab_id') && threadPostBody.includes(expectedPusherTabId), 'Assertion Error: Desktop thread POST did not send the Pusher tab token to PHP.');

        console.log("Checking if posted thread exists in DB...");
        const threadDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_thread WHERE subject='${standardSubject}';"`).toString().trim();
        console.log("DB count for thread:", threadDbCheck);
        assert.ok(parseInt(threadDbCheck, 10) >= 1, 'Assertion Error: Normal user thread post was not found in database.');

        const tidOutput = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tid FROM pre_forum_thread WHERE subject='${standardSubject}' ORDER BY tid DESC LIMIT 1;"`).toString().trim();
        assert.match(tidOutput, /^\d+$/, 'Assertion Error: Created thread ID was not found.');

        await page.waitForURL(new RegExp(`forum\\.php\\?mod=viewthread&tid=${tidOutput}(&|$)`));

        const currentUrl = page.url();
        const postContent = await page.textContent('body');

        assert.match(currentUrl, new RegExp(`mod=viewthread&tid=${tidOutput}(&|$)`), `Assertion Error: Normal user posting did not redirect to the created thread (tid ${tidOutput}).`);
        assert.ok(postContent.includes(standardSubject), 'Assertion Error: Created thread subject was not rendered after submission.');
        const creditNames = await page.evaluate(() => window.creditnotice || '');
        assert.match(creditNames, /1\|EXP\|/, 'Assertion Error: Credit prompt metadata did not localize extcredits1 as EXP.');
        assert.match(creditNames, /2\|Karma\|/, 'Assertion Error: Credit prompt metadata did not localize extcredits2 as Karma.');
        const creditPrompt = page.locator('#creditpromptdiv');
        await creditPrompt.waitFor({ state: 'visible', timeout: 5000 });
        const creditPromptText = (await creditPrompt.textContent()).trim();
        assert.ok(creditPromptText.includes('EXP'), 'Assertion Error: Credit update prompt did not show EXP.');
        assert.ok(!creditPromptText.includes('|'), 'Assertion Error: Credit update prompt exposed internal credit-name separators.');
        report += `### 2. Unprivileged User Posting\n- **Status**: Checked\n- **Thread Created**: ${standardSubject} (tid ${tidOutput})\n\n`;

        const literalSearchKeywords = ['literal-ci%_marker', 'literal-ci\\%_marker', 'literal-ci*marker'];
        const literalSearchSubject = `${standardSubject} ${literalSearchKeywords.join(' ')}`;
        const decoyTid = '4';
        const decoySubjectB64 = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT TO_BASE64(subject) FROM pre_forum_thread WHERE tid=${decoyTid};"`).toString().trim();
        assert.ok(decoySubjectB64, 'Assertion Error: Forum search wildcard fixture could not find its decoy thread.');
        const decoySubject = 'literal-ciXabcYmarker literal-ci%Xmarker';
        const originalSubjectB64 = Buffer.from(standardSubject).toString('base64');
        const literalSearchSubjectB64 = Buffer.from(literalSearchSubject).toString('base64');
        const decoySubjectB64New = Buffer.from(decoySubject).toString('base64');
        execSync(`sudo mysql -u root ultrax -e "UPDATE pre_forum_thread SET subject=CONVERT(FROM_BASE64('${literalSearchSubjectB64}') USING utf8mb4) WHERE tid=${tidOutput}; UPDATE pre_forum_post SET subject=CONVERT(FROM_BASE64('${literalSearchSubjectB64}') USING utf8mb4) WHERE tid=${tidOutput} AND first=1; UPDATE pre_forum_thread SET subject=CONVERT(FROM_BASE64('${decoySubjectB64New}') USING utf8mb4) WHERE tid=${decoyTid}; UPDATE pre_forum_post SET subject=CONVERT(FROM_BASE64('${decoySubjectB64New}') USING utf8mb4) WHERE tid=${decoyTid} AND first=1;"`);
        try {
            // Search all literal variants in one OR query so the site's search
            // throttle does not turn the second assertion into a rate-limit test.
            const literalSearchKeyword = literalSearchKeywords.join('|');
            const searchUrl = new URL('http://127.0.0.1:8080/search.php?mod=forum');
            searchUrl.searchParams.set('srchtxt', literalSearchKeyword);
            searchUrl.searchParams.set('searchsubmit', 'yes');
            await page.goto(searchUrl.toString(), { waitUntil: 'networkidle' });
            assert.ok(new URL(page.url()).searchParams.has('searchid'), `Assertion Error: Forum search did not create a result set for ${literalSearchKeyword}.`);
            const resultThreadIds = await page.locator('#threadlist a').evaluateAll(links => links.map(link => {
                const url = new URL(link.href);
                return url.searchParams.get('tid') || url.searchParams.get('ptid');
            }).filter(Boolean));
            assert.ok(resultThreadIds.includes(String(tidOutput)), `Assertion Error: Forum search did not return the literal target for ${literalSearchKeyword}.`);
            assert.ok(!resultThreadIds.includes(decoyTid), `Assertion Error: Forum search treated wildcard characters as patterns for ${literalSearchKeyword}.`);
        } finally {
            execSync(`sudo mysql -u root ultrax -e "UPDATE pre_forum_thread SET subject=CONVERT(FROM_BASE64('${originalSubjectB64}') USING utf8mb4) WHERE tid=${tidOutput}; UPDATE pre_forum_post SET subject=CONVERT(FROM_BASE64('${originalSubjectB64}') USING utf8mb4) WHERE tid=${tidOutput} AND first=1; UPDATE pre_forum_thread SET subject=CONVERT(FROM_BASE64('${decoySubjectB64}') USING utf8mb4) WHERE tid=${decoyTid}; UPDATE pre_forum_post SET subject=CONVERT(FROM_BASE64('${decoySubjectB64}') USING utf8mb4) WHERE tid=${decoyTid} AND first=1;"`);
        }
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);

        // Reply to Thread
            console.log("Attempting to reply to thread...");
            const desktopReplyBtn = page.locator('#post_reply');
            assert.strictEqual(await desktopReplyBtn.count(), 1, 'Assertion Error: Desktop reply control did not render.');
            await desktopReplyBtn.click();
            const replyForm = page.locator('#fwin_reply form:visible');
            await replyForm.waitFor({ state: 'visible' });
            await fillPostEditor('Reply text from unprivileged account.', page, replyForm);
            await solveSecurityQuestion(page, replyForm);
            const replyBtn = replyForm.locator('#postsubmit, button[name="replysubmit"]');
            assert.strictEqual(await replyBtn.count(), 1, 'Assertion Error: Desktop reply submit button did not render.');
            const [replyResponse] = await Promise.all([
                page.waitForResponse(response =>
                    response.request().method() === 'POST' &&
                    response.url().includes('forum.php?mod=post')
                ),
                replyBtn.click()
            ]);
            assert.ok(
                replyResponse.ok() || (replyResponse.status() >= 300 && replyResponse.status() < 400),
                `Assertion Error: Desktop reply POST failed with HTTP ${replyResponse.status()}.`
            );
            await page.waitForURL(new RegExp(`mod=viewthread&tid=${tidOutput}`));
            await page.waitForFunction(
                message => document.body && document.body.innerText.includes(message),
                'Reply text from unprivileged account.'
            );

            console.log("Checking if reply exists in DB...");
            const replyDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_post WHERE tid='${tidOutput}' AND first=0;"`).toString().trim();
            assert.ok(parseInt(replyDbCheck, 10) >= 1, 'Assertion Error: Reply post was not found in database.');
            report += '### 3. Unprivileged User Reply\n- **Status**: Checked\n- **Reply Count**: ' + replyDbCheck + '\n\n';

            console.log("Testing deleted reply revision restore...");
            const deletableReplyPid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${tidOutput}' AND first=0 AND authorid='${userUid}' AND message='Reply text from unprivileged account.' ORDER BY pid DESC LIMIT 1;"`).toString().trim();
            assert.match(deletableReplyPid, /^\d+$/, 'Assertion Error: Reply for deletion test was not found.');
            await page.goto(`http://127.0.0.1:8080/forum.php?mod=misc&action=postdelete&tid=${tidOutput}&pid=${deletableReplyPid}`);
            await page.waitForLoadState('networkidle');
            const deleteForm = page.locator('#postdeleteform');
            assert.strictEqual(await deleteForm.count(), 1, 'Assertion Error: Post deletion confirmation form did not render.');
            const deleteSubmit = deleteForm.locator('#postdeletesubmit');
            assert.strictEqual(await deleteSubmit.count(), 1, 'Assertion Error: Post deletion confirmation button did not render.');
            const expectedDeletePusherTabId = await page.evaluate(() => window.KK_PUSHER_TAB_ID || '');
            assert.ok(expectedDeletePusherTabId, 'Assertion Error: Post deletion page did not provide a Pusher tab token.');
            const [deleteRequest] = await Promise.all([
                page.waitForRequest(request =>
                    request.method() === 'POST' &&
                    request.url().includes('forum.php?mod=misc&action=postdelete')
                ),
                deleteSubmit.click()
            ]);
            const deleteRequestBody = deleteRequest.postData() || '';
            assert.ok(deleteRequestBody.includes('pusher_tab_id') && deleteRequestBody.includes(expectedDeletePusherTabId), 'Assertion Error: Post deletion request did not send the Pusher tab token to PHP.');
            await page.waitForURL(new RegExp(`mod=viewthread&tid=${tidOutput}`));

            const deletedPostCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_post WHERE tid='${tidOutput}' AND pid='${deletableReplyPid}';"`).toString().trim();
            assert.strictEqual(deletedPostCheck, '0', 'Assertion Error: Deleted reply still exists in the post table.');
            const deleteLogId = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT editid FROM pre_forum_editlog WHERE tid='${tidOutput}' AND pid='${deletableReplyPid}' AND action='delete' AND old_message='Reply text from unprivileged account.' ORDER BY editid DESC LIMIT 1;"`).toString().trim();
            assert.match(deleteLogId, /^\d+$/, 'Assertion Error: Deleted reply did not create a deletion revision.');

            const deletedEditLogUrl = `forum.php?mod=misc&action=editlog&tid=${tidOutput}&pid=${deletableReplyPid}`;
            await page.goto(`http://127.0.0.1:8080/${deletedEditLogUrl}`);
            await page.waitForLoadState('networkidle');
            const deletedHistory = page.locator('.revision-window');
            assert.strictEqual(await deletedHistory.count(), 1, 'Assertion Error: Deleted reply revision history did not render.');
            assert.ok((await deletedHistory.textContent()).includes('Reply text from unprivileged account.'), 'Assertion Error: Deleted reply content was not present in revision history.');
            const restoreButton = deletedHistory.locator('#revision_restore');
            assert.strictEqual(await restoreButton.isEnabled(), true, 'Assertion Error: Deleted reply revision was not restorable.');
            page.once('dialog', dialog => dialog.accept());
            const [restoreResponse] = await Promise.all([
                page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('action=editlog')),
                restoreButton.click()
            ]);
            assert.ok(restoreResponse.ok() || (restoreResponse.status() >= 300 && restoreResponse.status() < 400), `Assertion Error: Deleted reply restore POST failed with HTTP ${restoreResponse.status()}.`);
            const restoredPostCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_post WHERE tid='${tidOutput}' AND pid='${deletableReplyPid}' AND message='Reply text from unprivileged account.';"`).toString().trim();
            assert.strictEqual(restoredPostCheck, '1', 'Assertion Error: Deleted reply was not restored with its original content and PID.');
            await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await page.waitForLoadState('domcontentloaded');
            await page.waitForFunction(
                message => document.body && document.body.innerText.includes(message),
                'Reply text from unprivileged account.'
            );
            report += '### Deleted Reply Revision Restore\n- **Status**: Checked\n- **Deletion Log**: Success\n- **Restore**: Success\n\n';

            // --- Test: Comment on first floor ---
            console.log("Posting comment on first floor via UI...");
            const firstFloorPid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${tidOutput}' AND first=1 LIMIT 1;"`).toString().trim();
            assert.ok(firstFloorPid, 'Assertion Error: First floor post ID was not found.');

            await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await page.waitForLoadState('networkidle');

            const firstFloorCommentBtn = page.locator(`a.cmmnt[href*="pid=${firstFloorPid}"]`);
            assert.strictEqual(await firstFloorCommentBtn.count(), 1, 'Assertion Error: Comment control did not render for the first floor post.');
            await firstFloorCommentBtn.click();

            const firstFloorCommentForm = page.locator('#fwin_comment form#commentform');
            await firstFloorCommentForm.waitFor({ state: 'visible' });
            const firstFloorCommentMessage = firstFloorCommentForm.locator('#commentmessage');
            const firstFloorSubmitCommentBtn = firstFloorCommentForm.locator('#commentsubmit');
            assert.strictEqual(await firstFloorCommentMessage.count(), 1, 'Assertion Error: First floor comment message input did not render.');
            assert.strictEqual(await firstFloorSubmitCommentBtn.count(), 1, 'Assertion Error: First floor comment submit button did not render.');

            const firstFloorCommentText = 'Test comment on first floor.';
            await firstFloorCommentMessage.fill(firstFloorCommentText);
            await solveSecurityQuestion(page, firstFloorCommentForm);
            const [firstFloorCommentResponse] = await Promise.all([
                page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('mod=post') && response.url().includes('commentsubmit=yes')),
                firstFloorSubmitCommentBtn.click()
            ]);
            assert.ok(firstFloorCommentResponse.ok(), `Assertion Error: First floor comment request failed with HTTP ${firstFloorCommentResponse.status()}.`);

            const firstFloorCommentDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_postcomment WHERE authorid='${userUid}' AND pid='${firstFloorPid}' AND comment='${firstFloorCommentText}';"`).toString().trim();
            assert.strictEqual(firstFloorCommentDbCheck, '1', 'Assertion Error: First floor comment was not created in database.');

            // Navigate back to viewthread to verify and screenshot
            await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await page.waitForLoadState('networkidle');
            assert.ok((await page.textContent('body')).includes(firstFloorCommentText), 'Assertion Error: First floor comment was not rendered in viewthread.');
            await page.screenshot({ path: 'screenshot_desktop_viewthread_commented_first_floor.png' });
            console.log("✅ Comment on first floor posted successfully.");


            // --- Test: Full Advanced Editor ---
            console.log("Testing full advanced editor...");
            const advancedSubject = `Advanced User Thread ${testRunId}`;
            await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=${forumFid}`);
            await page.waitForLoadState('networkidle');
            const advancedForm = page.locator('#postform');
            assert.strictEqual(await advancedForm.count(), 1, 'Assertion Error: Full advanced editor form did not render.');
            await advancedForm.locator('input[name="subject"]').fill(advancedSubject);
            await fillPostEditor('Body text from the full advanced editor.', page, advancedForm);
            await solveSecurityQuestion(page, advancedForm);
            const advancedSubmit = advancedForm.locator('button[name="topicsubmit"]');
            assert.strictEqual(await advancedSubmit.count(), 1, 'Assertion Error: Full advanced editor submit button did not render.');
            const [advancedResponse] = await Promise.all([
                page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('forum.php?mod=post')),
                advancedSubmit.click()
            ]);
            assert.ok(advancedResponse.ok() || (advancedResponse.status() >= 300 && advancedResponse.status() < 400), `Assertion Error: Full advanced editor POST failed with HTTP ${advancedResponse.status()}.`);
            const advancedTid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tid FROM pre_forum_thread WHERE subject='${advancedSubject}' ORDER BY tid DESC LIMIT 1;"`).toString().trim();
            assert.match(advancedTid, /^\d+$/, 'Assertion Error: Full advanced editor thread ID was not found.');
            await page.waitForURL(new RegExp(`forum\\.php\\?mod=viewthread&tid=${advancedTid}(&|$)`));
            const advancedDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_thread WHERE subject='${advancedSubject}';"`).toString().trim();
            assert.strictEqual(advancedDbCheck, '1', 'Assertion Error: Full advanced editor thread was not found in database.');
            report += `### Full Advanced Editor\n- **Status**: Checked\n- **Thread Created**: ${advancedSubject} (tid ${advancedTid})\n\n`;


            // Edit Thread
            console.log("Attempting to edit thread...");
            await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await page.waitForLoadState('networkidle');
            const pidOutput = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${tidOutput}' AND first=1 LIMIT 1;"`).toString().trim();
            assert.match(pidOutput, /^\d+$/, 'Assertion Error: Created thread first-post ID was not found.');
                const editPostBtn = page.locator(`a.editp[href*="action=edit"][href*="pid=${pidOutput}"]`);
                assert.strictEqual(await editPostBtn.count(), 1, 'Assertion Error: Desktop edit control did not render.');
                await editPostBtn.click();
                const editForm = page.locator('#fwin_edit form#postform_edit');
                await editForm.waitFor({ state: 'visible' });
                assert.strictEqual(await editForm.count(), 1, 'Assertion Error: Desktop edit modal did not render its form.');

                const editSubject = editForm.locator('input[name="subject"]');
                assert.strictEqual(await editSubject.count(), 1, 'Assertion Error: Desktop edit subject input did not render.');
                await editSubject.fill(editedStandardSubject);
                const editMessage = editForm.locator('textarea[name="message"]');
                assert.strictEqual(await editMessage.count(), 1, 'Assertion Error: Desktop edit message input did not render.');
                await editMessage.fill('Edited body text from unprivileged account.');
                await solveSecurityQuestion(page, editForm);
                const editBtn = editForm.locator('button[name="editsubmit"]');
                assert.strictEqual(await editBtn.count(), 1, 'Assertion Error: Desktop edit submit button did not render.');

                const [editPostResponse] = await Promise.all([
                    page.waitForResponse(response =>
                        response.request().method() === 'POST' &&
                        response.url().includes('forum.php?mod=post') &&
                        response.url().includes('action=edit')
                    ),
                    editBtn.click()
                ]);
                assert.ok(editPostResponse.ok(), `Assertion Error: Desktop edit POST failed with HTTP ${editPostResponse.status()}.`);

                const editResponseBody = await editPostResponse.text();
                assert.ok(editResponseBody.includes('succeedhandle_edit'), 'Assertion Error: Desktop edit POST response did not invoke the close-window callback succeedhandle_edit.');

                await page.waitForFunction(() => {
                    const modal = document.getElementById('fwin_edit');
                    return !modal || modal.style.display === 'none';
                }, null, { timeout: 5000 });
                assert.match(page.url(), new RegExp(`mod=viewthread&tid=${tidOutput}(&|$)`), 'Assertion Error: Desktop edit submission navigated away from the thread instead of closing the float window via callback.');

                console.log("Checking if edited thread title exists in DB...");
                const editDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_thread WHERE tid='${tidOutput}' AND subject='${editedStandardSubject}';"`).toString().trim();
                assert.strictEqual(editDbCheck, '1', 'Assertion Error: Edited thread title was not updated in database.');
                await page.reload({ waitUntil: 'networkidle' });
                const editedThreadBody = await page.textContent('body');
                assert.ok(editedThreadBody.includes(editedStandardSubject), 'Assertion Error: Edited thread title was not rendered after reload.');
                assert.ok(editedThreadBody.includes('Edited body text from unprivileged account.'), 'Assertion Error: Edited thread body was not rendered after reload.');

                console.log("Testing post revision history as the author...");
                const editHistoryUrl = `forum.php?mod=misc&action=editlog&tid=${tidOutput}&pid=${pidOutput}`;
                const editHistoryLink = page.locator(`a.editlog[href*="action=editlog"][href*="pid=${pidOutput}"]`);
                assert.strictEqual(await editHistoryLink.count(), 1, 'Assertion Error: Author edit-history link did not render.');
                const [editHistoryResponse] = await Promise.all([
                    page.waitForResponse(response => response.url().includes('action=editlog') && response.status() === 200),
                    editHistoryLink.click()
                ]);
                assert.ok(editHistoryResponse.ok(), 'Assertion Error: Author edit-history request failed.');
                const editHistoryModal = page.locator('#fwin_editlog');
                await editHistoryModal.waitFor({ state: 'visible' });
                await page.waitForFunction(message => {
                    const diff = document.querySelector('#fwin_editlog #revision_diff');
                    return diff && diff.textContent.includes(message);
                }, 'Body text from unprivileged account.');
                const editHistoryText = await editHistoryModal.textContent();
                assert.ok(editHistoryText.includes('Body text from unprivileged account.'), 'Assertion Error: Author could not view the previous post content.');

                const editHistoryDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT CONCAT(uid, ':', IF(dateline > 0, '1', '0')) FROM pre_forum_editlog WHERE tid='${tidOutput}' AND pid='${pidOutput}' AND action='edit' AND old_message LIKE '%Body text from unprivileged account.%' ORDER BY editid DESC LIMIT 1;"`).toString().trim();
                assert.strictEqual(editHistoryDbCheck, `${userUid}:1`, 'Assertion Error: Edit history did not persist the editor UID and edit time.');

                console.log("Testing post revision history access control...");
                const guestContext = await browser.newContext();
                await stubPusher(guestContext);
                const guestPage = await guestContext.newPage();
                await guestPage.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
                await guestPage.waitForLoadState('networkidle');
                assert.strictEqual(await guestPage.locator(`a.editlog[href*="action=editlog"][href*="pid=${pidOutput}"]`).count(), 0, 'Assertion Error: Guest saw the author-only edit-history link.');
                await guestPage.goto(`http://127.0.0.1:8080/${editHistoryUrl}`);
                const guestHistoryText = await guestPage.textContent('body');
                assert.ok(guestHistoryText.includes('Only the author and administrators can view post revision history.'), 'Assertion Error: Guest could access post revision history.');
                await guestContext.close();

                console.log("Testing no-op edit does not create a revision...");
                const editlogCountBefore = Number(execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_editlog WHERE tid='${tidOutput}' AND pid='${pidOutput}' AND action='edit';"`).toString().trim());
                await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
                await page.waitForLoadState('networkidle');
                const noopEditBtn = page.locator(`a.editp[href*="action=edit"][href*="pid=${pidOutput}"]`);
                await noopEditBtn.click();
                const noopEditForm = page.locator('#fwin_edit form#postform_edit');
                await noopEditForm.waitFor({ state: 'visible' });
                await solveSecurityQuestion(page, noopEditForm);
                const [noopEditResponse] = await Promise.all([
                    page.waitForResponse(response =>
                        response.request().method() === 'POST' &&
                        response.url().includes('forum.php?mod=post') &&
                        response.url().includes('action=edit')
                    ),
                    noopEditForm.locator('button[name="editsubmit"]').click()
                ]);
                assert.ok(noopEditResponse.ok(), `Assertion Error: No-op edit POST failed with HTTP ${noopEditResponse.status()}.`);
                await page.waitForFunction(() => {
                    const modal = document.getElementById('fwin_edit');
                    return !modal || modal.style.display === 'none';
                }, null, { timeout: 5000 });
                const editlogCountAfter = Number(execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_editlog WHERE tid='${tidOutput}' AND pid='${pidOutput}' AND action='edit';"`).toString().trim());
                assert.strictEqual(editlogCountAfter, editlogCountBefore, 'Assertion Error: A no-op edit unexpectedly created a post revision.');

                report += `### 4. Unprivileged User Edit & Revision History\n- **Status**: Checked\n- **Edited Title**: ${editedStandardSubject}\n- **Author History Access**: Success\n- **Guest Access Denied**: Success\n- **No-Op Edit Skipped Revision**: Success\n\n`;

        console.log("Testing Personal Info Update via spacecp...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp&ac=profile');
        await page.waitForLoadState('networkidle');

        const personalInfoTab = page.locator('a[href*="mod=spacecp"][href*="ac=profile"][href*="op=info"]');
        assert.strictEqual(await personalInfoTab.count(), 1, 'Assertion Error: Personal information profile tab did not render.');
        await Promise.all([
            page.waitForURL(url => url.searchParams.get('op') === 'info'),
            personalInfoTab.click()
        ]);

        const profileForm = page.locator('form[action*="mod=spacecp"]');
        assert.strictEqual(await profileForm.count(), 1, 'Assertion Error: Personal profile form did not render.');
        const customStatusInput = profileForm.locator('input[name="customstatus"]');
        const profileSubmit = profileForm.locator('button[type="submit"], input[type="submit"], #profilesubmitbtn');
        assert.strictEqual(await customStatusInput.count(), 1, 'Assertion Error: Custom status field did not render.');
        assert.strictEqual(await profileSubmit.count(), 1, 'Assertion Error: Personal profile submit control did not render.');
        await customStatusInput.fill('Custom Member Status');
        const [profileResponse] = await Promise.all([
            page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('home.php?mod=spacecp')
            ),
            profileSubmit.click()
        ]);
        assert.ok(
            profileResponse.ok() || (profileResponse.status() >= 300 && profileResponse.status() < 400),
            `Assertion Error: Personal profile POST failed with HTTP ${profileResponse.status()}.`
        );
        const profileValues = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COALESCE(customstatus,'') FROM pre_common_member_field_forum WHERE uid='${userUid}';"`).toString().trim();
        assert.strictEqual(profileValues, 'Custom Member Status', 'Assertion Error: Personal profile custom status was not persisted.');
        await page.reload({ waitUntil: 'networkidle' });
        assert.strictEqual(
            await page.locator('input[name="customstatus"]').inputValue(),
            'Custom Member Status',
            'Assertion Error: Reloaded profile UI did not show the saved custom status.'
        );

        console.log("Testing User Threads Page (with view=me)...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=thread&view=me');
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_space_thread_viewme.png' });

        const viewMeBody = await page.textContent('body');
        assert.ok(viewMeBody.includes(editedStandardSubject), 'Assertion Error: view=me user threads page did not list the edited thread.');

        console.log("Testing Other User Threads Page (home.php?mod=space&uid=1&do=thread)...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&uid=1&do=thread');
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_space_thread_default.png' });

        const defaultThreadBody = await page.textContent('body');
        assert.ok(defaultThreadBody.includes('Admin Seed Thread'), 'Assertion Error: Other user threads page did not list the seeded admin thread.');

        console.log("Testing User Replies Page (home.php?mod=space&do=thread&view=me&type=reply)...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=thread&view=me&type=reply');
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_desktop_space_thread_reply.png' });

        const viewReplyBody = await page.textContent('body');
        assert.ok(
            viewReplyBody.includes('Reply text from unprivileged account.'),
            'Assertion Error: view=me&type=reply user replies page did not load correctly.'
        );

        console.log("Testing Thread Recommendation and Hot Reply Voting via UI...");
        const adminTidOutput = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT tid FROM pre_forum_thread WHERE authorid=1 ORDER BY tid DESC LIMIT 1;\"").toString().trim();
        assert.match(adminTidOutput, /^\d+$/, 'Assertion Error: Seeded admin thread for recommendation testing was not found.');
        const targetRecommendTid = adminTidOutput;
        const postreviewFixtureMessage = `Desktop postreview fixture ${String(testRunId).replace(/[^A-Za-z0-9_-]/g, '')}`;
        const postreviewFixtureSqlMessage = postreviewFixtureMessage.replace(/'/g, "''");
        let adminReplyPidOutput = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${targetRecommendTid}' AND authorid=1 AND first=0 AND message='${postreviewFixtureSqlMessage}' LIMIT 1;"`).toString().trim();
        if(!adminReplyPidOutput) {
            const fixtureFid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT fid FROM pre_forum_thread WHERE tid='${targetRecommendTid}' LIMIT 1;"`).toString().trim();
            const fixturePosition = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT maxposition + 1 FROM pre_forum_thread WHERE tid='${targetRecommendTid}';"`).toString().trim();
            const fixturePid = execSync("sudo mysql -u root ultrax -N -s -e \"INSERT INTO pre_forum_post_tableid (pid) VALUES (NULL); SELECT LAST_INSERT_ID();\"").toString().trim().split(/\s+/).pop();
            const fixtureDateline = Math.floor(Date.now() / 1000);
            execSync(`sudo mysql -u root ultrax -e "INSERT INTO pre_forum_post (pid, fid, tid, first, author, authorid, subject, dateline, message, invisible, anonymous, htmlon, bbcodeoff, smileyoff, parseurloff, attachment, status, position, bestanswer) VALUES (${fixturePid}, ${fixtureFid}, ${targetRecommendTid}, 0, 'admin', 1, '', ${fixtureDateline}, '${postreviewFixtureSqlMessage}', 0, 0, 0, 0, 0, 0, 0, 0, ${fixturePosition}, 0); UPDATE pre_forum_thread SET replies=replies+1, maxposition=${fixturePosition}, lastpost=${fixtureDateline}, lastposter='admin' WHERE tid=${targetRecommendTid};"`);
            adminReplyPidOutput = fixturePid;
        }
        assert.match(adminReplyPidOutput, /^\d+$/, 'Assertion Error: Isolated admin reply for postreview testing was not created.');
        const targetSupportTid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tid FROM pre_forum_post WHERE pid='${adminReplyPidOutput}' LIMIT 1;"`).toString().trim();
        assert.match(targetSupportTid, /^\d+$/, 'Assertion Error: Seeded admin reply thread ID was not found.');

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${targetRecommendTid}`);
        await page.waitForLoadState('networkidle');
        const recommendBtn = page.locator('a[href*="action=recommend&do=add"]');
        assert.strictEqual(await recommendBtn.count(), 1, 'Assertion Error: Desktop thread recommend button did not render.');
        assert.ok(await recommendBtn.isVisible(), 'Assertion Error: Desktop thread recommend button was not visible.');
        const recommendCount = page.locator('#recommendv_add');
        assert.strictEqual(await recommendCount.count(), 1, 'Assertion Error: Desktop recommendation count did not render.');
        const recommendCountBefore = Number((await recommendCount.textContent()).trim() || '0');
        console.log("Clicking desktop thread recommend button via UI...");
        const [recommendResponse] = await Promise.all([
            page.waitForResponse(response => response.url().includes('action=recommend&do=add')),
            recommendBtn.click()
        ]);
        assert.ok(recommendResponse.ok(), `Assertion Error: Thread recommendation request failed with HTTP ${recommendResponse.status()}.`);
        await page.waitForFunction(
            previous => Number(document.querySelector('#recommendv_add')?.textContent.trim() || '0') > previous,
            recommendCountBefore
        );
        const recommendDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_memberrecommend WHERE tid='${targetRecommendTid}' AND recommenduid='${userUid}';"`).toString().trim();
        assert.strictEqual(recommendDbCheck, '1', 'Assertion Error: Thread recommendation was not persisted.');

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${targetSupportTid}`);
        await page.waitForLoadState('networkidle');
        const supportBtn = page.locator(`a[href*="action=postreview&do=support"][href*="pid=${adminReplyPidOutput}"]`);
        assert.strictEqual(await supportBtn.count(), 1, 'Assertion Error: Desktop postreview support button did not render.');
        assert.ok(await supportBtn.isVisible(), 'Assertion Error: Desktop postreview support button was not visible.');
        const supportCount = page.locator(`#review_support_${adminReplyPidOutput}`);
        assert.strictEqual(await supportCount.count(), 1, 'Assertion Error: Desktop postreview support count did not render.');
        const supportCountBefore = Number((await supportCount.textContent()).trim() || '0');
        console.log("Clicking desktop postreview support button via UI...");
        const [supportResponse] = await Promise.all([
            page.waitForResponse(response => response.url().includes('action=postreview&do=support')),
            supportBtn.click()
        ]);
        assert.ok(supportResponse.ok(), `Assertion Error: Postreview support request failed with HTTP ${supportResponse.status()}.`);
        await page.waitForFunction(
            ({ pid, previous }) => Number(document.getElementById(`review_support_${pid}`)?.textContent.trim() || '0') > previous,
            { pid: adminReplyPidOutput, previous: supportCountBefore }
        );
        const supportDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_hotreply_member WHERE pid='${adminReplyPidOutput}' AND uid='${userUid}' AND attitude=1;"`).toString().trim();
        assert.strictEqual(supportDbCheck, '1', 'Assertion Error: Postreview support vote was not persisted.');
        const supportNoticeCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT CONCAT(COUNT(*), ':', COALESCE(MAX(from_num), 0)) FROM pre_home_notification WHERE uid='1' AND authorid='${userUid}' AND type='post' AND from_id='${adminReplyPidOutput}' AND from_idtype='postreview_support';"`).toString().trim();
        assert.strictEqual(supportNoticeCheck, '1:1', 'Assertion Error: New postreview support vote did not notify the reply author exactly once.');

        const againstBtn = page.locator(`a[href*="action=postreview&do=against"][href*="pid=${adminReplyPidOutput}"]`);
        assert.strictEqual(await againstBtn.count(), 1, 'Assertion Error: Desktop postreview oppose button did not render.');
        console.log("Changing desktop postreview support vote to oppose via UI...");
        const [againstResponse] = await Promise.all([
            page.waitForResponse(response => response.url().includes('action=postreview&do=against')),
            againstBtn.click()
        ]);
        assert.ok(againstResponse.ok(), `Assertion Error: Postreview oppose request failed with HTTP ${againstResponse.status()}.`);
        const changedVoteDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_hotreply_member WHERE pid='${adminReplyPidOutput}' AND uid='${userUid}' AND attitude=0;"`).toString().trim();
        assert.strictEqual(changedVoteDbCheck, '1', 'Assertion Error: Postreview vote change was not persisted.');
        const changedVoteNoticeCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_home_notification WHERE uid='1' AND authorid='${userUid}' AND type='post' AND from_id='${adminReplyPidOutput}' AND from_idtype='postreview_against';"`).toString().trim();
        assert.strictEqual(changedVoteNoticeCheck, '0', 'Assertion Error: Changing a postreview vote generated an unwanted notification.');

        console.log("Cancelling desktop postreview oppose vote via UI...");
        const [cancelVoteResponse] = await Promise.all([
            page.waitForResponse(response => response.url().includes('action=postreview&do=against')),
            againstBtn.click()
        ]);
        assert.ok(cancelVoteResponse.ok(), `Assertion Error: Postreview cancellation request failed with HTTP ${cancelVoteResponse.status()}.`);
        const cancelledVoteDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_hotreply_member WHERE pid='${adminReplyPidOutput}' AND uid='${userUid}';"`).toString().trim();
        assert.strictEqual(cancelledVoteDbCheck, '0', 'Assertion Error: Postreview vote cancellation was not persisted.');
        const finalVoteNoticeCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT CONCAT(COUNT(*), ':', COALESCE(MAX(from_num), 0)) FROM pre_home_notification WHERE uid='1' AND authorid='${userUid}' AND type='post' AND from_id='${adminReplyPidOutput}';"`).toString().trim();
        assert.strictEqual(finalVoteNoticeCheck, '1:1', 'Assertion Error: Changing or cancelling a postreview vote generated notification noise.');

        console.log("Casting a new desktop postreview oppose vote via UI...");
        const [newAgainstResponse] = await Promise.all([
            page.waitForResponse(response => response.url().includes('action=postreview&do=against')),
            againstBtn.click()
        ]);
        assert.ok(newAgainstResponse.ok(), `Assertion Error: New postreview oppose request failed with HTTP ${newAgainstResponse.status()}.`);
        const againstNoticeCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT CONCAT(COUNT(*), ':', COALESCE(MAX(from_num), 0)) FROM pre_home_notification WHERE uid='1' AND authorid='${userUid}' AND type='post' AND from_id='${adminReplyPidOutput}' AND from_idtype='postreview_against';"`).toString().trim();
        assert.strictEqual(againstNoticeCheck, '1:1', 'Assertion Error: New postreview oppose vote did not notify the reply author exactly once.');

        console.log("Cancelling the new desktop postreview oppose vote via UI...");
        const [newAgainstCancelResponse] = await Promise.all([
            page.waitForResponse(response => response.url().includes('action=postreview&do=against')),
            againstBtn.click()
        ]);
        assert.ok(newAgainstCancelResponse.ok(), `Assertion Error: New postreview oppose cancellation failed with HTTP ${newAgainstCancelResponse.status()}.`);
        const againstNoticeAfterCancel = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT CONCAT(COUNT(*), ':', COALESCE(MAX(from_num), 0)) FROM pre_home_notification WHERE uid='1' AND authorid='${userUid}' AND type='post' AND from_id='${adminReplyPidOutput}' AND from_idtype='postreview_against';"`).toString().trim();
        assert.strictEqual(againstNoticeAfterCancel, '1:1', 'Assertion Error: Cancelling a new postreview oppose vote generated notification noise.');

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${targetRecommendTid}`);
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_desktop_thread_recommend.png' });

        report += '### 4b. Personal Info Update & Space Threads Verification\n- **Status**: Checked\n- **spacecp Update**: Success\n- **Threads Page (with view=me)**: Success — `screenshot_space_thread_viewme.png`\n- **Other User Threads Page (uid=1)**: Success — `screenshot_space_thread_default.png`\n- **User Replies Page (type=reply)**: Success — `screenshot_desktop_space_thread_reply.png`\n- **Thread Recommendation & Hot Reply Check**: Success — `screenshot_desktop_thread_recommend.png`\n\n';

        console.log("Testing Personal Messages (PM) on Desktop via UI...");
        const userPmToAdmin = 'UI sent test message to admin.';
        await sendPrivateMessage(page, 1, userPmToAdmin);
        const userPmDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_common_pm_message p INNER JOIN pre_common_pm_member m ON m.plid=p.plid WHERE m.uid='1' AND p.authorid='${userUid}' AND p.message='${userPmToAdmin}';"`).toString().trim();
        assert.strictEqual(userPmDbCheck, '1', 'Assertion Error: User PM was not delivered to the admin inbox.');

        console.log("Testing Reply Quote & Notification (do=notice) and PM send back from admin via UI...");
            const quotePid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${tidOutput}' AND authorid='${userUid}' AND first=0 AND message LIKE 'Reply text from unprivileged account.%' ORDER BY pid ASC LIMIT 1;"`).toString().trim();
            assert.match(quotePid, /^\d+$/, 'Assertion Error: Reply post ID for the quote-reply test was not found.');

            const adminContext = await browser.newContext();
            await stubPusher(adminContext);
            const adminPage = await adminContext.newPage();
            await adminPage.goto('http://127.0.0.1:8080/member.php?mod=logging&action=login');
            await adminPage.waitForLoadState('domcontentloaded');
            const adminLoginForm = adminPage.locator('form[id^="loginform_"]:visible');
            assert.strictEqual(await adminLoginForm.count(), 1, 'Assertion Error: Admin quote-reply login form did not render.');
            await adminLoginForm.locator('input[name="username"]').fill('admin');
            await adminLoginForm.locator('input[name="password"]').fill('Testpassword123!');
            await solveSecurityQuestion(adminPage, adminLoginForm);
            const adminLoginSubmit = adminLoginForm.locator('button[name="loginsubmit"], button[type="submit"], input[type="submit"]');
            assert.strictEqual(await adminLoginSubmit.count(), 1, 'Assertion Error: Admin login submit control did not render.');
            const [adminLoginResponse] = await Promise.all([
                adminPage.waitForResponse(response =>
                    response.request().method() === 'POST' &&
                    response.url().includes('member.php?mod=logging')
                ),
                adminLoginSubmit.click()
            ]);
            assert.ok(
                adminLoginResponse.ok() || (adminLoginResponse.status() >= 300 && adminLoginResponse.status() < 400),
                `Assertion Error: Admin login POST failed with HTTP ${adminLoginResponse.status()}.`
            );
            await adminPage.waitForURL(url => !url.href.includes('member.php?mod=logging'));
            await adminPage.waitForLoadState('domcontentloaded');
            assert.strictEqual(
                await adminPage.evaluate(() => Number(window.discuz_uid || 0)),
                1,
                'Assertion Error: Admin login did not establish the expected browser session.'
            );

            const adminPmToUser = 'Admin reply PM to user via UI.';
            await sendPrivateMessage(adminPage, userUid, adminPmToUser);
            const adminPmDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_common_pm_message p INNER JOIN pre_common_pm_member m ON m.plid=p.plid WHERE m.uid='${userUid}' AND p.authorid='1' AND p.message='${adminPmToUser}';"`).toString().trim();
            assert.strictEqual(adminPmDbCheck, '1', 'Assertion Error: Admin PM was not delivered to the user inbox.');

            await adminPage.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await adminPage.waitForLoadState('networkidle');
            const adminQuoteLink = adminPage.locator(`a.fastre[href*="repquote=${quotePid}"]`);
            assert.strictEqual(await adminQuoteLink.count(), 1, 'Assertion Error: Admin quote-reply link did not render.');
            await adminQuoteLink.click();
            const adminReplyForm = adminPage.locator('#fwin_reply form:visible');
            await adminReplyForm.waitFor({ state: 'visible' });
            await appendToQuotedPostEditor(
                'Admin quote reply to user thread.',
                'Reply text from unprivileged account.',
                adminPage,
                adminReplyForm
            );
            await solveSecurityQuestion(adminPage, adminReplyForm);
            const adminReplyBtn = adminReplyForm.locator('#postsubmit, button[name="replysubmit"]');
            assert.strictEqual(await adminReplyBtn.count(), 1, 'Assertion Error: Admin reply submit button was not rendered.');
            const [adminReplyResponse] = await Promise.all([
                adminPage.waitForResponse(response =>
                    response.request().method() === 'POST' &&
                    response.url().includes('forum.php?mod=post')
                ),
                adminReplyBtn.click()
            ]);
            assert.ok(
                adminReplyResponse.ok() || (adminReplyResponse.status() >= 300 && adminReplyResponse.status() < 400),
                `Assertion Error: Admin reply POST failed with HTTP ${adminReplyResponse.status()}.`
            );
            await adminPage.waitForFunction(
                message => document.body && document.body.innerText.includes(message),
                'Admin quote reply to user thread.'
            );
            await adminContext.close();

            // Verify the notice badge clears and the notification is persisted as read.
            await openPmFromNotice(page, userUid);
            const pmBody = await page.textContent('body');
            assert.ok(pmBody.includes(adminPmToUser), 'Assertion Error: Desktop PM center did not display the delivered admin message.');
            report += '### 4c. Desktop Personal Message (PM)\n- **Status**: Checked\n- **Send PM via UI**: Success\n- **Admin Send Back PM**: Success\n- **Header Notice Hover Dropdown**: Success\n- **Unread Badge Cleared**: Success\n- **Notification Read State**: Success\n- **PM Center View**: Success\n- **Screenshot**: `screenshot_desktop_notice_dropdown.png`\n\n';

            const adminReplyDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_post WHERE tid='${tidOutput}' AND authorid=1 AND first=0 AND message LIKE '%Admin quote reply to user thread.%';"`).toString().trim();
            assert.ok(parseInt(adminReplyDbCheck, 10) >= 1, 'Assertion Error: Admin quote reply was not created in database.');

            console.log("Posting postcomment via UI and testing type=postcomment page...");
            const postCommentText = 'Test postcomment content text.';
            const adminReplyPid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${tidOutput}' AND authorid=1 AND first=0 AND message LIKE '%Admin quote reply to user thread.%' ORDER BY pid DESC LIMIT 1;"`).toString().trim();
            assert.ok(adminReplyPid, 'Assertion Error: Admin reply post ID was not found.');

            await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await page.waitForLoadState('networkidle');
            const adminQuotedPost = page.locator(`#post_${adminReplyPid}`);
            assert.ok(
                (await adminQuotedPost.textContent()).includes('Reply text from unprivileged account.'),
                'Assertion Error: Submitted admin reply did not render the quoted post text.'
            );
            assert.ok(
                await adminQuotedPost.locator('.quote, blockquote').count(),
                'Assertion Error: Submitted admin reply did not render a quote container.'
            );
            await adminQuotedPost.screenshot({ path: 'screenshot_desktop_quote_reply.png' });
            const commentBtn = page.locator(`a.cmmnt[href*="pid=${adminReplyPid}"]`);
            assert.strictEqual(await commentBtn.count(), 1, 'Assertion Error: Comment control did not render for the admin reply.');
            await commentBtn.click();

            const commentForm = page.locator('#fwin_comment form#commentform');
            await commentForm.waitFor({ state: 'visible' });
            const commentMessage = commentForm.locator('#commentmessage');
            const submitCommentBtn = commentForm.locator('#commentsubmit');
            assert.strictEqual(await commentMessage.count(), 1, 'Assertion Error: Post comment message input did not render.');
            assert.strictEqual(await submitCommentBtn.count(), 1, 'Assertion Error: Post comment submit button did not render.');
            await commentMessage.fill(postCommentText);
            await solveSecurityQuestion(page, commentForm);
            const [postCommentResponse] = await Promise.all([
                page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('mod=post') && response.url().includes('commentsubmit=yes')),
                submitCommentBtn.click()
            ]);
            assert.ok(postCommentResponse.ok(), `Assertion Error: Post comment request failed with HTTP ${postCommentResponse.status()}.`);

            const postCommentDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_postcomment WHERE authorid='${userUid}' AND pid='${adminReplyPid}' AND comment='${postCommentText}';"`).toString().trim();
            assert.strictEqual(postCommentDbCheck, '1', 'Assertion Error: Post comment was not created in database.');

            // Navigate back to viewthread to verify and screenshot the postcomment
            await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await page.waitForLoadState('domcontentloaded');
            const commentedThreadBody = await page.textContent('body');
            assert.ok(commentedThreadBody.includes('Admin quote reply to user thread.'), 'Assertion Error: Admin quote reply was not rendered in viewthread.');
            assert.ok(commentedThreadBody.includes(postCommentText), 'Assertion Error: Post comment was not rendered in viewthread.');
            await page.screenshot({ path: 'screenshot_desktop_viewthread_commented.png' });

            await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=thread&view=me&type=postcomment');
            await page.waitForLoadState('domcontentloaded');
            await page.screenshot({ path: 'screenshot_desktop_space_thread_postcomment.png' });
            const viewPostcommentBody = await page.textContent('body');
            assert.ok(
                viewPostcommentBody.includes(postCommentText),
                'Assertion Error: view=me&type=postcomment page did not load correctly.'
            );

            await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=notice');
            await page.waitForLoadState('domcontentloaded');
            await page.screenshot({ path: 'screenshot_desktop_notice.png' });

            const noticeDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_home_notification WHERE uid='${userUid}';"`).toString().trim();
            assert.ok(parseInt(noticeDbCheck, 10) >= 1, 'Assertion Error: Notification record was not found in database.');

            const noticeBody = await page.textContent('body');
            assert.ok(
                noticeBody.includes('Admin quote reply to user thread.'),
                'Assertion Error: Desktop reply notification page did not render the exact admin reply notification.'
            );
            report += '### 4d. Desktop Reply Quote & Notification (do=notice)\n- **Status**: Checked\n- **Admin Quote Reply via UI**: Success\n- **Rendered Quote Screenshot**: `screenshot_desktop_quote_reply.png`\n- **DB Notification Check**: Passed\n- **Notice Page Render**: Success\n- **Screenshot**: `screenshot_desktop_notice.png`\n\n';

        console.log("Checking profile page for user custom avatar...");
        await page.goto(`http://127.0.0.1:8080/home.php?mod=space&uid=${userUid}&do=profile`);
        await page.waitForLoadState('networkidle');

        const profileAvatarImg = page.locator('#uhd .avt img, #uhd .icn.avt img').first();
        assert.strictEqual(await profileAvatarImg.count(), 1, 'Assertion Error: Uploaded avatar image was not rendered on profile page.');
        assert.ok(await profileAvatarImg.evaluate(image => image.complete && image.naturalWidth > 0), 'Assertion Error: Profile avatar image did not load.');

        console.log("Checking other user's profile page on desktop (admin uid=1)...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&uid=1&do=profile');
        await page.waitForLoadState('domcontentloaded');
        await page.locator('body').waitFor({ state: 'visible' });
        const otherProfileBody = await page.textContent('body');
        assert.ok(otherProfileBody.includes('admin'), 'Assertion Error: Desktop other user profile page did not load.');
        assert.ok(otherProfileBody.includes('EXP'), 'Assertion Error: Profile did not label extcredits1 as EXP.');
        assert.ok(otherProfileBody.includes('Karma'), 'Assertion Error: Profile did not label extcredits2 as Karma.');
        await page.screenshot({ path: 'screenshot_desktop_other_user_profile.png' });

        console.log("Checking header for user custom avatar...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=forumdisplay&fid=${forumFid}`);
        await page.waitForLoadState('networkidle');

        const headerSnippet = await page.evaluate(() => {
            const hd = document.getElementById('hd') || document.getElementById('um') || document.body;
            return hd ? hd.innerHTML.substring(0, 400) : '';
        });

        const headerAvatarImg = page.locator('#um .avt img, #hd .avt img, .header-user-avatar img').first();
        assert.strictEqual(await headerAvatarImg.count(), 1, `Assertion Error: Uploaded avatar image was not rendered in page header. Header HTML: ${headerSnippet}`);
        assert.ok(await headerAvatarImg.evaluate(image => image.complete && image.naturalWidth > 0), 'Assertion Error: Header avatar image did not load.');

        console.log("Checking viewthread page for author custom avatar...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
        await page.waitForLoadState('networkidle');

        const viewthreadAvatarImg = page.locator('#postlist .pls .avatar img, #postlist .postauthor .avatar img').first();
        assert.strictEqual(await viewthreadAvatarImg.count(), 1, 'Assertion Error: Uploaded author avatar image was not rendered on viewthread page.');
        assert.ok(await viewthreadAvatarImg.evaluate(image => image.complete && image.naturalWidth > 0), 'Assertion Error: Viewthread avatar image did not load.');

        report += '### 5. Unprivileged User Avatar Setup & Verification\n- **Status**: Checked\n- **Avatar Status in DB**: 1\n- **Profile Avatar Check**: Passed\n- **Other User Profile Screenshot**: `screenshot_desktop_other_user_profile.png`\n- **Header Avatar Check**: Passed\n- **Viewthread Avatar Check**: Passed\n\n';

        // 6. User Image Attachment Post Test
        console.log("Attempting to post thread with image attachment...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=${forumFid}`);
        await page.waitForLoadState('networkidle');

        const attachSubject = page.locator('input[name="subject"]');
        assert.strictEqual(await attachSubject.count(), 1, 'Assertion Error: Image attachment subject field did not render.');
        await attachSubject.fill(attachmentSubject);

        const uploaderRuntime = await page.evaluate(() => ({
            available: typeof DiscuzUploader === 'function',
            scripts: Array.from(document.scripts).map(script => script.src).filter(Boolean),
        }));
        assert.ok(uploaderRuntime.available, 'Assertion Error: Desktop HTML5 DiscuzUploader runtime did not load.');
        assert.ok(
            uploaderRuntime.scripts.some(src => /\/discuz_uploader\.js(?:\?|$)/.test(src)),
            `Assertion Error: Renamed desktop uploader script was not loaded. Scripts: ${uploaderRuntime.scripts.join(', ')}`
        );

        const attachmentSource = 'static/image/smiley/BQ2/alu1.jpg';
        const parallelSource2 = 'static/image/mobile/images/pic_bg.jpg';
        const parallelSource3 = 'static/image/mobile/preview.png';
        const attachmentFixture = 'scratch/one_megabyte_image.jpg';
        const oneMegabyte = 1024 * 1024;
        assert.ok(fs.existsSync(attachmentSource), `Assertion Error: Attachment fixture is missing: ${attachmentSource}`);
        assert.ok(fs.existsSync(parallelSource2), `Assertion Error: Parallel attachment fixture is missing: ${parallelSource2}`);
        assert.ok(fs.existsSync(parallelSource3), `Assertion Error: Parallel attachment fixture is missing: ${parallelSource3}`);
        fs.mkdirSync('scratch', { recursive: true });
        const sourceBytes = fs.readFileSync(attachmentSource);
        assert.ok(sourceBytes.length < oneMegabyte, 'Assertion Error: Attachment source fixture is unexpectedly larger than 1 MiB.');
        fs.writeFileSync(attachmentFixture, Buffer.concat([sourceBytes, Buffer.alloc(oneMegabyte - sourceBytes.length)]));
        assert.strictEqual(fs.statSync(attachmentFixture).size, oneMegabyte, 'Assertion Error: Generated image fixture is not exactly 1 MiB.');
        const attachmentFixtures = [
            'scratch/parallel_image_2.jpg',
            'scratch/parallel_image_3.png',
            attachmentFixture
        ];
        // Use different real images so WebUploader's duplicate suppression does
        // not discard the parallel batch based on a shared content prefix.
        fs.copyFileSync(parallelSource2, attachmentFixtures[0]);
        fs.copyFileSync(parallelSource3, attachmentFixtures[1]);
        const editorTarget = await page.evaluate(() => {
            const iframe = Array.from(document.querySelectorAll('iframe[id$="_iframe"]')).find(node => {
                const style = getComputedStyle(node);
                return style.display !== 'none' && style.visibility !== 'hidden';
            });
            if(iframe) {
                return { type: 'iframe', id: iframe.id };
            }
            const textarea = document.querySelector('textarea[name="message"]');
            return textarea ? { type: 'textarea', id: textarea.id } : null;
        });
        assert.ok(editorTarget, 'Assertion Error: No active forum editor was available for paste/drop upload tests.');
        // Keep paste/drop focused on browser image handling; verify the exact 1 MiB
        // payload separately through the native file-input upload path below.
        const fixtureBase64 = sourceBytes.toString('base64');
        const dispatchEditorImageEvent = async (eventType, files) => {
            return page.evaluate(({target, eventType, files}) => {
                const createEvent = (type, dataTransfer) => {
                    let event;
                    if(type === 'paste') {
                        try {
                            event = new ClipboardEvent(type, { bubbles: true, cancelable: true, clipboardData: dataTransfer });
                        } catch(error) {}
                        if(!event || !event.clipboardData) {
                            event = new Event(type, { bubbles: true, cancelable: true });
                            Object.defineProperty(event, 'clipboardData', { value: dataTransfer });
                        }
                    } else {
                        try {
                            event = new DragEvent(type, { bubbles: true, cancelable: true, dataTransfer });
                        } catch(error) {}
                        if(!event || !event.dataTransfer) {
                            event = new Event(type, { bubbles: true, cancelable: true });
                            Object.defineProperty(event, 'dataTransfer', { value: dataTransfer });
                        }
                    }
                    return event;
                };
                const dataTransfer = new DataTransfer();
                for(const file of files) {
                    const bytes = Uint8Array.from(atob(file.base64), character => character.charCodeAt(0));
                    dataTransfer.items.add(new File([bytes], file.name, {
                        type: file.type,
                        lastModified: file.lastModified
                    }));
                }
                let targetDocument = document;
                let targetNode;
                if(target.type === 'iframe') {
                    const iframe = document.getElementById(target.id);
                    targetDocument = iframe.contentDocument;
                    targetNode = targetDocument.body;
                } else {
                    targetNode = document.getElementById(target.id);
                }
                if(!targetNode) {
                    throw new Error('Editor event target was not found');
                }
                const cancelled = !targetNode.dispatchEvent(createEvent(eventType, dataTransfer));
                return { cancelled };
            }, {
                target: editorTarget,
                eventType,
                files
            });
        };
        const waitForEditorUploads = async (expected, action, label) => {
            const responses = [];
            const result = new Promise((resolve, reject) => {
                let timer;
                const cleanup = () => {
                    clearTimeout(timer);
                    page.off('response', onResponse);
                };
                const onResponse = response => {
                    if(response.request().method() === 'POST' && response.url().includes('misc.php?mod=upload')) {
                        responses.push(response);
                        if(responses.length === expected) {
                            cleanup();
                            resolve(responses);
                        }
                    }
                };
                timer = setTimeout(() => {
                    cleanup();
                    reject(new Error(`Timed out waiting for ${expected} ${label} uploads; received ${responses.length}.`));
                }, 60000);
                page.on('response', onResponse);
            });
            const dispatchResult = await action();
            const uploadResults = await result;
            for(const response of uploadResults) {
                const responseText = await response.text();
                assert.match(responseText.trim(), /^\d+$/, `Assertion Error: ${label} image upload failed. Response: ${responseText}`);
            }
            return dispatchResult;
        };
        const pasteFiles = [
            { name: 'paste-image.jpg', type: 'image/jpeg', lastModified: 1, base64: fixtureBase64 },
            { name: 'paste-image.jpg', type: 'image/jpeg', lastModified: 1, base64: fixtureBase64 }
        ];
        const pasteResult = await waitForEditorUploads(1, () => dispatchEditorImageEvent('paste', pasteFiles), 'paste');
        assert.ok(pasteResult.cancelled, 'Assertion Error: Image paste event was not cancelled after upload handling.');
        const dropFiles = [
            { name: 'drop-image-1.jpg', type: 'image/jpeg', lastModified: 2, base64: fixtureBase64 },
            { name: 'drop-image-2.jpg', type: 'image/jpeg', lastModified: 3, base64: fixtureBase64 }
        ];
        const dropResult = await waitForEditorUploads(2, () => dispatchEditorImageEvent('drop', dropFiles), 'drop');
        assert.ok(dropResult.cancelled, 'Assertion Error: Image drop event was not cancelled after upload handling.');
        await page.waitForFunction(target => {
            if(target.type === 'iframe') {
                const iframe = document.getElementById(target.id);
                return iframe && iframe.contentDocument && iframe.contentDocument.querySelectorAll('img[aid^="attachimg_"]').length >= 3;
            }
            const textarea = document.getElementById(target.id);
            return textarea && (textarea.value.match(/\[attachimg\]/g) || []).length >= 3;
        }, editorTarget, { timeout: 10000 });
        console.log('Verified paste and drag-and-drop image uploads, duplicate suppression, and editor insertion.');
        const uploadPickers = page.locator('div[id^="rt_"] input[type="file"]');
        assert.strictEqual(await uploadPickers.count(), 2, 'Assertion Error: Desktop WebUploader pickers did not render.');
        const imageInput = uploadPickers.nth(0);
        // Paste/drop already exercises two concurrent uploads above. The native
        // picker now verifies the exact 1 MiB fixture within the remaining post
        // attachment quota.
        const nativeUploadFixtures = [attachmentFixture];
        const uploadMaxBytes = await page.evaluate(() => {
            if(!window.imgUpload || !imgUpload.settings) return 0;
            return (Number(imgUpload.settings.file_size_limit) || 0) * 1024;
        });
        assert.ok(
            uploadMaxBytes === 0 || fs.statSync(attachmentFixture).size <= uploadMaxBytes,
            `Assertion Error: 1 MiB fixture exceeds the configured image upload size limit (${uploadMaxBytes} bytes).`
        );
        const uploadResponses = [];
        let finishParallelUploadWait;
        const parallelUploadWait = new Promise((resolve, reject) => {
            finishParallelUploadWait = () => {
                clearTimeout(timeoutId);
                page.off('response', onUploadResponse);
                resolve(uploadResponses);
            };
            const timeoutId = setTimeout(() => {
                page.off('response', onUploadResponse);
                reject(new Error(`Timed out waiting for ${nativeUploadFixtures.length} native image upload; received ${uploadResponses.length}.`));
            }, 60000);
            const onUploadResponse = response => {
                if(response.request().method() === 'POST' && response.url().includes('misc.php?mod=upload')) {
                    uploadResponses.push(response);
                    if(uploadResponses.length === nativeUploadFixtures.length) {
                        finishParallelUploadWait();
                    }
                }
            };
            page.on('response', onUploadResponse);
        });
        await imageInput.setInputFiles(nativeUploadFixtures);
        const nativeResponses = await parallelUploadWait;
        for(const response of nativeResponses) {
            const responseText = await response.text();
            assert.match(responseText.trim(), /^\d+$/, `Assertion Error: Desktop parallel image upload failed. Response: ${responseText}`);
        }
        await page.waitForFunction(
            expected => document.querySelectorAll('#imgattachlist input[name^="attachnew["]').length >= expected,
            nativeUploadFixtures.length,
            { timeout: 10000 }
        );
        const aid = execSync(`sudo mysql -u root ultrax -N -s -e \"SELECT aid FROM pre_forum_attachment_unused WHERE uid='${userUid}' AND filesize='${oneMegabyte}' ORDER BY aid DESC LIMIT 1;\"`).toString().trim();
        assert.match(aid, /^\d+$/, 'Assertion Error: 1 MiB unused attachment was not created.');
        console.log("Discovered attachment AID:", aid);
        const displayWidthInput = page.locator(`#imgattachlist input[name="attachnew[${aid}][displaywidth]"]`);
        assert.strictEqual(await displayWidthInput.count(), 1, 'Assertion Error: Image attachment display-width control did not render.');
        await displayWidthInput.fill('64');

        const attachMsg = `Posting thread with image attachment content. [attach]${aid}[/attach]`;

        await fillPostEditor(attachMsg);
        await solveSecurityQuestion(page);

        const extraTagBtn = await page.$('#extra_tag_b, a[href*="extra_tag"], #extra_tag_b a');
        assert.ok(extraTagBtn, 'Assertion Error: Desktop tag control did not render.');
        const tagsInput = page.locator('#keyword-input');
        assert.strictEqual(await tagsInput.count(), 1, 'Assertion Error: Desktop tag input did not render.');
        if(!await tagsInput.isVisible()) {
            await extraTagBtn.click();
            await tagsInput.waitFor({ state: 'visible' });
        }
        await tagsInput.fill('sample tag');
        await tagsInput.press('Enter');

        const attachSubmitBtn = page.locator('button[name="topicsubmit"]');
        assert.strictEqual(await attachSubmitBtn.count(), 1, 'Assertion Error: Image attachment thread submit button did not render.');
        const [attachPostResponse] = await Promise.all([
            page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('forum.php?mod=post')
            ),
            attachSubmitBtn.click()
        ]);
        if (!(attachPostResponse.ok() || (attachPostResponse.status() >= 300 && attachPostResponse.status() < 400))) {
            const responseText = (await attachPostResponse.text()).replace(/\s+/g, ' ').slice(0, 2000);
            assert.fail(`Assertion Error: Image attachment thread POST failed with HTTP ${attachPostResponse.status()}. Response: ${responseText}`);
        }
        await page.waitForURL(/forum\.php\?mod=viewthread&tid=\d+/);

        console.log("Checking if attachment thread exists in DB and loads in viewthread...");
        const attachTid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tid FROM pre_forum_thread WHERE subject='${attachmentSubject}' ORDER BY tid DESC LIMIT 1;"`).toString().trim();
        assert.ok(attachTid, 'Assertion Error: Thread with attachment was not created in database.');
        assert.ok(page.url().includes(`tid=${attachTid}`), 'Assertion Error: Image attachment submission redirected to the wrong thread.');

        const storedAttachMessage = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT message FROM pre_forum_post WHERE tid='${attachTid}' AND first=1 ORDER BY pid ASC LIMIT 1;"`).toString().trim();
        assert.ok(storedAttachMessage.includes(`[attach=64]${aid}[/attach]`), `Assertion Error: Attachment display width was not stored in post BBCode. Message: ${storedAttachMessage}`);

        const attachDbRecord = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_attachment WHERE tid='${attachTid}';"`).toString().trim();
        console.log("DB count for pre_forum_attachment:", attachDbRecord);
        assert.ok(parseInt(attachDbRecord, 10) >= 1, 'Assertion Error: Image attachment record was not linked in pre_forum_attachment database table.');

        await page.waitForLoadState('networkidle');

        const viewthreadBody = await page.textContent('body');
        assert.ok(
            viewthreadBody.includes(attachmentSubject) && viewthreadBody.includes('Posting thread with image attachment content.') && viewthreadBody.includes('sample tag'),
            'Assertion Error: Attachment thread page did not load thread content cleanly in viewthread.'
        );

        const postImg = await page.$('#postlist .t_f img[id^="aimg_"], #postlist .t_f img[aid], #postlist .t_f img[file], #postlist .t_f img[zoomfile], #postlist .t_f .tattl img, #postlist .t_f img[src*="data/attachment/"]');
        // Verify the stored type as well as the browser's rendered image.
        const postContentNode = page.locator('#postlist .t_f');
        assert.strictEqual(await postContentNode.count(), 1, 'Assertion Error: Attachment post content container did not render.');
        const tfSnippet = await postContentNode.evaluate(el => el.innerHTML.substring(0, 600));
        const attachmentIndex = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT CONCAT(tid, ':', tableid) FROM pre_forum_attachment WHERE aid='${aid}' LIMIT 1;"`).toString().trim();
        assert.ok(attachmentIndex, `Assertion Error: Attachment ${aid} was not present in pre_forum_attachment.`);
        const attachTableId = attachmentIndex.split(':')[1];
        assert.match(attachTableId, /^\d+$/, `Assertion Error: Attachment ${aid} has an invalid tableid in ${attachmentIndex}.`);
        const attachIsimage = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT isimage FROM pre_forum_attachment_${attachTableId} WHERE aid='${aid}' AND tid='${attachTid}' LIMIT 1;"`).toString().trim();
        const unusedAttachment = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_attachment_unused WHERE aid='${aid}';"`).toString().trim();
        assert.strictEqual(attachmentIndex, `${attachTid}:${attachTid.slice(-1)}`, `Assertion Error: Attachment index was not bound to thread ${attachTid}. Found: ${attachmentIndex}`);
        assert.strictEqual(unusedAttachment, '0', `Assertion Error: Attachment ${aid} remained in pre_forum_attachment_unused.`);

        const storedAttachmentInfo = execSync(`sudo mysql -u root ultrax -N -s -e \"SELECT CONCAT(filesize, ' ', attachment) FROM pre_forum_attachment_${attachTableId} WHERE aid='${aid}' AND tid='${attachTid}' LIMIT 1;\"`).toString().trim();
        const storedAttachmentMatch = storedAttachmentInfo.match(/^(\d+)\s+(\S+)/);
        assert.ok(storedAttachmentMatch, `Assertion Error: Database attachment size record was unreadable. Stored: ${storedAttachmentInfo}`);
        const storedAttachmentSize = Number(storedAttachmentMatch[1]);
        const storedAttachmentFile = storedAttachmentMatch[2];
        assert.strictEqual(storedAttachmentSize, oneMegabyte, `Assertion Error: Database attachment size did not preserve 1 MiB. Stored: ${storedAttachmentInfo}`);

        const storedAttachmentPath = path.join('data/attachment/forum', storedAttachmentFile);
        assert.strictEqual(fs.statSync(storedAttachmentPath).size, oneMegabyte, `Assertion Error: Stored attachment file did not preserve 1 MiB: ${storedAttachmentPath}`);

        assert.strictEqual(attachIsimage, '1', `Assertion Error: Uploaded PNG was not stored as an image. isimage: ${attachIsimage}`);
        assert.ok(postImg !== null, `Assertion Error: Attached image <img> element was not rendered inside post content (.t_f). .t_f: ${tfSnippet.substring(0, 200)}. isimage: ${attachIsimage}`);
        assert.strictEqual(await postImg.getAttribute('width'), '64', 'Assertion Error: Attached image display width was not rendered.');
        const imageSize = await postImg.evaluate(img => ({ width: img.naturalWidth, height: img.naturalHeight }));
        assert.ok(imageSize.width > 0 && imageSize.height > 0, `Assertion Error: Attached image did not load (${imageSize.width}x${imageSize.height}).`);

        await page.screenshot({ path: 'screenshot_attachment_viewthread.png' });

        report += `### 6. Unprivileged User Image Attachment Post\n- **Status**: Checked\n- **Thread Created**: ${attachmentSubject} (TID: ${attachTid}, AID: ${aid})\n- **1 MiB Byte-Preservation Check**: Passed\n- **Image Attachment DOM Check**: Passed\n- **Viewthread Verification**: Success\n\n`;

        // 6b. Non-Image Attachment Post Test
        console.log("Attempting to post thread with non-image attachment...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=${forumFid}`);
        await page.waitForLoadState('networkidle');

        const nonImgSubject = page.locator('input[name="subject"]');
        assert.strictEqual(await nonImgSubject.count(), 1, 'Assertion Error: Non-image attachment subject field did not render.');
        await nonImgSubject.fill(nonImageAttachmentSubject);

        fs.mkdirSync('scratch', { recursive: true });
        const nonImgFixture = 'scratch/sample_test_document.txt';
        fs.writeFileSync(nonImgFixture, 'This is a test non-image attachment document content.');
        const nonImgPickers = page.locator('div[id^="rt_"] input[type="file"]');
        assert.strictEqual(await nonImgPickers.count(), 2, 'Assertion Error: Desktop WebUploader pickers did not render.');
        const nonImgInput = nonImgPickers.nth(1);
        const nonImgUploadResponse = page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('misc.php?mod=upload'));
        await nonImgInput.setInputFiles(nonImgFixture);
        const nonImgResp = await (await nonImgUploadResponse).text();
        assert.match(nonImgResp.trim(), /^\d+$/, `Assertion Error: Desktop non-image upload failed. Response: ${nonImgResp}`);
        await page.waitForFunction(() => document.querySelector('#fsUploadProgress input[name^="attachnew["]'), null, { timeout: 5000 });
        const nonImgAid = await page.locator('#fsUploadProgress input[name^="attachnew["]').evaluate(input => input.name.match(/^attachnew\[(\d+)\]/)[1]);
        console.log("Discovered non-image attachment AID:", nonImgAid);

        const nonImgAttachMsg = `Posting thread with non-image attachment document. [attach]${nonImgAid}[/attach]`;

        await fillPostEditor(nonImgAttachMsg);
        await solveSecurityQuestion(page);

        const nonImgSubmitBtn = page.locator('button[name="topicsubmit"]');
        assert.strictEqual(await nonImgSubmitBtn.count(), 1, 'Assertion Error: Non-image attachment thread submit button did not render.');
        const [nonImgPostResponse] = await Promise.all([
            page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('forum.php?mod=post')
            ),
            nonImgSubmitBtn.click()
        ]);
        assert.ok(
            nonImgPostResponse.ok() || (nonImgPostResponse.status() >= 300 && nonImgPostResponse.status() < 400),
            `Assertion Error: Non-image attachment thread POST failed with HTTP ${nonImgPostResponse.status()}.`
        );
        await page.waitForURL(/forum\.php\?mod=viewthread&tid=\d+/);

        console.log("Checking if non-image attachment thread exists in DB and loads in viewthread...");
        const nonImgTid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tid FROM pre_forum_thread WHERE subject='${nonImageAttachmentSubject}' ORDER BY tid DESC LIMIT 1;"`).toString().trim();
        assert.ok(nonImgTid, 'Assertion Error: Thread with non-image attachment was not created in database.');
        assert.ok(page.url().includes(`tid=${nonImgTid}`), 'Assertion Error: Non-image attachment submission redirected to the wrong thread.');

        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_attachment_non_image_viewthread.png' });

        const nonImgViewthreadBody = await page.textContent('body');
        assert.ok(
            nonImgViewthreadBody.includes(nonImageAttachmentSubject) && nonImgViewthreadBody.includes('sample_test_document.txt'),
            'Assertion Error: Non-image attachment thread page did not load content in viewthread.'
        );

        // 6c. SVG Image Attachment Post Test
        console.log("Attempting to post thread with SVG image attachment...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=${forumFid}`);
        await page.waitForLoadState('networkidle');

        const svgSubject = page.locator('input[name="subject"]');
        assert.strictEqual(await svgSubject.count(), 1, 'Assertion Error: SVG attachment subject field did not render.');
        await svgSubject.fill(svgAttachmentSubject);

        fs.mkdirSync('scratch', { recursive: true });
        const svgFixture = 'scratch/sample_icon.svg';
        fs.writeFileSync(svgFixture, '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="100" height="100"><defs><linearGradient id="base"><stop offset="0" stop-color="blue" /></linearGradient><linearGradient id="derived" xlink:href="#base" /><circle id="dot" cx="50" cy="50" r="40" style="fill: url(#derived); stroke: #fff; background-image: url(https://example.com/tracker.svg)" /></defs><use xlink:href="#dot" style="opacity: .8" /></svg>');

        const svgPickers = page.locator('div[id^="rt_"] input[type="file"]');
        assert.strictEqual(await svgPickers.count(), 2, 'Assertion Error: Desktop WebUploader pickers did not render.');
        const svgInput = svgPickers.nth(0);
        const svgUploadResponse = page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('misc.php?mod=upload'));
        await svgInput.setInputFiles(svgFixture);
        const svgResp = await (await svgUploadResponse).text();
        assert.match(svgResp.trim(), /^\d+$/, `Assertion Error: Desktop SVG upload failed. Response: ${svgResp}`);
        await page.waitForFunction(() => document.querySelector('#imgattachlist input[name^="attachnew["]'), null, { timeout: 5000 });
        const svgAid = await page.locator('#imgattachlist input[name$="[description]"]').evaluate(input => input.name.match(/^attachnew\[(\d+)\]/)[1]);
        console.log("Discovered SVG attachment AID:", svgAid);

        const svgAttachMsg = `Posting thread with SVG image content. [attach]${svgAid}[/attach]`;

        await fillPostEditor(svgAttachMsg);
        await solveSecurityQuestion(page);

        const svgSubmitBtn = page.locator('button[name="topicsubmit"]');
        assert.strictEqual(await svgSubmitBtn.count(), 1, 'Assertion Error: SVG attachment thread submit button did not render.');
        const [svgPostResponse] = await Promise.all([
            page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('forum.php?mod=post')
            ),
            svgSubmitBtn.click()
        ]);
        assert.ok(
            svgPostResponse.ok() || (svgPostResponse.status() >= 300 && svgPostResponse.status() < 400),
            `Assertion Error: SVG attachment thread POST failed with HTTP ${svgPostResponse.status()}.`
        );
        await page.waitForURL(/forum\.php\?mod=viewthread&tid=\d+/);

        console.log("Checking if SVG attachment thread exists in DB and loads in viewthread...");
        const svgTid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tid FROM pre_forum_thread WHERE subject='${svgAttachmentSubject}' ORDER BY tid DESC LIMIT 1;"`).toString().trim();
        assert.ok(svgTid, 'Assertion Error: Thread with SVG attachment was not created in database.');
        assert.ok(page.url().includes(`tid=${svgTid}`), 'Assertion Error: SVG attachment submission redirected to the wrong thread.');

        const svgDbRecord = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_attachment WHERE tid='${svgTid}';"`).toString().trim();
        assert.ok(parseInt(svgDbRecord, 10) >= 1, 'Assertion Error: SVG attachment record was not linked in pre_forum_attachment database table.');

        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_attachment_svg_viewthread.png' });

        const svgAttachmentIndex = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT CONCAT(tid, ':', tableid) FROM pre_forum_attachment WHERE aid='${svgAid}' LIMIT 1;"`).toString().trim();
        const svgAttachTableId = svgAttachmentIndex.split(':')[1];
        const svgIsImage = svgAttachTableId === undefined ? '' : execSync(`sudo mysql -u root ultrax -N -s -e "SELECT isimage FROM pre_forum_attachment_${svgAttachTableId} WHERE aid='${svgAid}' AND tid='${svgTid}' LIMIT 1;"`).toString().trim();
        assert.ok(svgIsImage === '1' || svgIsImage === '2', `Assertion Error: Uploaded SVG was not stored as an image. isimage: ${svgIsImage}`);
        const svgStoredFile = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT attachment FROM pre_forum_attachment_${svgAttachTableId} WHERE aid='${svgAid}' AND tid='${svgTid}' LIMIT 1;"`).toString().trim();
        const svgStoredContent = fs.readFileSync(`data/attachment/forum/${svgStoredFile}`, 'utf8');
        assert.match(svgStoredContent, /<use[^>]* href="#dot"/, 'Assertion Error: Stored SVG did not normalize xlink:href to href.');
        assert.match(svgStoredContent, /<linearGradient[^>]* href="#base"/, 'Assertion Error: Stored SVG did not preserve a local gradient inheritance reference.');
        assert.ok(!svgStoredContent.includes('xlink:href'), 'Assertion Error: Stored SVG retained legacy xlink:href.');
        assert.match(svgStoredContent, /<circle[^>]* fill="url\(#derived\)"[^>]* stroke="#fff"/, 'Assertion Error: Stored SVG did not convert safe inline styles to presentation attributes.');
        assert.match(svgStoredContent, /<use[^>]* opacity=".8"/, 'Assertion Error: Stored SVG did not preserve a safe inline opacity.');
        assert.ok(!svgStoredContent.includes('style='), 'Assertion Error: Stored SVG retained an inline style attribute.');
        assert.ok(!svgStoredContent.includes('example.com'), 'Assertion Error: Stored SVG retained an external CSS resource.');

        const svgViewthreadBody = await page.textContent('body');
        assert.ok(
            svgViewthreadBody.includes(svgAttachmentSubject) && svgViewthreadBody.includes('Posting thread with SVG image content.'),
            'Assertion Error: SVG attachment thread page did not load content cleanly in viewthread.'
        );
        report += `### 6c. SVG Attachment Post\n- **Status**: Checked\n- **Thread Created**: ${svgAttachmentSubject} (TID: ${svgTid}, AID: ${svgAid})\n- **SVG Stored as Image (isimage)**: ${svgIsImage}\n- **Screenshot**: \`screenshot_attachment_svg_viewthread.png\`\n\n`;

        // 7. Tag Edit Panel & Retagging Test
        console.log("Testing Tag Edit Panel & Retagging functionality...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
        await page.waitForLoadState('networkidle');

        const editTagBtn = page.locator('a[onclick*="misc.php?mod=tag&op=manage"]');
        assert.strictEqual(await editTagBtn.count(), 1, 'Assertion Error: Tag edit button (misc.php?mod=tag&op=manage) did not render in viewthread.');
        await editTagBtn.click();

        await page.waitForSelector('#fwin_mods #keyword-input', { state: 'visible', timeout: 5000 });
        await page.screenshot({ path: 'screenshot_tag_itembox_edit.png' });

        // #tags is hidden; type into #keyword-input and press Enter to call addKeyword()
        const tagInput = page.locator('#fwin_mods #keyword-input');
        await tagInput.fill('retagtest');
        await tagInput.press('Enter');

        const saveTagBtn = page.locator('#fwin_mods button[name="search_button"]');
        const tagSetResponse = page.waitForResponse(response =>
            response.request().method() === 'GET' &&
            response.url().includes('forum.php?mod=misc&action=retag')
        );
        await saveTagBtn.click();
        await tagSetResponse;

        const dbTags = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tags FROM pre_forum_thread WHERE tid='${tidOutput}';"`, { encoding: 'utf-8' }).trim();
        assert.ok(dbTags.includes('retagtest'), `Assertion Error: Database tags column for TID ${tidOutput} was not updated. Actual: ${dbTags}`);

        const dbModerated = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT moderated FROM pre_forum_thread WHERE tid='${tidOutput}';"`, { encoding: 'utf-8' }).trim();
        assert.strictEqual(dbModerated, '1', `Assertion Error: Database moderated column for TID ${tidOutput} was not set to 1 after retagging.`);

        const dbModLog = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT action FROM pre_forum_threadmod WHERE tid='${tidOutput}' AND action='TAG';"`, { encoding: 'utf-8' }).trim();
        assert.strictEqual(dbModLog, 'TAG', `Assertion Error: Thread moderation history log did not record action TAG for TID ${tidOutput}.`);

        const modActText = await page.locator('.modact').first().textContent().catch(() => '');
        assert.ok(modActText.includes('admin') || modActText.length > 0, `Assertion Error: Moderation log bar (.modact) did not render in viewthread after retagging.`);

        console.log("Tag Edit Panel & Retagging test passed!");
        report += `### 7. Tag Edit Panel & Retagging\n- **Status**: Passed\n- **Tag Retagged**: retagtest\n- **Threadmod Log Action**: TAG\n- **Moderated Column**: 1\n- **Screenshot**: \`screenshot_tag_itembox_edit.png\`\n\n`;

        // 8. WYSIWYG mode preserves TeX source through a round trip.
        console.log("Testing WYSIWYG mode TeX preservation...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=${forumFid}`);
        await page.waitForLoadState('networkidle');

		// Test that inline and display TeX formulas render in WYSIWYG and survive save, restore, and submission.
		const inlineMathSource = '$f$';
		const displayMathSource = '$$A(p) = \\#\\{q \\in E : \\text{on}(q, p)\\}$$';
		const mathContent = 'Inline ' + inlineMathSource + ' and display ' + displayMathSource + ' math';
		const waitForMathRendered = () => page.waitForFunction(expectedSources => {
			const frame = document.querySelector('#e_iframe');
			if (!frame || !frame.contentDocument) return false;
			const rendered = Array.from(frame.contentDocument.querySelectorAll('.math-editor-rendered'));
			if (rendered.length !== expectedSources.length) return false;
			const sources = rendered.map(el => el.getAttribute('data-math-source'));
			if (!expectedSources.every(source => sources.includes(source))) return false;
			if (frame.contentDocument.querySelector('.math-editor-rendered .math-editor-rendered')) return false;
			return rendered.every(el => el.querySelector('mjx-container'));
		}, [inlineMathSource, displayMathSource]);
		await page.fill('#e_textarea', mathContent);
		await page.locator('#e_visual_btn').click();
		await waitForMathRendered();

		// Idempotency: re-running the renderer must not nest or duplicate formulas.
		await page.evaluate(() => window.renderMathEditorContent());
		await waitForMathRendered();

		// Idempotency: the TeX source must survive repeated WYSIWYG round trips unchanged.
		for (let roundTrip = 1; roundTrip <= 2; roundTrip++) {
			await page.locator('#e_code_btn').click();
			assert.strictEqual(await page.inputValue('#e_textarea'), mathContent, `Assertion Error: TeX math source was corrupted after WYSIWYG round trip ${roundTrip}.`);
			await page.locator('#e_visual_btn').click();
			await waitForMathRendered();
		}
		await page.locator('#e_svd').click();
		await page.locator('#e_code_btn').click();
		await page.fill('#e_textarea', 'Unsaved replacement content');
		await page.locator('#e_visual_btn').click();
		page.once('dialog', dialog => dialog.accept());
		await page.locator('#e_rst').click();
		await waitForMathRendered();
		await page.locator('#e_code_btn').click();
		const editorTextAfterToggle = await page.inputValue('#e_textarea');
		assert.strictEqual(editorTextAfterToggle, mathContent, 'Assertion Error: TeX math formulas were corrupted after saving and restoring editor data.');
		await page.locator('#e_visual_btn').click();
		await waitForMathRendered();
		await page.fill('input[name="subject"]', mathDraftSubject);
		await solveSecurityQuestion(page);
		const mathSubmitBtn = page.locator('button[name="topicsubmit"][type="submit"]');
		const [mathPostResponse] = await Promise.all([
			page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('forum.php?mod=post')),
			mathSubmitBtn.click()
		]);
		assert.ok(mathPostResponse.ok() || (mathPostResponse.status() >= 300 && mathPostResponse.status() < 400), `Assertion Error: Restored math draft POST failed with HTTP ${mathPostResponse.status()}.`);
		const mathTid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tid FROM pre_forum_thread WHERE subject='${mathDraftSubject}' ORDER BY tid DESC LIMIT 1;"`).toString().trim();
		assert.match(mathTid, /^\d+$/, 'Assertion Error: Restored math draft thread ID was not found.');
		await page.waitForURL(new RegExp(`forum\\.php\\?mod=viewthread&tid=${mathTid}(&|$)`));
		await page.waitForLoadState('load');
		const mathDraftMessage = execSync(`sudo mysql --raw -u root ultrax -N -s -e "SELECT p.message FROM pre_forum_post p INNER JOIN pre_forum_thread t ON t.tid=p.tid WHERE t.subject='${mathDraftSubject}' AND p.first=1 LIMIT 1;"`, { encoding: 'utf-8' }).trim();
		assert.strictEqual(mathDraftMessage, mathContent, `Assertion Error: Submitted restored math draft did not preserve the original TeX source.\nExpected: ${JSON.stringify(mathContent)}\nActual:   ${JSON.stringify(mathDraftMessage)}`);

		console.log("WYSIWYG mode TeX preservation test passed!");
		report += `### 8. WYSIWYG Math Draft Preservation\n- **Status**: Passed\n- **Rendering**: inline \`$f$\` and display \`$$...$$\` rendered as \`mjx-container\`\n- **Idempotency**: no nested/duplicate formulas and source unchanged after repeated renders and mode round trips\n- **Save/Restore**: formulas rendered after restoration\n- **Submission**: Original TeX preserved in database\n\n`;

        // 9. Existing math is rendered when the WYSIWYG editor opens (6178d4e8).
        // Without the feature, math already present in the editor content on entering
        // WYSIWYG mode is left as raw $...$ / $$...$$ text instead of being typeset.
        console.log("Testing WYSIWYG editor rendering existing math on open...");
        const existingInlineMath = '$x^2 + y^2 = z^2$';
        const existingDisplayMath = '$$\\sum_{k=1}^{n} k = \\frac{n(n+1)}{2}$$';
        const existingMathMessage = `Before ${existingInlineMath} and before ${existingDisplayMath} after.`;

        // Create a thread whose first post contains math while the forum still uses the source editor.
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=${forumFid}`);
        await page.waitForLoadState('networkidle');
        await page.fill('input[name="subject"]', existingMathSubject);
        await page.fill('#e_textarea', existingMathMessage);
        await solveSecurityQuestion(page);
        const existingMathSubmitBtn = page.locator('button[name="topicsubmit"][type="submit"]');
        assert.strictEqual(await existingMathSubmitBtn.count(), 1, 'Assertion Error: Existing-math thread submit button did not render.');
        const [existingMathPostResponse] = await Promise.all([
            page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('forum.php?mod=post')),
            existingMathSubmitBtn.click()
        ]);
        assert.ok(existingMathPostResponse.ok() || (existingMathPostResponse.status() >= 300 && existingMathPostResponse.status() < 400), `Assertion Error: Existing-math thread POST failed with HTTP ${existingMathPostResponse.status()}.`);
        const existingMathTid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tid FROM pre_forum_thread WHERE subject='${existingMathSubject}' ORDER BY tid DESC LIMIT 1;"`).toString().trim();
        assert.match(existingMathTid, /^\d+$/, 'Assertion Error: Existing-math thread ID was not found.');
        const existingMathPid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${existingMathTid}' AND first=1 LIMIT 1;"`).toString().trim();
        assert.match(existingMathPid, /^\d+$/, 'Assertion Error: Existing-math first post ID was not found.');
        const storedExistingMath = execSync(`sudo mysql --raw -u root ultrax -N -s -e "SELECT message FROM pre_forum_post WHERE pid='${existingMathPid}';"`, { encoding: 'utf-8' }).trim();
        assert.strictEqual(storedExistingMath, existingMathMessage, 'Assertion Error: Existing-math thread stored an unexpected message.');

        try {
            // Force this forum's editor to open directly in WYSIWYG mode.
            execSync(`sudo mysql -u root ultrax -e "UPDATE pre_forum_forum SET editormode=1 WHERE fid='${forumFid}';"`);

            await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=edit&fid=${forumFid}&tid=${existingMathTid}&pid=${existingMathPid}&extra=page%3D1`);
            await page.waitForLoadState('domcontentloaded');

            assert.strictEqual(await page.evaluate(() => wysiwyg), 1, 'Assertion Error: Edit page did not open the editor in WYSIWYG mode.');
            assert.strictEqual(await page.locator('#e_iframe:visible').count(), 1, 'Assertion Error: WYSIWYG editor iframe was not visible.');

            await page.waitForFunction(({ inline, display }) => {
                const frame = document.querySelector('#e_iframe');
                if (!frame || !frame.contentDocument || !frame.contentDocument.body) return false;
                const rendered = frame.contentDocument.querySelectorAll('.math-editor-rendered');
                if (rendered.length !== 2) return false;
                const sources = Array.from(rendered).map(node => node.getAttribute('data-math-source'));
                return sources.includes(inline) && sources.includes(display)
                    && Array.from(rendered).every(node => node.querySelector('mjx-container'))
                    && Array.from(rendered).every(node => node.getAttribute('contenteditable') === 'false');
            }, { inline: existingInlineMath, display: existingDisplayMath });

            const renderedDetails = await page.frameLocator('#e_iframe').locator('.math-editor-rendered').evaluateAll(nodes => nodes.map(node => ({
                source: node.getAttribute('data-math-source'),
                editable: node.getAttribute('contenteditable'),
                typeset: !!node.querySelector('mjx-container')
            })));
            assert.strictEqual(renderedDetails.length, 2, `Assertion Error: Expected 2 rendered formulas, found ${renderedDetails.length}.`);
            for (const detail of renderedDetails) {
                assert.ok(detail.source === existingInlineMath || detail.source === existingDisplayMath, `Assertion Error: Unexpected rendered formula source ${detail.source}.`);
                assert.strictEqual(detail.editable, 'false', 'Assertion Error: Rendered formula span was not marked contenteditable=false.');
                assert.strictEqual(detail.typeset, true, 'Assertion Error: Rendered formula span had no typeset output.');
            }

            // Re-running the renderer over already-rendered content must not double-wrap formulas.
            await page.evaluate(() => window.renderMathEditorContent());
            await page.waitForFunction(() => {
                const frame = document.querySelector('#e_iframe');
                if (!frame || !frame.contentDocument) return false;
                return frame.contentDocument.querySelectorAll('.math-editor-rendered').length === 2;
            });
            const afterReRender = await page.frameLocator('#e_iframe').locator('.math-editor-rendered').count();
            assert.strictEqual(afterReRender, 2, `Assertion Error: Re-rendering wrapped existing math ${afterReRender} times instead of once.`);

            // Round trip: source mode must recover the original TeX, then WYSIWYG re-renders exactly once.
            await page.locator('#e_code_btn').click();
            const sourceAfterRoundTrip = await page.inputValue('#e_textarea');
            assert.strictEqual(sourceAfterRoundTrip, existingMathMessage, 'Assertion Error: Existing math was corrupted after switching back to source.');
            await page.locator('#e_visual_btn').click();
            await page.waitForFunction(({ inline, display }) => {
                const frame = document.querySelector('#e_iframe');
                if (!frame || !frame.contentDocument) return false;
                const rendered = frame.contentDocument.querySelectorAll('.math-editor-rendered');
                const sources = Array.from(rendered).map(node => node.getAttribute('data-math-source'));
                return rendered.length === 2 && sources.includes(inline) && sources.includes(display)
                    && Array.from(rendered).every(node => node.querySelector('mjx-container'));
            }, { inline: existingInlineMath, display: existingDisplayMath });
            await page.screenshot({ path: 'screenshot_wysiwyg_existing_math.png', fullPage: true });

            report += `### 9. WYSIWYG Editor Renders Existing Math on Open\n- **Status**: Passed\n- **Inline Math**: ${existingInlineMath}\n- **Display Math**: ${existingDisplayMath}\n- **Direct WYSIWYG Open**: rendered without interaction\n- **Idempotent Re-Render**: Passed\n- **TeX Round Trip**: Passed\n- **Screenshot**: \`screenshot_wysiwyg_existing_math.png\`\n\n`;
        } finally {
            execSync(`sudo mysql -u root ultrax -e "UPDATE pre_forum_forum SET editormode=-1 WHERE fid='${forumFid}';"`);
        }
        console.log("WYSIWYG editor existing-math rendering test passed!");

    } catch (error) {
        console.error("Test execution failed:", error);
        process.exitCode = 1;
        console.log('::error::' + String(error && error.message || error).slice(0, 1000).replace(/[\r\n]+/g, ' | '));
        try {
            const currentUrl = page.url();
            const pageTitle = await page.title().catch(() => 'Unknown Title');
            const pageSource = await page.content().catch(() => '');
            if (pageSource) {
                fs.writeFileSync('forum_page_source.html', pageSource);
                fs.writeFileSync('browser_page_source.html', pageSource);
            }
            await page.screenshot({ path: 'screenshot_forum_failure.png', fullPage: true }).catch(() => {});
            const errLog = `[Forum Failure] URL: ${currentUrl} | Title: ${pageTitle}\nError: ${error.stack || error.message}\nPage Source saved to forum_page_source.html\n---\n`;
            fs.appendFileSync('browser_error.txt', errLog);
        } catch (e) {
            console.error('Failed to capture failure state:', e.message);
        }
        report += "## Error Encountered\n```\n" + error.message + "\n```\n\n";
        const gistBody = (report + "\n\n--- browser_error.txt ---\n" + (fs.existsSync('browser_error.txt') ? fs.readFileSync('browser_error.txt','utf8') : '')).slice(0, 90000);
        await reportCiFailure({ label: 'forum', body: '**FAIL ' + testRunId + ' forum**\n\n```\n' + gistBody.slice(0, 50000) + '\n```' });
    } finally {
        await browser.close();
        fs.writeFileSync('functional_test_report.md', report);
        console.log("Tests completed.");
    }
})();
