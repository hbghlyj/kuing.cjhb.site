const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('assert');
const { reportCiFailure } = require('./report_ci_failure');
const { execSync } = require('child_process');

const PUSHER_STUB = `
(() => {
  const noopChannel = {
    bind: function() {},
    unbind: function() {}
  };
  window.Pusher = function() {
    this.connection = { bind: function() {}, unbind: function() {} };
    this.subscribe = function() { return noopChannel; };
    this.unsubscribe = function() {};
    this.disconnect = function() {};
  };
})();
`;

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    await context.route('**/chat/pusher.min.js', route => route.fulfill({
        contentType: 'application/javascript',
        body: PUSHER_STUB
    }));
    const page = await context.newPage();

    page.on('pageerror', exception => {
        throw new Error(`Uncaught exception in browser: ${exception}`);
    });

    page.on('requestfailed', request => {
        throw new Error(`Browser request failed: ${request.url()} (${request.failure()?.errorText || 'unknown error'})`);
    });

    page.on('console', msg => {
        if (msg.type() === 'error') {
            const loc = msg.location();
            const detail = `text="${msg.text()}" at ${loc.url || 'unknown'}:${loc.lineNumber}`;
            console.error(`[Browser Console Error] ${detail}`);
            throw new Error(`Console error in browser: ${detail}`);
        }
    });

    let report = "\n\n## Tags Feature Functional Test Report\n\n";
    console.log("Starting Tags Feature tests...");
    const solveSecurityQuestion = async root => {
        const input = root.locator('input[name*="secanswer"]:visible');
        if(!await input.count() && await root.locator('[id^="secqaa_q"]').count()) {
            await input.waitFor({ state: 'visible', timeout: 5000 });
        }
        if(!await input.count()) {
            return false;
        }
        assert.strictEqual(await input.count(), 1, 'Assertion Error: Tags test security-answer input did not render exactly once.');
        await input.fill('2');
        const [response] = await Promise.all([
            page.waitForResponse(item =>
                item.url().includes('misc.php?mod=secqaa') &&
                item.url().includes('action=check')
            ),
            input.press('Tab')
        ]);
        assert.ok((await response.text()).includes('succeed'), 'Assertion Error: Tags test security answer was rejected.');
        return true;
    };

    try {
        console.log("Logging in as admin to post thread with tags via UI...");
        await page.goto('http://127.0.0.1:8080/member.php?mod=logging&action=login');
        await page.waitForLoadState('domcontentloaded');
        const loginForm = page.locator('form[id^="loginform_"]:visible');
        assert.strictEqual(await loginForm.count(), 1, 'Assertion Error: Tags test login form did not render.');
        await loginForm.locator('input[name="username"]').fill('admin');
        await loginForm.locator('input[name="password"]').fill('Testpassword123!');
        const secqaa = loginForm.locator('input[name*="secanswer"]');
        if (await secqaa.count()) await secqaa.fill('2');
        const loginSubmitBtn = loginForm.locator('button[type="submit"], input[type="submit"], button[name="loginsubmit"]');
        assert.strictEqual(await loginSubmitBtn.count(), 1, 'Assertion Error: Tags test login submit control did not render.');
        const [loginResponse] = await Promise.all([
            page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('member.php?mod=logging')
            ),
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            loginSubmitBtn.click()
        ]);
        assert.ok(
            loginResponse.ok() || (loginResponse.status() >= 300 && loginResponse.status() < 400),
            `Assertion Error: Tags test login POST failed with HTTP ${loginResponse.status()}.`
        );

        const spaceUrl = page.url();
        assert.ok(
            !spaceUrl.includes('mod=logging'),
            `Assertion Error: Tags test admin login failed — redirected to ${spaceUrl}`
        );


        console.log("Posting new thread with tags in Forum (fid=2) via UI...");
        await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=2');
        await page.waitForLoadState('networkidle');
        await page.locator('input[name="subject"]:visible').fill('Thread with Tags');

        const editorFrame = page.locator('iframe[id$="_iframe"]');
        if(await editorFrame.count()) {
            assert.strictEqual(await editorFrame.count(), 1, 'Assertion Error: More than one tag-post editor iframe rendered.');
            await page.frameLocator('iframe[id$="_iframe"]').locator('body').fill('Posting thread content with tag via UI.');
        } else {
            const textArea = page.locator('textarea[name="message"]:visible');
            assert.strictEqual(await textArea.count(), 1, 'Assertion Error: Tag post editor did not render.');
            await textArea.fill('Posting thread content with tag via UI.');
        }

        const tagInput = page.locator('#keyword-input:visible');
        assert.strictEqual(await tagInput.count(), 1, 'Assertion Error: Visible tag input did not render.');
        await tagInput.fill('playwright tag');
        await tagInput.press('Enter');
        assert.strictEqual(await page.locator('#tags').inputValue(), 'playwright tag', 'Assertion Error: Tag editor did not preserve space in multi-word tag.');
        await solveSecurityQuestion(page);

        const postsubmitBtn = page.locator('button[name="topicsubmit"]:visible');
        assert.strictEqual(await postsubmitBtn.count(), 1, 'Assertion Error: Tag post submit button did not render.');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            postsubmitBtn.click()
        ]);
        assert.match(page.url(), /mod=viewthread&tid=\d+/, 'Assertion Error: Tagged thread submission did not navigate to the new thread.');
        const createdTid = new URL(page.url()).searchParams.get('tid');
        assert.match(createdTid || '', /^\d+$/, 'Assertion Error: Tagged thread submission did not provide a thread ID.');
        const tagidOutput = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT tagid FROM pre_common_tag WHERE tagname='playwright tag' LIMIT 1;\"").toString().trim();
        assert.match(tagidOutput, /^\d+$/, 'Assertion Error: Submitted multi-word tag was not created in the database.');

        console.log("Testing Tag Search...");
        const tagLink = page.locator('a[href*="misc.php?mod=tag"]').filter({ hasText: 'playwright tag' });
        assert.strictEqual(await tagLink.count(), 1, 'Assertion Error: Submitted tag link did not render on the created thread.');
        assert.strictEqual(await tagLink.getAttribute('target'), '_blank', 'Assertion Error: Submitted tag link did not target a new tab.');
        const [tagPage] = await Promise.all([
            page.waitForEvent('popup'),
            tagLink.click()
        ]);
        await tagPage.waitForLoadState('networkidle');
        const tagResultLink = tagPage.locator('a[href^="forum.php?mod=viewthread&tid=' + createdTid + '"]').filter({ hasText: 'Thread with Tags' });
        assert.strictEqual(await tagResultLink.count(), 1, 'Assertion Error: Tag search result did not link to the created thread.');
        await tagPage.screenshot({ path: 'screenshot_tags_03_search_result.png' });
        await tagPage.close();
        report += `### Tag Search Result\n- **Status**: Checked\n- **Screenshot**: \`screenshot_tags_03_search_result.png\`\n\n`;

        // Admin Tag Management Check
        console.log("Testing Admin Panel Tag Management UI...");
        await page.goto('http://127.0.0.1:8080/admin.php?action=tag');
        await page.waitForLoadState('networkidle');
        const adminPassword = page.locator('input[name="admin_password"]');
        if (await adminPassword.count()) {
            await adminPassword.fill('Testpassword123!');
            const adminSubmit = page.locator('button[type="submit"], input[type="submit"], input[name="submit"]');
            assert.strictEqual(await adminSubmit.count(), 1, 'Assertion Error: AdminCP password submit control did not render.');
            const [adminResponse] = await Promise.all([
                page.waitForResponse(response =>
                    response.request().method() === 'POST' &&
                    response.url().includes('admin.php')
                ),
                adminSubmit.click()
            ]);
            assert.ok(
                adminResponse.ok() || (adminResponse.status() >= 300 && adminResponse.status() < 400),
                `Assertion Error: AdminCP authentication failed with HTTP ${adminResponse.status()}.`
            );
        }
        const adminContent = page.url().includes('frames=yes') ? page.frameLocator('#main') : page;
        const tagAdminForm = adminContent.locator('form[action*="action=tag"]');
        await tagAdminForm.waitFor({ state: 'visible' });
        assert.strictEqual(await tagAdminForm.count(), 1, 'Assertion Error: Admin tag-management form did not render.');
        assert.strictEqual(await tagAdminForm.locator('input[name="tagname"]').count(), 1, 'Assertion Error: Admin tag search field did not render.');
        report += `### Admin Tag Management UI\n- **Status**: Passed\n\n`;

    } catch (error) {
        console.error("Test execution failed:", error);
        process.exitCode = 1;
        console.log('::error::' + String(error && error.message || error).slice(0, 1000).replace(/[\r\n]+/g, ' | '));
        const failBody = ('**FAIL ' + (process.env.TEST_RUN_ID || 'ci') + ' tags**\n\n```\n' + String(error.stack || error.message).slice(0, 50000) + '\n```').slice(0, 60000);
        await reportCiFailure({ label: 'tags', body: failBody });
        report += "## Error Encountered in Tags Test\n```\n" + error.message + "\n```\n\n";
    } finally {
        await browser.close();
        fs.appendFileSync('functional_test_report.md', report);
        console.log("Tags tests completed and report appended.");
    }
})();
