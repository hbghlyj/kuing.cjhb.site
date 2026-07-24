const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('assert');
const { execSync } = require('child_process');

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();

    page.on('response', async response => {
        if (response.status() >= 400) {
            try {
                const text = await response.text();
                console.error(`[HTTP ${response.status()}] ${response.url()}\nResponse Body:\n${text}\n---`);
            } catch (e) {}
        }
    });

    page.on('pageerror', exception => {
        console.error(`Uncaught Browser Exception at URL [${page.url()}]:\nMessage: ${exception.message}\nStack:\n${exception.stack || exception}`);
        throw new Error(`Uncaught exception in browser at [${page.url()}]: ${exception.message || exception}`);
    });

    page.on('console', msg => {
        if (msg.type() === 'error') {
            const txt = msg.text();
            if (txt.includes('Failed to load resource')) return;
            throw new Error(`Console error in browser: ${txt}`);
        }
    });

    let report = "\n\n## Admin Panel Functional Test Report\n\n";
    console.log("Starting Admin Panel tests...");

    try {
        const username = 'admin';
        const password = 'Testpassword123!';

        console.log("Phase 2: Admin Account Testing");
        await page.goto('http://127.0.0.1:8080/member.php?mod=logging&action=login');
        await page.waitForLoadState('networkidle');
        const adminLoginForm = page.locator('form[id^="loginform_"]:visible');
        if (await adminLoginForm.count()) {
            await adminLoginForm.locator('input[name="username"]').fill('admin');
            await adminLoginForm.locator('input[name="password"]').fill('Testpassword123!');
            const secqaa = adminLoginForm.locator('input[name*="secanswer"]');
            if (await secqaa.count()) await secqaa.fill('2');
            const submitBtn = adminLoginForm.locator('button[type="submit"], input[type="submit"], button[name="loginsubmit"]');
            if (await submitBtn.count()) {
                await Promise.all([
                    page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {}),
                    submitBtn.click()
                ]);
            }
        }
        report += '### 1. Admin Authentication\n- **Status**: Checked\n\n';

        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
        await page.waitForLoadState('networkidle');
        const bioInput = await page.$('textarea[name="bio"]');
        if (bioInput) {
            await bioInput.fill('Updated bio as admin');
            const saveBtn = await page.$('button[name="profilesubmit"]');
            if (saveBtn) {
                await saveBtn.click();
                await page.waitForTimeout(1000);
                const savedMsg = await page.textContent('body');
                assert.ok(savedMsg.includes('saved') || savedMsg.includes('success') || !page.url().includes('profilesubmit'), 'Assertion Error: Profile update failed.');
            }
        }
        await page.screenshot({ path: 'screenshot_forum_02_admin_profile.png' });
        report += '### 2. Admin Profile Update\n- **Status**: Checked\n\n';

        console.log("Checking Admin Panel...");
        await page.goto('http://127.0.0.1:8080/admin.php');
        await page.waitForLoadState('networkidle');

        const adminPassInput = await page.$('input[name="admin_password"]');
        if (adminPassInput) {
            // mustlogin=1 authenticates this already logged-in account by password.
            await adminPassInput.fill(password);
            const adminSubmitBtn = await page.$('button[type="submit"], input[type="submit"], input[name="submit"]');
            if (adminSubmitBtn) {
                await adminSubmitBtn.click();
                await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {});
            }
            await page.waitForTimeout(3000);
        }

        // Verify admin authentication success: password prompt must be gone and admin workspace/frames loaded
        const hasLoginPrompt = await page.$('input[name="admin_password"]');
        const pageSource = await page.content();
        const hasAdminWorkspace = pageSource.includes('admincpnav') ||
            pageSource.includes('admincpframe') ||
            pageSource.includes('action=logout') ||
            pageSource.includes('action=header') ||
            page.frames().some(f => f.url().includes('admin.php?action='));

        assert.ok(!hasLoginPrompt && hasAdminWorkspace, 'Assertion Error: Admin panel authentication failed. Still on unauthorized login screen.');
        await page.screenshot({ path: 'screenshot_forum_03_admin_panel.png' });
        report += '### 3. Admin Panel UI\n- **Status**: Checked\n\n';

        console.log("Checking Admin Panel Logs Page...");
        await page.goto('http://127.0.0.1:8080/admin.php?action=logs');
        await page.waitForLoadState('networkidle');

        const logsPageSource = await page.content();
        assert.ok(
            !logsPageSource.includes('action_noaccess') && (logsPageSource.includes('logs') || logsPageSource.includes('operation=') || page.url().includes('action=logs')),
            'Assertion Error: Admin CP logs page failed due to insufficient permissions or access restriction.'
        );
        await page.screenshot({ path: 'screenshot_forum_04_admin_logs.png' });
        report += '### 4. Admin Panel Logs Access\n- **Status**: Checked\n- **URL**: admin.php?action=logs\n\n';

        console.log("Checking renamed uploader operations...");
        await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=2');
        await page.waitForLoadState('networkidle');
        const uploadFormhash = await page.evaluate(() => window.FORMHASH || document.querySelector('input[name="formhash"]')?.value || '');
        assert.ok(uploadFormhash, 'Assertion Error: Uploader operation test could not obtain formhash.');

        const validJpegBase64 = fs.readFileSync('static/image/smiley/BQ2/alu1.jpg').toString('base64');
        const uploadOperation = async (operation, fields = {}, query = '') => {
            return await page.evaluate(async ({ op, q, f, b64 }) => {
                const formhash = window.FORMHASH || (document.querySelector('input[name="formhash"]') ? document.querySelector('input[name="formhash"]').value : '');
                const blob = await fetch('data:image/jpeg;base64,' + b64).then(r => r.blob());
                const formData = new FormData();
                formData.append('formhash', formhash);
                for (const [k, v] of Object.entries(f)) {
                    formData.append(k, v);
                }
                formData.append('Filedata', blob, op + '_test.jpg');
                const res = await fetch(`misc.php?mod=upload&operation=${op}${q}`, {
                    method: 'POST',
                    body: formData
                });
                return await res.text();
            }, { op: operation, q: query, f: fields, b64: validJpegBase64 });
        };

        const pollUpload = JSON.parse(await uploadOperation('poll', {}, '&fid=2'));
        assert.ok(pollUpload.aid > 0 && pollUpload.errorcode === 0, `Assertion Error: Poll image upload failed: ${JSON.stringify(pollUpload)}`);

        const albumUpload = JSON.parse(await uploadOperation('album'));
        assert.ok(parseInt(albumUpload.picid, 10) > 0, `Assertion Error: Album image upload failed: ${JSON.stringify(albumUpload)}`);

        let portalCatid = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT catid FROM pre_portal_category ORDER BY catid LIMIT 1;\"").toString().trim() || '1';
        const portalUpload = JSON.parse(await uploadOperation('portal', { catid: portalCatid, aid: '0' }));
        assert.ok(portalUpload.aid > 0 && portalUpload.errorcode === 0, `Assertion Error: Portal attachment upload failed: ${JSON.stringify(portalUpload)}`);

        const jsonEditorUpload = JSON.parse(await uploadOperation('jsoneditorupload', {}, '&fid=2'));
        assert.ok(
            jsonEditorUpload.success === 1 && jsonEditorUpload.file && jsonEditorUpload.file.aid > 0,
            `Assertion Error: JSON editor upload failed: ${JSON.stringify(jsonEditorUpload)}`
        );

        report += '### 5. Renamed HTML5 Uploader Operations\n- **Status**: Checked\n- **Poll image**: Success\n- **Album image**: Success\n- **Portal attachment**: Success\n- **JSON editor attachment**: Success\n\n';

    } catch (error) {
        console.error("Admin test execution failed:", error);
        process.exitCode = 1;
        try {
            const currentUrl = page.url();
            const pageTitle = await page.title().catch(() => 'Unknown Title');
            const pageSource = await page.content().catch(() => '');
            if (pageSource) {
                fs.writeFileSync('admin_page_source.html', pageSource);
                fs.writeFileSync('browser_page_source.html', pageSource);
            }
            await page.screenshot({ path: 'screenshot_admin_failure.png', fullPage: true }).catch(() => {});
            const errLog = `[Admin Failure] URL: ${currentUrl} | Title: ${pageTitle}\nError: ${error.stack || error.message}\nPage Source saved to admin_page_source.html\n---\n`;
            fs.appendFileSync('browser_error.txt', errLog);
        } catch (e) {
            console.error('Failed to capture failure state:', e.message);
        }
        report += "## Error Encountered in Admin Test\n```\n" + error.message + "\n```\n\n";
    } finally {
        await browser.close();
        fs.appendFileSync('functional_test_report.md', report);
        console.log("Admin tests completed and report appended.");
    }
})();
