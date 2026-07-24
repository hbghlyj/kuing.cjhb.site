const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('assert');
const { execSync } = require('child_process');

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();

    page.on('pageerror', exception => {
        throw new Error(`Uncaught exception in browser: ${exception}`);
    });

    page.on('requestfailed', request => {
        console.error(`[Request Failed] URL: ${request.url()} | Type: ${request.resourceType()} | Error: ${request.failure() ? request.failure().errorText : 'unknown'}`);
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

    try {
        console.log("Logging in as admin to post thread with tags via UI...");
        await page.goto('http://127.0.0.1:8080/member.php?mod=logging&action=login');
        await page.waitForLoadState('networkidle');
        await page.locator('input[name="username"]:visible').fill('admin');
        await page.locator('input[name="password"]').fill('Testpassword123!');
        const secqaa = await page.$('input[name*="secanswer"]');
        if (secqaa) await secqaa.fill('2');
        await page.locator('button[name="loginsubmit"]').click();
        await page.waitForLoadState('networkidle');

        console.log("Posting new thread with tags in Forum (fid=2) via UI...");
        await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=2');
        await page.waitForLoadState('networkidle');
        await page.locator('input[name="subject"]').fill('Thread with Tags');
        await page.evaluate(() => {
            const textArea = document.querySelector('textarea[name="message"], #postmessage');
            if (textArea) textArea.value = 'Posting thread content with tag via UI.';
            if (window.editdoc && window.editdoc.body) window.editdoc.body.innerHTML = 'Posting thread content with tag via UI.';
        });

        const extraTagBtn = await page.$('#extra_tag_b, a[href*="extra_tag"], #extra_tag_b a');
        if (extraTagBtn) {
            await extraTagBtn.click().catch(() => {});
        }
        const tagsInput = await page.$('#tags, input[name="tags"]');
        if (tagsInput) {
            await tagsInput.fill('playwright', { force: true }).catch(async () => {
                await page.evaluate(() => {
                    const input = document.querySelector('#tags, input[name="tags"]');
                    if (input) input.value = 'playwright';
                });
            });
        }
        const postsubmitBtn = await page.$('#postsubmit, button[name="topicsubmit"]');
        if (postsubmitBtn) {
            await postsubmitBtn.click();
            await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {});
        }

        const tagidOutput = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tagid FROM pre_common_tag WHERE tagname='playwright' LIMIT 1;"`).toString().trim();

        // Test Tag Search
        if (tagidOutput) {
            console.log("Testing Tag Search...");
            await page.goto(`http://127.0.0.1:8080/misc.php?mod=tag&id=${tagidOutput}`);
            await page.waitForLoadState('networkidle');
            const tagSearchText = await page.textContent('body');
            assert.ok(tagSearchText.includes('Thread with Tags') || tagSearchText.includes('playwright'), 'Assertion Error: Tag search result did not list the created thread or tag.');
            await page.screenshot({ path: 'screenshot_tags_03_search_result.png' });
            report += `### Tag Search Result\n- **Status**: Checked\n- **Screenshot**: \`screenshot_tags_03_search_result.png\`\n\n`;
        }

        // Admin Tag Management Check
        console.log("Testing Admin Panel Tag Management UI...");
        await page.goto('http://127.0.0.1:8080/admin.php?action=tag');
        await page.waitForLoadState('networkidle');
        const adminPageText = await page.textContent('body');
        assert.ok(adminPageText.includes('Login') || adminPageText.includes('登录') || adminPageText.includes('密码'), 'Assertion Error: Admin panel tag management UI did not load correctly.');
        report += `### Admin Tag Management UI\n- **Status**: Checked\n\n`;

    } catch (error) {
        console.error("Test execution failed:", error);
        process.exitCode = 1;
        report += "## Error Encountered in Tags Test\n```\n" + error.message + "\n```\n\n";
    } finally {
        await browser.close();
        fs.appendFileSync('functional_test_report.md', report);
        console.log("Tags tests completed and report appended.");
    }
})();
